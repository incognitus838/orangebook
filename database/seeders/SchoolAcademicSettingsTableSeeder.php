<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\SchoolAcademicSetting;
use Illuminate\Database\Seeder;

class SchoolAcademicSettingsTableSeeder extends Seeder
{
    public function run(): void
    {
        $schools = School::where('status', 1)->get();

        foreach ($schools as $school) {
            $year = AcademicYear::where('school_id', $school->id)->where('status', 1)->first();
            if (!$year) {
                continue;
            }

            SchoolAcademicSetting::create([
                'school_id' => $school->id,
                'academic_year_id' => $year->id,
                'pass_mark' => 40,
                'ca_weight' => 30,
                'exam_weight' => 70,
                'grading_scale' => 'waec',
                'grade_bands' => SchoolAcademicSetting::waecBands(),
            ]);
        }
    }
}
