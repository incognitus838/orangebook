<?php

namespace App\Services;

use App\Helpers\SiteHelper;
use App\Models\ParentProfile;
use App\Models\School;
use App\Models\StaffInvite;
use App\Models\StudentParentLink;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SchoolJoinService
{
    public static function makeJoinCode(School $school): string
    {
        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $school->name), 0, 5));
        if ($prefix === '') {
            $prefix = 'SCHOOL';
        }
        do {
            $code = $prefix . '-' . strtoupper(Str::random(4));
        } while (School::where('join_code', $code)->exists());
        return $code;
    }

    public function findSchool(string $joinCode): ?School
    {
        return School::where('join_code', strtoupper(trim($joinCode)))->where('status', 1)->first();
    }

    public function findStudents(string $joinCode, string $childName, ?string $admission = null)
    {
        $school = $this->findSchool($joinCode);
        if (!$school) {
            return collect();
        }
        $name = strtolower(trim($childName));
        $students = User::where('school_id', $school->id)
            ->where('usergroup_id', 6)
            ->where('status', 'active')
            ->whereRaw('LOWER(name) = ?', [$name])
            ->with('studentAcademicLatest', 'userprofile')
            ->get();
        $admission = trim((string) $admission);
        if ($admission !== '' && $students->count() > 1) {
            return $students->filter(function ($u) use ($admission) {
                return (string) $u->registration_number === $admission
                    || (string) optional($u->userprofile)->registration_number === $admission
                    || (string) optional($u->studentAcademicLatest)->roll_number === $admission;
            })->values();
        }
        return $students;
    }

    public function findStudent(string $joinCode, string $childName, ?string $admission = null): ?User
    {
        $matches = $this->findStudents($joinCode, $childName, $admission);
        return $matches->count() === 1 ? $matches->first() : null;
    }

    public function registerParent(User $student, array $data): User
    {
        $existing = User::where('email', $data['email'])->first();
        if ($existing && (int) $existing->school_id !== (int) $student->school_id) {
            abort(422, 'That email is already used at another school.');
        }

        if ($existing && $existing->isParent()) {
            $parent = $existing;
        } else {
            $parent = User::create([
                'school_id' => $student->school_id,
                'usergroup_id' => 7,
                'name' => $data['name'],
                'email' => $data['email'],
                'mobile_no' => $data['phone'] ?? null,
                'password' => Hash::make($data['password']),
                'status' => 'active',
                'email_verified' => true,
                'email_verified_at' => now(),
            ]);
            Userprofile::create([
                'school_id' => $student->school_id,
                'user_id' => $parent->id,
                'usergroup_id' => 7,
                'firstname' => explode(' ', $data['name'])[0],
                'lastname' => explode(' ', $data['name'] . ' Parent')[1] ?? 'Parent',
            ]);
            ParentProfile::create([
                'school_id' => $student->school_id,
                'user_id' => $parent->id,
                'relation' => 'guardian',
            ]);
        }

        StudentParentLink::firstOrCreate(
            ['parent_id' => $parent->id, 'student_id' => $student->id],
            ['school_id' => $student->school_id, 'status' => 1]
        );

        return $parent;
    }

    public function claimStudent(string $joinCode, string $childName, string $admission, string $password): User
    {
        $matches = $this->findStudents($joinCode, $childName, $admission);
        if ($matches->count() !== 1) {
            throw new \RuntimeException('Those details do not match a student in this school.');
        }
        $student = $matches->first();
        $ok = (string) $student->registration_number === trim($admission)
            || (string) optional($student->userprofile)->registration_number === trim($admission)
            || (string) optional($student->studentAcademicLatest)->roll_number === trim($admission);
        if (!$ok) {
            throw new \RuntimeException('Those details do not match a student in this school.');
        }
        $student->password = Hash::make($password);
        $student->save();
        return $student;
    }

    public function inviteStaff(User $admin, array $data): StaffInvite
    {
        $invite = StaffInvite::create([
            'school_id' => $admin->school_id,
            'invited_by' => $admin->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'usergroup_id' => $data['usergroup_id'] ?? 5,
            'class_id' => $data['class_id'] ?? null,
            'subjects' => $data['subjects'] ?? null,
            'token' => Str::random(48),
            'expires_at' => now()->addDays(7),
        ]);

        $url = url('/staff/join/' . $invite->token);
        \Log::info("Staff invite for {$invite->email}\n{$url}");

        return $invite;
    }

    public function acceptStaff(StaffInvite $invite, string $password): User
    {
        if (!$invite->isOpen()) {
            abort(422, 'This invite is no longer valid.');
        }
        if (User::where('email', $invite->email)->exists()) {
            abort(422, 'That email already has an Orangebook login.');
        }

        $user = User::create([
            'school_id' => $invite->school_id,
            'usergroup_id' => $invite->usergroup_id,
            'name' => $invite->name,
            'email' => $invite->email,
            'mobile_no' => $invite->phone,
            'password' => Hash::make($password),
            'status' => 'active',
            'email_verified' => true,
            'email_verified_at' => now(),
        ]);
        Userprofile::create([
            'school_id' => $invite->school_id,
            'user_id' => $user->id,
            'usergroup_id' => $invite->usergroup_id,
            'firstname' => explode(' ', $invite->name)[0],
            'lastname' => explode(' ', $invite->name . ' Staff')[1] ?? 'Staff',
        ]);
        $year = SiteHelper::getAcademicYear($invite->school_id);
        if ($year) {
            TeacherProfile::create([
                'school_id' => $invite->school_id,
                'academic_year_id' => $year->id,
                'user_id' => $user->id,
                'status' => 1,
                'designation' => $invite->usergroup_id == User::TEACHER_USERGROUP_ID ? 'teacher' : 'staff',
            ]);
        }
        if ($invite->class_id) {
            $class = \App\Models\StandardLink::where('id', $invite->class_id)->where('school_id', $invite->school_id)->first();
            if ($class && ! $class->class_teacher_id) {
                $class->class_teacher_id = $user->id;
                $class->save();
            }
        }
        $invite->accepted_at = now();
        $invite->save();
        return $user;
    }
}
