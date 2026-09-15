<?php

namespace App\Services;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Plan;
use App\Models\School;
use App\Models\SchoolAcademicSetting;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SchoolOnboardingService
{
    public function createSchool(array $data, ?Plan $plan = null): User
    {
        $plan = $plan ?: Plan::where('name', 'trial')->first() ?: Plan::first();

        $school = School::create([
            'name' => $data['school_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'slug' => $data['slug'] ?? Str::slug($data['school_name']),
            'status' => 1,
            'primary_color' => $data['primary_color'] ?? '#1B2A4A',
            'address' => $data['address'] ?? 'Nigeria',
        ]);
        $school->join_code = SchoolJoinService::makeJoinCode($school);
        $school->save();

        $admin = User::create([
            'school_id' => $school->id,
            'usergroup_id' => User::SCHOOLADMIN_USERGROUP_ID,
            'name' => $data['admin_name'],
            'email' => $data['email'],
            'mobile_no' => $data['phone'] ?? ('0803' . random_int(1000000, 9999999)),
            'password' => Hash::make($data['password']),
            'status' => 'active',
            'email_verified' => true,
            'email_verified_at' => now(),
        ]);

        Userprofile::create([
            'user_id' => $admin->id,
            'school_id' => $school->id,
            'usergroup_id' => User::SCHOOLADMIN_USERGROUP_ID,
            'firstname' => explode(' ', $data['admin_name'])[0],
            'lastname' => explode(' ', $data['admin_name'] . ' Admin')[1] ?? 'Admin',
            'address' => $data['address'] ?? 'Nigeria',
        ]);

        $days = (int) ($plan->cycle ?: 14);
        if ($plan->name === 'trial') {
            $days = 14;
        }

        Subscription::create([
            'school_id' => $school->id,
            'user_id' => $admin->id,
            'plan_id' => $plan->id,
            'status' => $plan->name === 'trial' ? 'trial' : 'active',
            'end_date' => now()->addDays($days)->toDateString(),
        ]);

        $this->seedNigerianStructure($school);

        return $admin;
    }

    public function seedNigerianStructure(School $school): void
    {
        $year = AcademicYear::firstOrCreate(
            ['school_id' => $school->id, 'status' => 1],
            [
                'name' => '2025/2026',
                'description' => 'Current Nigerian academic session',
                'start_date' => '2025-09-08',
                'end_date' => '2026-07-17',
            ]
        );

        if (!AcademicTerm::where('school_id', $school->id)->exists()) {
            foreach ([
                ['First Term', 'first', 1, true],
                ['Second Term', 'second', 2, false],
                ['Third Term', 'third', 3, false],
            ] as $term) {
                AcademicTerm::create([
                    'school_id' => $school->id,
                    'academic_year_id' => $year->id,
                    'name' => $term[0],
                    'slug' => $term[1],
                    'term_order' => $term[2],
                    'is_current' => $term[3],
                ]);
            }
        }

        SchoolAcademicSetting::firstOrCreate(
            ['school_id' => $school->id, 'academic_year_id' => $year->id],
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

        if (!Standard::where('school_id', $school->id)->exists()) {
            $order = 1;
            foreach (['JSS1', 'JSS2', 'JSS3', 'SS1', 'SS2', 'SS3'] as $name) {
                Standard::create([
                    'school_id' => $school->id,
                    'name' => $name,
                    'slug' => Str::slug($name) . '-' . $school->id,
                    'order' => str_pad((string) $order++, 2, '0', STR_PAD_LEFT),
                    'status' => 1,
                ]);
            }
        }

        if (!Section::where('school_id', $school->id)->exists()) {
            foreach (['A', 'B', 'Science', 'Arts', 'Commercial'] as $name) {
                Section::create([
                    'school_id' => $school->id,
                    'name' => $name,
                    'status' => 1,
                ]);
            }
        }

        $standards = Standard::where('school_id', $school->id)->get();
        $sections = Section::where('school_id', $school->id)->get()->keyBy('name');
        $admin = User::where('school_id', $school->id)->where('usergroup_id', 3)->first();

        foreach ($standards as $standard) {
            $isJss = str_starts_with($standard->name, 'JSS');
            $arms = $isJss ? ['A', 'B'] : ['Science', 'Arts', 'Commercial'];
            foreach ($arms as $arm) {
                $section = $sections->get($arm);
                if (!$section) {
                    continue;
                }
                StandardLink::firstOrCreate([
                    'school_id' => $school->id,
                    'academic_year_id' => $year->id,
                    'standard_id' => $standard->id,
                    'section_id' => $section->id,
                ], [
                    'class_teacher_id' => $admin?->id ?: 1,
                    'status' => 1,
                ]);
            }
        }
    }
}
