<?php

namespace Database\Seeders;

use App\Helpers\SiteHelper;
use App\Models\Homework;
use App\Models\RoleUser;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\Subject;
use App\Models\Teacherlink;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Models\Userprofile;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CrestTeacherSeeder extends Seeder
{
    public function run(): void
    {
        $schoolId = 1;
        $year = SiteHelper::getAcademicYear($schoolId);
        if (!$year) {
            return;
        }

        $jss2 = Standard::where('school_id', $schoolId)->where('name', 'JSS2')->first();
        if (!$jss2) {
            return;
        }

        $class = StandardLink::where('school_id', $schoolId)
            ->where('academic_year_id', $year->id)
            ->where('standard_id', $jss2->id)
            ->whereHas('section', function ($q) {
                $q->where('name', 'A');
            })
            ->first();

        if (!$class) {
            return;
        }

        $teacher = User::updateOrCreate(
            ['email' => 'teacher@crest.ng'],
            [
                'school_id' => $schoolId,
                'usergroup_id' => 5,
                'name' => 'Adaeze Okonkwo',
                'mobile_no' => '08035550101',
                'password' => Hash::make('password'),
                'status' => 'active',
                'email_verified' => true,
                'email_verified_at' => now(),
            ]
        );

        Userprofile::updateOrCreate(
            ['user_id' => $teacher->id],
            [
                'school_id' => $schoolId,
                'usergroup_id' => 5,
                'firstname' => 'Adaeze',
                'lastname' => 'Okonkwo',
                'profession' => 'teacher',
                'address' => 'Ikeja, Lagos, Nigeria',
                'date_of_birth' => Carbon::now()->subYears(34)->toDateString(),
            ]
        );

        TeacherProfile::updateOrCreate(
            ['user_id' => $teacher->id, 'academic_year_id' => $year->id],
            [
                'school_id' => $schoolId,
                'status' => 1,
                'designation' => 'teacher',
                'specialization' => 'English',
            ]
        );

        RoleUser::firstOrCreate([
            'user_id' => $teacher->id,
            'role_id' => 4,
        ]);

        $class->class_teacher_id = $teacher->id;
        $class->save();

        $english = Subject::where('school_id', $schoolId)
            ->where('academic_year_id', $year->id)
            ->where('standard_id', $jss2->id)
            ->where('section_id', $class->section_id)
            ->where('name', 'English')
            ->first();

        if ($english) {
            Teacherlink::updateOrCreate(
                [
                    'standardLink_id' => $class->id,
                    'subject_id' => $english->id,
                ],
                [
                    'school_id' => $schoolId,
                    'academic_year_id' => $year->id,
                    'teacher_id' => $teacher->id,
                    'status' => 1,
                ]
            );
        }

        Homework::firstOrCreate(
            [
                'school_id' => $schoolId,
                'teacher_id' => $teacher->id,
                'standardLink_id' => $class->id,
                'description' => 'Read chapter 4 of New Oxford English and answer questions 1–8.',
            ],
            [
                'academic_year_id' => $year->id,
                'subject_id' => $english?->id,
                'date' => now()->toDateString(),
                'submission_date' => now()->addDays(3)->toDateString(),
                'status' => 'publish',
                'created_by' => $teacher->id,
                'updated_by' => $teacher->id,
            ]
        );
    }
}
