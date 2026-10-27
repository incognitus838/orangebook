<?php

namespace App\Services;

use App\Helpers\SiteHelper;
use App\Models\School;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\StudentParentLink;
use App\Models\User;
use App\Models\Userprofile;
use App\Support\AdmissionNumber;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LearnerEnrollmentService
{
    /**
     * Create a learner (and optional parent) for a school.
     *
     * @param  array<string, mixed>  $row
     * @return array{user: User, pin: string, parent: ?User, parent_pin: ?string}
     */
    public function enroll(School $school, array $row, StandardLink $class): array
    {
        if ((int) $class->school_id !== (int) $school->id) {
            abort(404);
        }

        $first = trim((string) ($row['first_name'] ?? ''));
        $last = trim((string) ($row['last_name'] ?? ''));
        $other = trim((string) ($row['other_names'] ?? ''));
        $name = trim($first.' '.$other.' '.$last);
        $name = preg_replace('/\s+/', ' ', $name) ?: $first;
        if ($name === '') {
            throw ValidationException::withMessages(['first_name' => 'Give a first name.']);
        }

        $email = trim((string) ($row['email'] ?? ''));
        $email = $email === '' ? null : strtolower($email);
        $admission = AdmissionNumber::normalize($row['admission_no'] ?? null);
        $year = SiteHelper::getAcademicYear($school->id);

        if (($school->kind ?? '') === 'training') {
            $existing = null;
            if ($email) {
                $existing = User::where('school_id', $school->id)->where('usergroup_id', User::STUDENT_USERGROUP_ID)->where('email', $email)->first();
            }
            if (! $existing && $admission) {
                $existing = User::where('school_id', $school->id)->where('usergroup_id', User::STUDENT_USERGROUP_ID)->where('registration_number', $admission)->first();
            }
            if ($existing) {
                $dup = StudentAcademic::where('user_id', $existing->id)
                    ->where('academic_year_id', $year->id)
                    ->where('standardLink_id', $class->id)
                    ->exists();
                if ($dup) {
                    throw ValidationException::withMessages(['class_id' => 'That learner is already in this course.']);
                }
                StudentAcademic::create([
                    'school_id' => $school->id,
                    'academic_year_id' => $year->id,
                    'user_id' => $existing->id,
                    'standardLink_id' => $class->id,
                    'roll_number' => $existing->registration_number,
                ]);

                return [
                    'user' => $existing->fresh(['studentAcademicLatest.standardLink.standard', 'studentAcademicLatest.standardLink.section']),
                    'pin' => '',
                    'parent' => null,
                    'parent_pin' => null,
                ];
            }
        }

        if ($email && User::where('email', $email)->exists()) {
            throw ValidationException::withMessages(['email' => 'That email is already in use.']);
        }
        if ($admission && User::where('school_id', $school->id)->where('registration_number', $admission)->exists()) {
            throw ValidationException::withMessages(['admission_no' => 'That admission number is already in use.']);
        }
        if (! $admission) {
            $admission = AdmissionNumber::next($school);
        }

        $phone = trim((string) ($row['phone'] ?? ''));
        $phone = $phone === '' ? null : $phone;

        $pin = (string) random_int(100000, 999999);

        $user = User::create([
            'school_id' => $school->id,
            'usergroup_id' => User::STUDENT_USERGROUP_ID,
            'name' => $name,
            'email' => $email,
            'mobile_no' => $phone,
            'registration_number' => $admission,
            'password' => Hash::make($pin),
            'status' => 'active',
            'email_verified' => (bool) $email,
            'email_verified_at' => $email ? now() : null,
        ]);

        $gender = in_array($row['gender'] ?? '', ['male', 'female'], true) ? $row['gender'] : null;

        Userprofile::create([
            'school_id' => $school->id,
            'user_id' => $user->id,
            'usergroup_id' => User::STUDENT_USERGROUP_ID,
            'firstname' => $first,
            'lastname' => trim($other.' '.$last),
            'gender' => $gender,
            'date_of_birth' => $row['date_of_birth'] ?? null,
            'registration_number' => $admission,
            'status' => 'active',
            'joining_date' => now()->toDateString(),
        ]);

        StudentAcademic::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'user_id' => $user->id,
            'standardLink_id' => $class->id,
            'roll_number' => $admission,
        ]);

        $parent = null;
        $parentPin = null;
        if ($school->usesParents()) {
            $linked = $this->linkParent($school, $user, $row);
            $parent = $linked['parent'];
            $parentPin = $linked['pin'];
        }

        return [
            'user' => $user->fresh(['studentAcademicLatest.standardLink.standard', 'studentAcademicLatest.standardLink.section']),
            'pin' => $pin,
            'parent' => $parent,
            'parent_pin' => $parentPin,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{parent: ?User, pin: ?string}
     */
    public function linkParent(School $school, User $student, array $row): array
    {
        $parentEmail = strtolower(trim((string) ($row['parent_email'] ?? '')));
        $parentName = trim((string) ($row['parent_name'] ?? ''));
        $parentPhone = trim((string) ($row['parent_phone'] ?? ''));
        $relation = trim((string) ($row['relationship'] ?? 'parent')) ?: 'parent';

        if ($parentEmail === '' && $parentName === '') {
            return ['parent' => null, 'pin' => null];
        }

        $parent = null;
        if ($parentEmail !== '') {
            $parent = User::where('school_id', $school->id)
                ->where('usergroup_id', User::PARENT_USERGROUP_ID)
                ->where('email', $parentEmail)
                ->first();
        }

        $pin = null;
        if (! $parent) {
            if ($parentName === '') {
                $parentName = $parentEmail !== '' ? strtok($parentEmail, '@') : 'Parent';
            }
            $pin = (string) random_int(100000, 999999);
            $parent = User::create([
                'school_id' => $school->id,
                'usergroup_id' => User::PARENT_USERGROUP_ID,
                'name' => $parentName,
                'email' => $parentEmail !== '' ? $parentEmail : null,
                'mobile_no' => $parentPhone !== '' ? $parentPhone : null,
                'password' => Hash::make($pin),
                'status' => 'active',
                'email_verified' => $parentEmail !== '',
                'email_verified_at' => $parentEmail !== '' ? now() : null,
            ]);
            $parts = preg_split('/\s+/', $parentName, 2);
            Userprofile::create([
                'school_id' => $school->id,
                'user_id' => $parent->id,
                'usergroup_id' => User::PARENT_USERGROUP_ID,
                'firstname' => $parts[0],
                'lastname' => $parts[1] ?? '',
                'status' => 'active',
            ]);
        }

        $exists = StudentParentLink::where('school_id', $school->id)
            ->where('parent_id', $parent->id)
            ->where('student_id', $student->id)
            ->exists();
        if (! $exists) {
            StudentParentLink::create([
                'school_id' => $school->id,
                'parent_id' => $parent->id,
                'student_id' => $student->id,
                'status' => 'active',
            ]);
        }

        return ['parent' => $parent, 'pin' => $pin];
    }

    public static function slip(User $user, string $pin, ?string $className = null): array
    {
        $school = $user->school;
        $className = $className ?: optional(optional($user->studentAcademicLatest)->standardLink)->StandardSection;

        return [
            'name' => $user->name,
            'class' => $className,
            'admission_no' => $user->registration_number,
            'pin' => $pin,
            'school_code' => $school?->join_code,
            'org' => $school?->name,
        ];
    }
}
