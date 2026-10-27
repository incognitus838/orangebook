<?php

namespace App\Services;

use App\Helpers\SiteHelper;
use App\Models\AcademicTerm;
use App\Models\Attendance;
use App\Models\Homework;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\TermResult;
use App\Models\User;
use Carbon\Carbon;

class SchoolDayService
{
    public function __construct(private ResultsService $results)
    {
    }

    public function teacher(User $teacher): array
    {
        $schoolId = $teacher->school_id;
        $year = SiteHelper::getAcademicYear($schoolId);
        $classIds = SiteHelper::teacherClassIds($schoolId, $teacher->id);
        $formClass = $year ? StandardLink::where([
            ['school_id', $schoolId],
            ['academic_year_id', $year->id],
            ['class_teacher_id', $teacher->id],
        ])->first() : null;

        $classes = StandardLink::with('standard', 'section')
            ->whereIn('id', $classIds)
            ->get();

        $day = $this->pack(
            $schoolId,
            $classes,
            $formClass,
            $classIds,
            url('/teacher/class'),
            url('/teacher/results'),
            url('/teacher/homeworks')
        );
        $day['role'] = 'teacher';
        $day['charts'] = $this->charts($schoolId, $classIds);
        $day['stats'] = $this->stats($schoolId, $classIds, $day);
        return $day;
    }

    public function principal(User $admin): array
    {
        $schoolId = $admin->school_id;
        $year = SiteHelper::getAcademicYear($schoolId);
        $classes = $year
            ? StandardLink::with('standard', 'section')
                ->where('school_id', $schoolId)
                ->where('academic_year_id', $year->id)
                ->get()
            : collect();

        $ids = $classes->pluck('id');
        $day = $this->pack(
            $schoolId,
            $classes,
            null,
            $ids,
            url('/admin/standardlinks'),
            url('/admin/reports'),
            url('/admin/homeworks')
        );
        $day['role'] = 'principal';
        $day['classCount'] = User::where('school_id', $schoolId)->where('usergroup_id', 6)->where('status', 'active')->count();
        $day['charts'] = $this->charts($schoolId, $ids);
        $day['stats'] = $this->stats($schoolId, $ids, $day);
        $day['intakeOpen'] = $year && $year->start_date && \Carbon\Carbon::parse($year->start_date)->gte(now()->subDays(28));
        return $day;
    }

    public function student(User $student): array
    {
        $classId = optional($student->studentAcademicLatest)->standardLink_id;
        $class = $classId ? StandardLink::with('standard', 'section')->find($classId) : null;
        $schoolId = $student->school_id;
        $year = SiteHelper::getAcademicYear($schoolId);
        $today = now()->toDateString();

        $mark = $classId
            ? Attendance::where('user_id', $student->id)
                ->where('standardLink_id', $classId)
                ->whereDate('date', $today)
                ->first()
            : null;

        $absentees = collect();
        if ($mark && in_array($mark->mark, ['absent', 'late', 'excused'], true)) {
            $absentees = collect([(object) [
                'name' => $student->name,
                'class' => $class?->StandardSection,
                'mark' => $mark->mark,
                'remarks' => $mark->remarks,
            ]]);
        }

        $term = $year ? AcademicTerm::where('school_id', $schoolId)
            ->where('academic_year_id', $year->id)
            ->where('is_current', true)
            ->first() : null;

        $unpublished = collect();
        if ($class && $term) {
            $published = TermResult::where('academic_term_id', $term->id)
                ->where('standardLink_id', $class->id)
                ->where('student_id', $student->id)
                ->whereNotNull('published_at')
                ->exists();
            if (!$published) {
                $unpublished->push((object) [
                    'class' => $class->StandardSection,
                    'class_id' => $class->id,
                    'empty_subjects' => [],
                    'label' => 'This term is not published yet',
                ]);
            }
        }

        $homeworkDue = $classId
            ? Homework::with('subject')
                ->where('standardLink_id', $classId)
                ->where('status', 'publish')
                ->whereDate('submission_date', '>=', $today)
                ->orderBy('submission_date')
                ->take(8)
                ->get()
            : collect();

        $ids = $classId ? collect([$classId]) : collect();
        $day = [
            'role' => 'student',
            'classToday' => $class,
            'classCount' => $class ? StudentAcademic::where('standardLink_id', $class->id)->count() : 0,
            'absentees' => $absentees,
            'unpublished' => $unpublished,
            'homeworkDue' => $homeworkDue,
            'classUrl' => url('/student/results'),
            'resultsUrl' => url('/student/results'),
            'homeworkUrl' => url('/student/homeworks'),
            'todayMark' => $mark,
        ];
        $day['charts'] = $this->charts($schoolId, $ids);
        $day['stats'] = $this->stats($schoolId, $ids, $day);
        return $day;
    }

    private function stats(int $schoolId, $classIds, array $day): array
    {
        $ids = collect($classIds)->filter();
        if (($day['role'] ?? '') === 'teacher' && $ids->isNotEmpty()) {
            $studentIds = StudentAcademic::whereIn('standardLink_id', $ids)->pluck('user_id');
            $students = User::whereIn('id', $studentIds)->where('status', 'active');
        } else {
            $students = User::where('school_id', $schoolId)->where('usergroup_id', 6)->where('status', 'active');
        }
        $teachers = User::where('school_id', $schoolId)->where('usergroup_id', 5)->where('status', 'active');
        $parents = User::where('school_id', $schoolId)->where('usergroup_id', 7)->where('status', 'active');

        $today = $day['charts']['today'] ?? ['present' => 0, 'absent' => 0, 'late' => 0, 'excused' => 0, 'marked' => 0];
        $presentPct = $today['marked'] > 0
            ? round((($today['present'] + $today['late']) / $today['marked']) * 100)
            : null;

        return [
            'students' => (clone $students)->count(),
            'teachers' => $teachers->count(),
            'parents' => $parents->count(),
            'boys' => (clone $students)->ByGender('male')->count(),
            'girls' => (clone $students)->ByGender('female')->count(),
            'present_pct' => $presentPct,
            'unpublished' => $day['unpublished']->count(),
            'absent_today' => $day['absentees']->count(),
        ];
    }

    private function charts(int $schoolId, $classIds): array
    {
        $ids = collect($classIds)->filter()->values();
        $today = now()->toDateString();
        $weekDays = collect();
        $cursor = now()->startOfWeek(Carbon::MONDAY);
        for ($i = 0; $i < 5; $i++) {
            $weekDays->push($cursor->copy()->addDays($i)->toDateString());
        }

        $query = Attendance::query()->where('school_id', $schoolId);
        if ($ids->isNotEmpty()) {
            $query->whereIn('standardLink_id', $ids);
        }
        $rows = (clone $query)
            ->where(function ($q) use ($weekDays) {
                foreach ($weekDays as $d) {
                    $q->orWhereDate('date', $d);
                }
            })
            ->get();

        $byDay = $rows->groupBy(fn ($a) => optional($a->date)->format('Y-m-d'));
        $week = $weekDays->map(function ($d) use ($byDay) {
            $set = $byDay->get($d, collect());
            $present = $set->filter(fn ($a) => in_array($a->mark, ['present', 'late'], true) || ((int) $a->status === 1 && !$a->mark))->count();
            $absent = $set->filter(fn ($a) => in_array($a->mark, ['absent', 'excused'], true) || ((int) $a->status === 0 && !$a->mark))->count();
            return [
                'date' => $d,
                'label' => Carbon::parse($d)->format('D'),
                'present' => $present,
                'absent' => $absent,
            ];
        });

        $todayRows = $byDay->get($today, collect());
        $todayCounts = [
            'present' => $todayRows->where('mark', 'present')->count(),
            'late' => $todayRows->where('mark', 'late')->count(),
            'absent' => $todayRows->where('mark', 'absent')->count(),
            'excused' => $todayRows->where('mark', 'excused')->count(),
        ];
        $todayCounts['marked'] = array_sum($todayCounts);

        $classes = StandardLink::with('standard', 'section')->whereIn('id', $ids)->get();
        $counts = $ids->isEmpty()
            ? collect()
            : StudentAcademic::whereIn('standardLink_id', $ids)
                ->selectRaw('standardLink_id, count(*) as n')
                ->groupBy('standardLink_id')
                ->pluck('n', 'standardLink_id');
        $strength = $classes->map(function ($class) use ($counts) {
            return [
                'label' => $class->StandardSection,
                'count' => (int) ($counts[$class->id] ?? 0),
            ];
        })->filter(fn ($row) => $row['count'] > 0)->values();

        return [
            'week' => $week,
            'week_total' => (int) $week->sum(fn ($d) => $d['present'] + $d['absent']),
            'today' => $todayCounts,
            'strength' => $strength,
        ];
    }

    private function pack(int $schoolId, $classes, $formClass, $classIds, string $classUrl, string $resultsUrl, string $homeworkUrl): array
    {
        $year = SiteHelper::getAcademicYear($schoolId);
        $today = now()->toDateString();
        $ids = collect($classIds)->filter()->values();

        $absentees = collect();
        if ($ids->isNotEmpty()) {
            $rows = Attendance::with('user')
                ->whereIn('standardLink_id', $ids)
                ->whereDate('date', $today)
                ->whereIn('mark', ['absent', 'late', 'excused'])
                ->get();
            $classMap = $classes->keyBy('id');
            $absentees = $rows->map(function ($row) use ($classMap) {
                return (object) [
                    'name' => optional($row->user)->name ?? 'Student',
                    'class' => optional($classMap->get($row->standardLink_id))->StandardSection,
                    'mark' => $row->mark,
                    'remarks' => $row->remarks,
                ];
            });
        }

        $term = $year ? AcademicTerm::where('school_id', $schoolId)
            ->where('academic_year_id', $year->id)
            ->where('is_current', true)
            ->first() : null;

        $unpublished = collect();
        if ($term) {
            foreach ($classes as $class) {
                $published = TermResult::where('academic_term_id', $term->id)
                    ->where('standardLink_id', $class->id)
                    ->whereNotNull('published_at')
                    ->exists();
                $gaps = $this->results->classGaps($term->id, $class->id);
                if (!$published || $gaps['empty_count'] > 0) {
                    $unpublished->push((object) [
                        'class' => $class->StandardSection,
                        'class_id' => $class->id,
                        'empty_subjects' => $gaps['empty_subjects'],
                        'label' => $published
                            ? (count($gaps['empty_subjects']) . ' subjects empty')
                            : 'Not published',
                    ]);
                }
            }
        }

        $homeworkDue = $ids->isEmpty()
            ? collect()
            : Homework::with('subject', 'standardLink')
                ->whereIn('standardLink_id', $ids)
                ->where('status', 'publish')
                ->whereDate('submission_date', '>=', $today)
                ->orderBy('submission_date')
                ->take(8)
                ->get();

        return [
            'classToday' => $formClass,
            'classCount' => $formClass ? StudentAcademic::where('standardLink_id', $formClass->id)->count() : $classes->count(),
            'absentees' => $absentees,
            'unpublished' => $unpublished,
            'homeworkDue' => $homeworkDue,
            'classUrl' => $formClass ? rtrim($classUrl, '/') . '/' . $formClass->id : $classUrl,
            'resultsUrl' => $resultsUrl,
            'homeworkUrl' => $homeworkUrl,
            'todayMark' => null,
        ];
    }
}
