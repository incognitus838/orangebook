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
    public function branding()
    {
        return view('admin.crest.branding', ['school' => Auth::user()->school]);
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
        $subscription = Subscription::where('school_id', $school->id)->with('plan')->latest('id')->first();
        $plans = Plan::where('is_active', 1)->orderBy('order')->get();
        $studentCount = User::where('school_id', $school->id)->where('usergroup_id', 6)->count();
        return view('admin.crest.billing', compact('school', 'subscription', 'plans', 'studentCount'));
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
        return back()->with('successmessage', 'Plan updated to ' . $plan->display_name . '. Pay Crest into the school account to keep Pro/Basic live.');
    }

    public function storeTeacher(Request $request, \App\Services\SchoolJoinService $join)
    {
        $data = $request->validate([
            'name' => 'required|string|max:80',
            'email' => 'required|email',
        ]);
        if (\App\Models\User::where('email', $data['email'])->exists()) {
            return back()->with('failmessage', 'That email already has a Crest login.')->withInput();
        }
        $invite = $join->inviteStaff(Auth::user(), $data);
        return back()->with('successmessage', 'Invite sent to ' . $invite->email . '. They have 7 days to set a password. Link: ' . url('/staff/join/'.$invite->token));
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
            'student_id' => 'required|integer',
            'password' => 'required|string|min:6',
        ]);
        $schoolId = Auth::user()->school_id;
        $student = User::where('school_id', $schoolId)->where('usergroup_id', 6)->where('id', $data['student_id'])->firstOrFail();

        $parent = User::firstOrCreate(
            ['email' => $data['email']],
            [
                'school_id' => $schoolId,
                'usergroup_id' => 7,
                'name' => $data['name'],
                'mobile_no' => '0805' . random_int(1000000, 9999999),
                'password' => Hash::make($data['password']),
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
        return back()->with('successmessage', $parent->name . ' can now see ' . $student->name . ' on the parent portal.');
    }
}
