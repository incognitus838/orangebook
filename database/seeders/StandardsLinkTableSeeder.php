<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Helpers\SiteHelper;
use App\Models\StandardLink;
use App\Models\RoleUser;
use App\Models\Standard;
use App\Models\Section;
use App\Models\School;
use App\Models\User;

class StandardsLinkTableSeeder extends Seeder
{
    public function run()
    {
        if (env('APP_ENV') != 'local' && env('APP_ENV') != 'development') {
            return;
        }

        $schools = School::where('status', 1)->get();
        foreach ($schools as $school) {
            $standards = Standard::where('school_id', $school->id)->orderBy('order')->get();
            $sections = Section::where('school_id', $school->id)->get()->keyBy('name');
            $teachers = User::where([['school_id', $school->id], ['usergroup_id', 5]])->pluck('id')->values();
            $academic_year = SiteHelper::getAcademicyear($school->id);
            $teacherIndex = 0;

            foreach ($standards as $standard) {
                $isJss = str_starts_with($standard->name, 'JSS');
                $armNames = $isJss ? ['A', 'B'] : ['Science', 'Arts', 'Commercial'];

                foreach ($armNames as $armName) {
                    $section = $sections->get($armName);
                    if (!$section) {
                        continue;
                    }

                    $teacherId = $teachers[$teacherIndex % max($teachers->count(), 1)] ?? $teachers->first();
                    $teacherIndex++;

                    $link = StandardLink::factory()->create([
                        'school_id' => $standard->school_id,
                        'academic_year_id' => $academic_year->id,
                        'standard_id' => $standard->id,
                        'section_id' => $section->id,
                        'class_teacher_id' => $teacherId,
                    ]);

                    RoleUser::factory()->create([
                        'user_id' => $teacherId,
                        'role_id' => 4,
                    ]);
                }
            }
        }
    }
}
