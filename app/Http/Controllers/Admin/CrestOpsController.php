<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\StudentParentLink;
use App\Models\Subscription;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Models\Userprofile;
use App\Models\ParentProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CrestOpsController extends Controller
{
    public function people(Request $request)
    {
        $who = $request->query('who', 'staff');
        if (!Auth::user()->school->usesParents()) {
            $who = 'staff';
        }
        if (!in_array($who, ['staff', 'parent'], true)) {
            $who = 'staff';
        }
        $invites = \App\Models\StaffInvite::where('school_id', Auth::user()->school_id)
            ->orderByDesc('id')
            ->take(20)
            ->get();
        $students = User::where('school_id', Auth::user()->school_id)->where('usergroup_id', 6)->orderBy('name')->get();
        $classes = \App\Models\StandardLink::with('standard', 'section')
            ->where('school_id', Auth::user()->school_id)
            ->get()
            ->sortBy(fn ($c) => sprintf('%03d-%s', optional($c->standard)->order ?? 99, optional($c->section)->name ?? ''));
        return view('admin.crest.people', compact('who', 'invites', 'students', 'classes'));
    }

    public function branding()
    {
        return redirect('/admin/schooldetails');
    }

    public function saveBranding(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'primary_color' => 'required|string|max:16',
            'custom_domain' => 'nullable|string|max:120',
        ]);
        $school = Auth::user()->school;
        $school->name = $data['name'];
        $school->primary_color = $data['primary_color'];
        $school->custom_domain = $data['custom_domain'] ?: null;
        if (!$school->slug) {
            $school->slug = Str::slug($data['name']);
        }
        $school->save();
        return back()->with('successmessage', 'Branding saved. Parents will see this name and colour.');
    }

    public function billing()
    {
        $school = Auth::user()->school;
        $studentCount = User::where('school_id', $school->id)->where('usergroup_id', 6)->count();
        return view('admin.crest.billing', compact('school', 'studentCount'));
    }

    public function changePlan(Request $request)
    {
        $plan = Plan::where('name', $request->input('plan'))->firstOrFail();
        Subscription::updateOrCreate(
            ['school_id' => Auth::user()->school_id],
            [
                'user_id' => Auth::id(),
                'plan_id' => $plan->id,
                'status' => $plan->name === 'trial' ? 'trial' : 'active',
                'end_date' => now()->addDays((int) $plan->cycle)->toDateString(),
            ]
        );
        return back()->with('successmessage', 'Plan updated to ' . $plan->display_name . '. Pay Orangebook into the school account to keep Pro/Basic live.');
    }

    public function storeTeacher(Request $request, \App\Services\SchoolJoinService $join)
    {
        $data = $request->validate([
            'name' => 'required|string|max:80',
            'email' => 'required|email',
        ]);
        if (\App\Models\User::where('email', $data['email'])->exists()) {
            return back()->with('failmessage', 'That email already has an Orangebook login.')->withInput();
        }
        $invite = $join->inviteStaff(Auth::user(), $data);
        return redirect('/admin/teachers')->with('successmessage', 'Invite sent to ' . $invite->email . '. They have 7 days to set a password.');
    }

    public function inviteTeacher()
    {
        $invites = \App\Models\StaffInvite::where('school_id', Auth::user()->school_id)
            ->orderByDesc('id')
            ->take(20)
            ->get();
        return view('admin.crest.invite-teacher', compact('invites'));
    }

    public function linkParent()
    {
        $students = User::where('school_id', Auth::user()->school_id)->where('usergroup_id', 6)->orderBy('name')->get();
        return view('admin.crest.link-parent', compact('students'));
    }

    public function storeParentLink(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:80',
            'email' => 'required|email',
            'phone' => 'nullable|string|max:20',
            'student_id' => 'required|integer',
        ]);
        $schoolId = Auth::user()->school_id;
        $student = User::where('school_id', $schoolId)->where('usergroup_id', 6)->where('id', $data['student_id'])->firstOrFail();
        $pin = (string) random_int(100000, 999999);

        $parent = User::firstOrCreate(
            ['email' => $data['email']],
            [
                'school_id' => $schoolId,
                'usergroup_id' => 7,
                'name' => $data['name'],
                'mobile_no' => $data['phone'] ?: null,
                'password' => Hash::make($pin),
                'status' => 'active',
                'email_verified' => true,
                'email_verified_at' => now(),
            ]
        );
        if ((int) $parent->school_id !== (int) $schoolId) {
            return back()->with('failmessage', 'That email belongs to another school.');
        }
        Userprofile::firstOrCreate(
            ['user_id' => $parent->id],
            ['school_id' => $schoolId, 'usergroup_id' => 7, 'firstname' => explode(' ', $data['name'])[0]]
        );
        ParentProfile::firstOrCreate(
            ['user_id' => $parent->id],
            ['school_id' => $schoolId, 'relation' => 'guardian']
        );
        StudentParentLink::firstOrCreate(
            ['parent_id' => $parent->id, 'student_id' => $student->id],
            ['school_id' => $schoolId, 'status' => 1]
        );
        $msg = optional(Auth::user()->school)->usesParents()
            ? $parent->name . ' can now see ' . $student->name . ' on the parent portal.'
            : $parent->name . ' is the emergency contact for ' . $student->name . '.';
        return redirect('/admin/people?who=parent')->with('successmessage', $msg);
    }

    public function storeGroup(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:80',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'integer',
        ]);
        $group = \App\Models\PeopleGroup::create([
            'school_id' => Auth::user()->school_id,
            'name' => $data['name'],
        ]);
        $ids = User::where('school_id', Auth::user()->school_id)->whereIn('id', $data['user_ids'])->pluck('id');
        $group->members()->sync($ids);
        return back()->with('successmessage', $group->name.' can be used on notices and circulars.');
    }

    public function storeTeacherBulk(Request $request, \App\Services\SchoolJoinService $join)
    {
        $data = $request->validate([
            'list' => 'required|string',
        ]);
        $sent = 0;
        $skip = 0;
        foreach (preg_split('/\r\n|\r|\n/', $data['list']) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $parts = array_map('trim', preg_split('/[,;]+/', $line, 2));
            if (count($parts) < 2 || !filter_var($parts[1], FILTER_VALIDATE_EMAIL)) {
                $skip++;
                continue;
            }
            [$name, $email] = $parts;
            if (User::where('email', $email)->exists()) {
                $skip++;
                continue;
            }
            $join->inviteStaff(Auth::user(), ['name' => $name, 'email' => $email]);
            $sent++;
        }
        return redirect('/admin/teachers')->with('successmessage', $sent.' teacher invite'.($sent === 1 ? '' : 's').' sent'.($skip ? ', '.$skip.' skipped' : '').'.');
    }

    public function storeParentBulk(Request $request)
    {
        $schoolId = Auth::user()->school_id;
        $classId = $request->input('class_id');
        $list = trim((string) $request->input('list'));
        if ($list === '') {
            return back()->with('failmessage', 'Paste a list. For a whole class: Name, email — one line per student without a parent. Or: Parent name, email, Child name.')->withInput();
        }
        if ($classId) {
            $studentIds = \App\Models\StudentAcademic::where('school_id', $schoolId)->where('standardLink_id', $classId)->pluck('user_id');
            $students = User::whereIn('id', $studentIds)->orderBy('name')->get();
            $linked = $this->linkParentsOfClass($schoolId, $students, $list);
        } else {
            $linked = $this->linkParentsFromList($schoolId, $list);
        }
        return redirect('/admin/parents')->with('successmessage', $linked.' parent'.($linked === 1 ? '' : 's').' linked.');
    }

    private function linkParentsFromList(int $schoolId, string $list): int
    {
        $n = 0;
        foreach (preg_split('/\r\n|\r|\n/', $list) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $parts = array_map('trim', preg_split('/[,;]+/', $line));
            if (count($parts) < 3 || !filter_var($parts[1], FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            [$parentName, $email, $childName] = [$parts[0], $parts[1], $parts[2]];
            $student = User::where('school_id', $schoolId)->where('usergroup_id', 6)->whereRaw('LOWER(name) = ?', [strtolower($childName)])->first();
            if (!$student) {
                continue;
            }
            $this->attachParent($schoolId, $parentName, $email, null, $student);
            $n++;
        }
        return $n;
    }

    private function linkParentsOfClass(int $schoolId, $students, string $list): int
    {
        $rows = [];
        foreach (preg_split('/\r\n|\r|\n/', $list) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $parts = array_map('trim', preg_split('/[,;]+/', $line));
            if (count($parts) >= 3 && filter_var($parts[1], FILTER_VALIDATE_EMAIL)) {
                $rows[] = ['name' => $parts[0], 'email' => $parts[1], 'child' => $parts[2]];
            } elseif (count($parts) === 2 && filter_var($parts[1], FILTER_VALIDATE_EMAIL)) {
                $rows[] = ['name' => $parts[0], 'email' => $parts[1], 'child' => null];
            } elseif (filter_var($line, FILTER_VALIDATE_EMAIL)) {
                $rows[] = ['name' => explode('@', $line)[0], 'email' => $line, 'child' => null];
            }
        }
        $n = 0;
        $without = $students->filter(fn ($s) => !$s->parent || $s->parent->count() === 0)->values();
        $i = 0;
        foreach ($rows as $row) {
            $student = null;
            if ($row['child']) {
                $student = $students->first(fn ($s) => strtolower($s->name) === strtolower($row['child']));
            }
            if (!$student && isset($without[$i])) {
                $student = $without[$i];
                $i++;
            }
            if (!$student) {
                continue;
            }
            $this->attachParent($schoolId, $row['name'], $row['email'], null, $student);
            $n++;
        }
        return $n;
    }

    private function attachParent(int $schoolId, string $name, string $email, ?string $phone, User $student): void
    {
        $parent = User::firstOrCreate(
            ['email' => $email],
            [
                'school_id' => $schoolId,
                'usergroup_id' => 7,
                'name' => $name,
                'mobile_no' => $phone ?: null,
                'password' => Hash::make((string) random_int(100000, 999999)),
                'status' => 'active',
                'email_verified' => true,
                'email_verified_at' => now(),
            ]
        );
        Userprofile::firstOrCreate(['user_id' => $parent->id], ['school_id' => $schoolId, 'usergroup_id' => 7, 'firstname' => explode(' ', $name)[0], 'status' => 'active']);
        ParentProfile::firstOrCreate(['user_id' => $parent->id], ['school_id' => $schoolId, 'relation' => 'guardian']);
        StudentParentLink::firstOrCreate(
            ['parent_id' => $parent->id, 'student_id' => $student->id],
            ['school_id' => $schoolId, 'status' => 1]
        );
    }
}
