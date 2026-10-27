<?php

namespace Database\Seeders;

use App\Models\StudentAcademic;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\SchoolOnboardingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoKindsSeeder extends Seeder
{
    public function run(): void
    {
        $onboard = app(SchoolOnboardingService::class);

        $ikeja = \App\Models\School::find(1);
        if ($ikeja && !$ikeja->kind) {
            $ikeja->kind = 'school';
            $ikeja->save();
        }

        $this->ensureOrg($onboard, [
            'kind' => 'ngo',
            'school_name' => 'Hope Rising Programme',
            'admin_name' => 'Halima Yusuf',
            'email' => 'hope@orangebook.ng',
            'phone' => '08031112233',
            'password' => 'password',
            'address' => 'Surulere, Lagos',
            'staff_name' => 'Tolu Adewale',
            'staff_email' => 'tolu.hope@orangebook.ng',
            'learners' => [
                ['Sade Bello', 'sade.hope@orangebook.ng'],
                ['Ibrahim Musa', 'ibrahim.hope@orangebook.ng'],
            ],
        ]);

        $this->ensureOrg($onboard, [
            'kind' => 'training',
            'school_name' => 'Yaba Skills Studio',
            'admin_name' => 'Chika Obi',
            'email' => 'yaba@orangebook.ng',
            'phone' => '08034445566',
            'password' => 'password',
            'address' => 'Yaba, Lagos',
            'staff_name' => 'Nneka Eze',
            'staff_email' => 'nneka.yaba@orangebook.ng',
            'learners' => [
                ['Kunle Adisa', 'kunle.yaba@orangebook.ng'],
                ['Blessing Okoro', 'blessing.yaba@orangebook.ng'],
            ],
        ]);
    }

    private function ensureOrg(SchoolOnboardingService $onboard, array $data): void
    {
        if (User::where('email', $data['email'])->exists()) {
            return;
        }
        $admin = $onboard->createSchool($data);
        $school = $admin->school;
        $year = \App\Helpers\SiteHelper::getAcademicYear($school->id);
        $class = \App\Models\StandardLink::where('school_id', $school->id)->first();

        $staff = User::create([
            'school_id' => $school->id,
            'usergroup_id' => 5,
            'name' => $data['staff_name'],
            'email' => $data['staff_email'],
            'mobile_no' => '0803'.random_int(1000000, 9999999),
            'password' => Hash::make('password'),
            'status' => 'active',
            'email_verified' => true,
            'email_verified_at' => now(),
        ]);
        Userprofile::create([
            'school_id' => $school->id,
            'user_id' => $staff->id,
            'usergroup_id' => 5,
            'firstname' => explode(' ', $data['staff_name'])[0],
            'lastname' => explode(' ', $data['staff_name'].' Staff')[1] ?? 'Staff',
            'status' => 'active',
        ]);
        TeacherProfile::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'user_id' => $staff->id,
            'status' => 1,
            'designation' => 'teacher',
        ]);
        if ($class && !$class->class_teacher_id) {
            $class->class_teacher_id = $staff->id;
            $class->save();
        } elseif ($class) {
            $class->class_teacher_id = $staff->id;
            $class->save();
        }

        foreach ($data['learners'] as $row) {
            [$name, $email] = $row;
            $learner = User::create([
                'school_id' => $school->id,
                'usergroup_id' => 6,
                'name' => $name,
                'email' => $email,
                'mobile_no' => '0805'.random_int(1000000, 9999999),
                'password' => Hash::make('password'),
                'status' => 'active',
                'email_verified' => true,
                'email_verified_at' => now(),
            ]);
            Userprofile::create([
                'school_id' => $school->id,
                'user_id' => $learner->id,
                'usergroup_id' => 6,
                'firstname' => explode(' ', $name)[0],
                'lastname' => explode(' ', $name.' Learner')[1] ?? '',
                'status' => 'active',
            ]);
            if ($class && $year) {
                StudentAcademic::create([
                    'school_id' => $school->id,
                    'academic_year_id' => $year->id,
                    'user_id' => $learner->id,
                    'standardLink_id' => $class->id,
                    'roll_number' => (string) random_int(1000, 9999),
                ]);
            }
        }
    }
}
