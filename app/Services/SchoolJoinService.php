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
        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $school->slug ?: $school->name), 0, 5));
        do {
            $code = $prefix . '-' . strtoupper(Str::random(4));
        } while (School::where('join_code', $code)->exists());
        return $code;
    }

    public function findStudent(string $joinCode, string $admission, string $dob): ?User
    {
        $school = School::where('join_code', strtoupper(trim($joinCode)))->where('status', 1)->first();
        if (!$school) {
            return null;
        }
        $admission = trim($admission);
        return User::where('school_id', $school->id)
            ->where('usergroup_id', 6)
            ->where('status', 'active')
            ->whereHas('userprofile', function ($q) use ($dob) {
                $q->whereDate('date_of_birth', $dob);
            })
            ->where(function ($q) use ($admission) {
                $q->where('registration_number', $admission)
                    ->orWhereHas('userprofile', fn ($p) => $p->where('registration_number', $admission))
                    ->orWhereHas('studentAcademicLatest', fn ($s) => $s->where('roll_number', $admission));
            })
            ->first();
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
                'mobile_no' => $data['phone'] ?? ('0805' . random_int(1000000, 9999999)),
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

    public function inviteStaff(User $admin, array $data): StaffInvite
    {
        $invite = StaffInvite::create([
            'school_id' => $admin->school_id,
            'invited_by' => $admin->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'usergroup_id' => 5,
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
            abort(422, 'That email already has a Crest login.');
        }

        $user = User::create([
            'school_id' => $invite->school_id,
            'usergroup_id' => $invite->usergroup_id,
            'name' => $invite->name,
            'email' => $invite->email,
            'mobile_no' => '0803' . random_int(1000000, 9999999),
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
                'designation' => 'teacher',
            ]);
        }
        $invite->accepted_at = now();
        $invite->save();
        return $user;
    }
}
