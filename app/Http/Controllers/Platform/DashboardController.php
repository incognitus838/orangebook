<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\SchoolApplication;
use App\Models\User;
use App\Services\SchoolOnboardingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $schools = $this->orgQuery()->get();
        $applications = SchoolApplication::orderByDesc('id')->get();
        $stats = [
            'orgs' => $schools->count(),
            'schools' => $schools->where('kind', 'school')->count() + $schools->whereNull('kind')->count(),
            'ngo' => $schools->where('kind', 'ngo')->count(),
            'training' => $schools->where('kind', 'training')->count(),
            'pending' => $applications->where('status', 'pending')->count(),
            'learners' => $schools->sum('students_count'),
        ];
        return view('platform.dashboard', compact('schools', 'applications', 'stats'));
    }

    public function organisations()
    {
        $schools = $this->orgQuery()->get();
        return view('platform.organisations', compact('schools'));
    }

    public function show($id)
    {
        $school = School::findOrFail($id);
        $people = User::where('school_id', $school->id)->where('status', 'active')->orderBy('usergroup_id')->orderBy('name')->get();
        $principal = $people->firstWhere('usergroup_id', 3);
        $counts = [
            'admins' => $people->where('usergroup_id', 3)->count(),
            'teachers' => $people->where('usergroup_id', 5)->count(),
            'learners' => $people->where('usergroup_id', 6)->count(),
            'parents' => $people->where('usergroup_id', 7)->count(),
        ];
        return view('platform.show', compact('school', 'people', 'principal', 'counts'));
    }

    public function applications()
    {
        $applications = SchoolApplication::orderByDesc('id')->get();
        return view('platform.applications', compact('applications'));
    }

    public function approveApplication($id, SchoolOnboardingService $onboard)
    {
        $application = SchoolApplication::findOrFail($id);
        if ($application->status === 'approved' && $application->school_id) {
            return redirect('/platform/organisations/'.$application->school_id);
        }
        $password = 'password';
        $admin = $onboard->createSchool([
            'school_name' => $application->school_name,
            'admin_name' => $application->admin_name,
            'email' => $application->email,
            'phone' => $application->phone,
            'kind' => $application->kind ?: 'school',
            'password' => $password,
        ]);
        $application->status = 'approved';
        $application->save();
        return redirect('/platform/organisations/'.$admin->school_id)
            ->with('successmessage', $application->school_name.' is live. Login '.$admin->email.' / password.');
    }

    public function open($id)
    {
        $school = School::findOrFail($id);
        $principal = User::where('school_id', $school->id)->where('usergroup_id', 3)->where('status', 'active')->firstOrFail();
        session(['platform_user_id' => Auth::id()]);
        Auth::login($principal);
        return redirect('/admin/dashboard')->with('successmessage', 'You are inside '.$school->name.'.');
    }

    public function return()
    {
        $id = session('platform_user_id');
        session()->forget('platform_user_id');
        if ($id) {
            $user = User::find($id);
            if ($user && $user->isSiteAdmin()) {
                Auth::login($user);
            }
        }
        return redirect('/platform');
    }

    public function toggle($id)
    {
        $school = School::findOrFail($id);
        $school->status = $school->status ? 0 : 1;
        $school->save();
        return back()->with('successmessage', $school->name.' is now '.($school->status ? 'active' : 'suspended').'.');
    }

    private function orgQuery()
    {
        return School::where('status', 1)->withCount([
            'users as students_count' => fn ($q) => $q->where('usergroup_id', 6)->where('status', 'active'),
            'users as teachers_count' => fn ($q) => $q->where('usergroup_id', 5)->where('status', 'active'),
        ])->orderBy('kind')->orderBy('name');
    }
}
