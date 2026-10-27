<?php

namespace Tests\Support;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\User;
use App\Models\Usergroup;
use App\Models\Userprofile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class Hall
{
    public static function open(array $schoolAttrs = []): array
    {
        foreach ([
            User::SITEADMIN_USERGROUP_ID => 'Site Admin',
            User::SCHOOLADMIN_USERGROUP_ID => 'School Admin',
            User::TEACHER_USERGROUP_ID => 'Teacher',
            User::STUDENT_USERGROUP_ID => 'Student',
            User::PARENT_USERGROUP_ID => 'Parent',
        ] as $id => $name) {
            DB::table('usergroups')->insertOrIgnore(['id' => $id, 'name' => $name]);
        }

        $school = School::factory()->create(array_merge([
            'kind' => 'school',
            'status' => 1,
            'join_code' => 'TEST-'.strtoupper(substr(md5(uniqid()), 0, 4)),
        ], $schoolAttrs));

        AcademicYear::where('school_id', $school->id)->update(['status' => 0]);
        $year = AcademicYear::create([
            'school_id' => $school->id,
            'name' => date('Y').'/'.(date('Y') + 1),
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'status' => 1,
        ]);
        Cache::flush();

        $standard = Standard::create([
            'school_id' => $school->id,
            'name' => 'JSS1',
            'slug' => 'jss1-'.$school->id,
            'order' => '01',
            'status' => 1,
            'is_entry' => true,
        ]);
        $section = Section::create([
            'school_id' => $school->id,
            'name' => 'A',
            'status' => 1,
        ]);
        $admin = User::factory()->schoolAdmin()->create([
            'school_id' => $school->id,
            'email' => 'admin-'.$school->id.'@school.test',
            'password' => Hash::make('secret123'),
            'status' => 'active',
        ]);
        Userprofile::create([
            'school_id' => $school->id,
            'user_id' => $admin->id,
            'usergroup_id' => User::SCHOOLADMIN_USERGROUP_ID,
            'firstname' => 'Ada',
            'lastname' => 'Principal',
            'status' => 'active',
        ]);
        $class = StandardLink::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'class_teacher_id' => $admin->id,
            'status' => 1,
        ]);

        return compact('school', 'admin', 'year', 'standard', 'section', 'class');
    }
}
