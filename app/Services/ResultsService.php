<?php

namespace App\Services;

use App\Helpers\SiteHelper;
use App\Models\AcademicTerm;
use App\Models\AssessmentScore;
use App\Models\AssessmentScoreAudit;
use App\Models\Attendance;
use App\Models\School;
use App\Models\SchoolAcademicSetting;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\Teacherlink;
use App\Models\TermResult;
use App\Models\User;

class ResultsService
{
    public function settings(int $schoolId, int $yearId): SchoolAcademicSetting
    {
        return SchoolAcademicSetting::firstOrCreate(
            ['school_id' => $schoolId, 'academic_year_id' => $yearId],
            [
                'pass_mark' => 40,
                'ca_weight' => 30,
                'exam_weight' => 70,
                'ca_max' => 30,
                'midterm_max' => 20,
                'exam_max' => 50,
                'grading_scale' => 'waec',
                'grade_bands' => SchoolAcademicSetting::waecBands(),
            ]
        );
    }

    public function gradeFor(float $total, SchoolAcademicSetting $settings): array
    {
        $bands = collect($settings->grade_bands ?: SchoolAcademicSetting::waecBands())
            ->sortByDesc('min');
        foreach ($bands as $band) {
            if ($total >= (float) $band['min']) {
                return [$band['grade'], $band['remark']];
            }
        }
        return ['F9', 'Fail'];
    }

    public function saveScores(array $context, array $rows, int $teacherId): void
    {
        $settings = $this->settings($context['school_id'], $context['academic_year_id']);

        foreach ($rows as $studentId => $marks) {
            $ca = $this->clamp($marks['ca'] ?? null, $settings->ca_max);
            $midterm = $this->clamp($marks['midterm'] ?? null, $settings->midterm_max);
            $exam = $this->clamp($marks['exam'] ?? null, $settings->exam_max);
            $total = round(($ca ?? 0) + ($midterm ?? 0) + ($exam ?? 0), 2);
            [$grade, $remark] = $this->gradeFor($total, $settings);

            $score = AssessmentScore::firstOrNew([
                'academic_term_id' => $context['academic_term_id'],
                'standardLink_id' => $context['standardLink_id'],
                'subject_id' => $context['subject_id'],
                'student_id' => $studentId,
            ]);

            $before = $score->exists ? $score->only(['ca', 'midterm', 'exam', 'total', 'grade']) : [];

            $score->fill($context);
            $score->teacher_id = $teacherId;
            $score->ca = $ca;
            $score->midterm = $midterm;
            $score->exam = $exam;
            $score->total = $total;
            $score->grade = $grade;
            $score->remark = $remark;
            $score->save();

            foreach (['ca', 'midterm', 'exam'] as $field) {
                $old = $before[$field] ?? null;
                $new = $score->$field;
                if ((string) $old !== (string) $new) {
                    AssessmentScoreAudit::create([
                        'assessment_score_id' => $score->id,
                        'changed_by' => $teacherId,
                        'field' => $field,
                        'old_value' => $old,
                        'new_value' => $new,
                    ]);
                }
            }
        }
    }

    public function classGaps(int $termId, int $classId): array
    {
        $subjectIds = Teacherlink::where('standardLink_id', $classId)
            ->pluck('subject_id')
            ->unique()
            ->filter();
        $subjects = Subject::whereIn('id', $subjectIds)->orderBy('name')->get();
        $studentIds = StudentAcademic::where('standardLink_id', $classId)->pluck('user_id');
        $scores = AssessmentScore::where('academic_term_id', $termId)
            ->where('standardLink_id', $classId)
            ->get()
            ->groupBy(fn ($row) => $row->student_id . '-' . $row->subject_id);

        $emptySubjects = [];
        $emptyCount = 0;
        foreach ($subjects as $subject) {
            $missingHere = 0;
            foreach ($studentIds as $studentId) {
                $row = optional($scores->get($studentId . '-' . $subject->id))->first();
                $filled = $row && ($row->ca !== null || $row->midterm !== null || $row->exam !== null);
                if (!$filled) {
                    $missingHere++;
                    $emptyCount++;
                }
            }
            if ($missingHere > 0) {
                $emptySubjects[] = $subject->name;
            }
        }

        return [
            'empty_count' => $emptyCount,
            'empty_subjects' => $emptySubjects,
            'subject_count' => $subjects->count(),
            'student_count' => $studentIds->count(),
        ];
    }

    public function publishClass(int $schoolId, int $termId, int $classId): void
    {
        $gaps = $this->classGaps($termId, $classId);
        if ($gaps['empty_count'] > 0) {
            throw new \RuntimeException(count($gaps['empty_subjects']) . ' subjects empty');
        }

        AssessmentScore::where('academic_term_id', $termId)
            ->where('standardLink_id', $classId)
            ->update(['published' => true]);

        $students = StudentAcademic::where('standardLink_id', $classId)->pluck('user_id');
        $averages = [];
        foreach ($students as $studentId) {
            $avg = AssessmentScore::where('academic_term_id', $termId)
                ->where('standardLink_id', $classId)
                ->where('student_id', $studentId)
                ->avg('total');
            $averages[$studentId] = $avg !== null ? round((float) $avg, 2) : null;
        }

        $ranked = collect($averages)->filter(fn ($v) => $v !== null)->sortDesc()->values();
        $positionMap = [];
        $pos = 1;
        $prev = null;
        $seen = 0;
        foreach ($ranked as $avg) {
            $seen++;
            if ($prev === null || $avg < $prev) {
                $pos = $seen;
                $prev = $avg;
            }
            $positionMap[(string) $avg] = $positionMap[(string) $avg] ?? $pos;
        }

        $settings = $this->settings($schoolId, AssessmentScore::where('standardLink_id', $classId)->value('academic_year_id') ?? 1);
        $size = $students->count();

        foreach ($students as $studentId) {
            $average = $averages[$studentId];
            [$grade] = $average !== null ? $this->gradeFor($average, $settings) : [null];
            TermResult::updateOrCreate(
                [
                    'academic_term_id' => $termId,
                    'standardLink_id' => $classId,
                    'student_id' => $studentId,
                ],
                [
                    'school_id' => $schoolId,
                    'average' => $average,
                    'position' => $average !== null ? ($positionMap[(string) $average] ?? null) : null,
                    'class_size' => $size,
                    'grade' => $grade,
                    'published_at' => now(),
                ]
            );
        }
    }

    public function schoolTermOverview(int $schoolId): array
    {
        $year = SiteHelper::getAcademicYear($schoolId);
        $term = AcademicTerm::where('school_id', $schoolId)
            ->where('academic_year_id', $year->id)
            ->where('is_current', true)
            ->first();

        $rows = [];
        if (!$term) {
            return ['term' => null, 'year' => $year, 'rows' => []];
        }

        $students = User::where('school_id', $schoolId)
            ->where('usergroup_id', 6)
            ->where('status', 'active')
            ->with('studentAcademicLatest.standardLink')
            ->orderBy('name')
            ->get();

        $classIds = $students->map(fn ($s) => optional($s->studentAcademicLatest)->standardLink_id)->filter()->unique();
        $subjectCounts = Teacherlink::whereIn('standardLink_id', $classIds)
            ->selectRaw('standardLink_id, count(distinct subject_id) as n')
            ->groupBy('standardLink_id')
            ->pluck('n', 'standardLink_id');

        $termResults = TermResult::where('academic_term_id', $term->id)->get()->keyBy('student_id');
        $scoreCounts = AssessmentScore::where('academic_term_id', $term->id)
            ->selectRaw('student_id, count(*) as n')
            ->groupBy('student_id')
            ->pluck('n', 'student_id');

        $attendance = Attendance::where('school_id', $schoolId)
            ->where('academic_year_id', $year->id)
            ->get()
            ->groupBy('user_id');

        foreach ($students as $student) {
            $classId = optional($student->studentAcademicLatest)->standardLink_id;
            $expected = (int) ($subjectCounts[$classId] ?? 0);
            $got = (int) ($scoreCounts[$student->id] ?? 0);
            $att = $attendance->get($student->id, collect());
            $present = $att->filter(fn ($a) => in_array($a->mark, ['present', 'late'], true) || ((int) $a->status === 1 && !$a->mark))->count();
            $percent = $att->count() ? round(($present / $att->count()) * 100) : null;
            $tr = $termResults->get($student->id);
            $rows[] = [
                'student' => $student,
                'class' => optional(optional($student->studentAcademicLatest)->standardLink)->StandardSection,
                'average' => $tr->average ?? null,
                'position' => $tr->position ?? null,
                'class_size' => $tr->class_size ?? null,
                'grade' => $tr->grade ?? null,
                'published' => (bool) optional($tr)->published_at,
                'attendance' => $percent,
                'missing' => max(0, $expected - $got),
            ];
        }

        return ['term' => $term, 'year' => $year, 'rows' => $rows];
    }

    public function reportForStudent(User $student): array
    {
        $year = SiteHelper::getAcademicYear($student->school_id);
        $term = AcademicTerm::where('school_id', $student->school_id)
            ->where('academic_year_id', $year->id)
            ->where('is_current', true)
            ->first();
        $classId = optional($student->studentAcademicLatest)->standardLink_id;

        $scores = collect();
        $termResult = null;
        if ($term && $classId) {
            $scores = AssessmentScore::with('subject')
                ->where('academic_term_id', $term->id)
                ->where('standardLink_id', $classId)
                ->where('student_id', $student->id)
                ->where('published', true)
                ->get();
            $termResult = TermResult::where('academic_term_id', $term->id)
                ->where('student_id', $student->id)
                ->first();
        }

        $rows = Attendance::where('user_id', $student->id)->where('academic_year_id', $year->id)->get();
        $totalDays = $rows->count();
        $presentDays = $rows->filter(function ($a) {
            return in_array($a->mark, ['present', 'late'], true) || ((int) $a->status === 1 && !$a->mark);
        })->count();

        return [
            'school' => School::find($student->school_id),
            'student' => $student,
            'term' => $term,
            'year' => $year,
            'className' => optional(optional($student->studentAcademicLatest)->standardLink)->StandardSection,
            'scores' => $scores,
            'termResult' => $termResult,
            'attendancePercent' => $totalDays ? round(($presentDays / $totalDays) * 100) : null,
            'formTeacher' => optional(optional($student->studentAcademicLatest)->standardLink)->teacher,
        ];
    }

    private function clamp($value, $max): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        $n = (float) $value;
        if ($n < 0) {
            return 0;
        }
        if ($max && $n > $max) {
            return (float) $max;
        }
        return $n;
    }
}
