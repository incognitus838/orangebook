<?php

namespace Database\Seeders;

use App\Helpers\SiteHelper;
use App\Models\AcademicTerm;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\Teacherlink;
use App\Services\ResultsService;
use Illuminate\Database\Seeder;

class ResultsSeeder extends Seeder
{
    public function run(): void
    {
        $schoolId = 1;
        $year = SiteHelper::getAcademicYear($schoolId);
        $term = AcademicTerm::where('school_id', $schoolId)->where('is_current', true)->first();
        $jss2 = Standard::where('school_id', $schoolId)->where('name', 'JSS2')->first();
        if (!$year || !$term || !$jss2) {
            return;
        }

        $class = StandardLink::where('school_id', $schoolId)
            ->where('academic_year_id', $year->id)
            ->where('standard_id', $jss2->id)
            ->whereHas('section', fn ($q) => $q->where('name', 'A'))
            ->first();
        if (!$class) {
            return;
        }

        $students = StudentAcademic::where('standardLink_id', $class->id)->pluck('user_id');
        $subjects = Subject::where('school_id', $schoolId)
            ->where('academic_year_id', $year->id)
            ->where('standard_id', $jss2->id)
            ->where('section_id', $class->section_id)
            ->get();

        $service = app(ResultsService::class);
        $bases = [78, 64, 71, 55, 88, 42, 69, 81, 60, 73];

        foreach ($subjects as $subject) {
            $teacherId = Teacherlink::where('standardLink_id', $class->id)
                ->where('subject_id', $subject->id)
                ->value('teacher_id') ?: $class->class_teacher_id;
            $rows = [];
            foreach ($students->values() as $i => $studentId) {
                $base = $bases[$i % count($bases)] + (crc32($subject->name) % 7) - 3;
                $base = max(32, min(95, $base));
                $rows[$studentId] = [
                    'ca' => round($base * 0.30, 1),
                    'midterm' => round($base * 0.20, 1),
                    'exam' => round($base * 0.50, 1),
                ];
            }
            $service->saveScores([
                'school_id' => $schoolId,
                'academic_year_id' => $year->id,
                'academic_term_id' => $term->id,
                'standardLink_id' => $class->id,
                'subject_id' => $subject->id,
            ], $rows, $teacherId);
        }

        $service->publishClass($schoolId, $term->id, $class->id);
    }
}
