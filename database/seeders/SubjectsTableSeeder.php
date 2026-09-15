<?php

namespace Database\Seeders;

use DB;
use Illuminate\Database\Seeder;
use App\Models\AcademicYear;
use App\Models\Standard;
use App\Models\Section;
use App\Models\School;

class SubjectsTableSeeder extends Seeder
{
    public function run()
    {
        $jss = ['English', 'Mathematics', 'Basic Science', 'Basic Technology', 'Social Studies', 'Civic Education', 'Computer Studies', 'PHE'];
        $ssScience = ['English', 'Mathematics', 'Physics', 'Chemistry', 'Biology', 'Civic Education'];
        $ssArts = ['English', 'Mathematics', 'Literature in English', 'Government', 'CRS', 'Civic Education'];
        $ssCommercial = ['English', 'Mathematics', 'Economics', 'Commerce', 'Financial Accounting', 'Civic Education'];

        $schools = School::where('status', 1)->get();
        foreach ($schools as $school) {
            $standards = Standard::where('school_id', $school->id)->get();
            $sections = Section::where('school_id', $school->id)->get();
            $academic_year = AcademicYear::where([['school_id', $school->id], ['status', 1]])->first();

            foreach ($standards as $standard) {
                $isJss = str_starts_with($standard->name, 'JSS');
                foreach ($sections as $section) {
                    if ($isJss && !in_array($section->name, ['A', 'B'], true)) {
                        continue;
                    }
                    if (!$isJss && !in_array($section->name, ['Science', 'Arts', 'Commercial'], true)) {
                        continue;
                    }

                    $subjects = $jss;
                    if (!$isJss) {
                        $subjects = match ($section->name) {
                            'Arts' => $ssArts,
                            'Commercial' => $ssCommercial,
                            default => $ssScience,
                        };
                    }

                    foreach ($subjects as $subject) {
                        DB::table('subjects')->insert([
                            'school_id' => $standard->school_id,
                            'academic_year_id' => $academic_year->id,
                            'standard_id' => $standard->id,
                            'section_id' => $section->id,
                            'name' => $subject,
                            'code' => strtoupper(substr($subject, 0, 3)) . '-' . $section->name . '-' . $standard->name,
                            'type' => 'core',
                            'status' => '1',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }
    }
}
