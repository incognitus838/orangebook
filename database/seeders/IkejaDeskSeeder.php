<?php

namespace Database\Seeders;

use App\Helpers\SiteHelper;
use App\Models\School;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\StudentParentLink;
use App\Models\User;
use App\Models\Userprofile;
use App\Support\AdmissionNumber;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class IkejaDeskSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::where('slug', 'ikeja-secondary')->first() ?: School::find(1);
        if (! $school) {
            return;
        }
        $year = SiteHelper::getAcademicYear($school->id);
        $class = $year
            ? StandardLink::with('standard', 'section')
                ->where('school_id', $school->id)
                ->where('academic_year_id', $year->id)
                ->whereHas('standard', fn ($q) => $q->where('name', 'JSS2'))
                ->whereHas('section', fn ($q) => $q->where('name', 'A'))
                ->first()
            : null;
        if (! $class) {
            $class = StandardLink::where('school_id', $school->id)->orderBy('id')->first();
        }

        $teacher = $this->person($school, User::TEACHER_USERGROUP_ID, 'teacher@orangebook.ng', 'Adaeze Okonkwo');
        if ($class && ! $class->class_teacher_id) {
            $class->class_teacher_id = $teacher->id;
            $class->save();
        }

        $student = $this->person($school, User::STUDENT_USERGROUP_ID, 'student@orangebook.ng', 'Amara Eze');
        if ($class && $year) {
            StudentAcademic::firstOrCreate(
                [
                    'school_id' => $school->id,
                    'academic_year_id' => $year->id,
                    'user_id' => $student->id,
                    'standardLink_id' => $class->id,
                ],
                ['roll_number' => $student->registration_number]
            );
        }

        $parent = $this->person($school, User::PARENT_USERGROUP_ID, 'parent@orangebook.ng', 'Funke Abdullahi');
        StudentParentLink::firstOrCreate(
            [
                'school_id' => $school->id,
                'parent_id' => $parent->id,
                'student_id' => $student->id,
            ],
            ['status' => 1]
        );
    }

    private function person(School $school, int $group, string $email, string $name): User
    {
        $parts = preg_split('/\s+/', $name, 2);
        $user = User::where('email', $email)->first();
        if (! $user) {
            $admission = $group === User::STUDENT_USERGROUP_ID ? AdmissionNumber::next($school) : null;
            $user = User::create([
                'school_id' => $school->id,
                'usergroup_id' => $group,
                'name' => $this->uniqueName($name),
                'email' => $email,
                'password' => Hash::make('password'),
                'status' => 'active',
                'registration_number' => $admission,
                'email_verified' => true,
                'email_verified_at' => now(),
            ]);
        } else {
            $user->school_id = $school->id;
            $user->usergroup_id = $group;
            $user->name = $this->uniqueName($name, $user->id);
            $user->status = 'active';
            $user->password = Hash::make('password');
            $user->email_verified = true;
            $user->email_verified_at = $user->email_verified_at ?: now();
            if ($group === User::STUDENT_USERGROUP_ID && ! $user->registration_number) {
                $user->registration_number = AdmissionNumber::next($school);
            }
            $user->save();
        }

        Userprofile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'school_id' => $school->id,
                'usergroup_id' => $group,
                'firstname' => $parts[0],
                'lastname' => $parts[1] ?? '',
                'status' => 'active',
                'registration_number' => $user->registration_number,
            ]
        );

        return $user->fresh();
    }

    private function uniqueName(string $name, ?int $exceptId = null): string
    {
        $candidate = $name;
        $n = 2;
        while (User::where('name', $candidate)->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))->exists()) {
            $candidate = $name.' '.$n;
            $n++;
        }

        return $candidate;
    }
}
