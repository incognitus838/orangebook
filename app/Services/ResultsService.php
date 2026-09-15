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

    public function publishClass(int $schoolId, int $termId, int $classId): void
    {
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
