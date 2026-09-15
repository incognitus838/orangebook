<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Services\SchoolOnboardingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class StartSchoolController extends Controller
{
    public function create(Request $request)
    {
        $plans = Plan::where('is_active', 1)->orderBy('order')->get();
        $selected = $request->query('plan', 'trial');
        return view('marketing.start', compact('plans', 'selected'));
    }

    public function store(Request $request, SchoolOnboardingService $onboarding)
    {
        $data = $request->validate([
            'school_name' => 'required|string|max:120|unique:schools,name',
            'admin_name' => 'required|string|max:80',
            'email' => 'required|email|unique:users,email|unique:schools,email',
            'phone' => 'nullable|string|max:20|unique:schools,phone',
            'password' => 'required|string|min:6',
            'plan' => 'nullable|string',
        ]);

        $plan = Plan::where('name', $data['plan'] ?? 'trial')->first();
        $data['slug'] = Str::slug($data['school_name']);
        $data['phone'] = $data['phone'] ?: ('0803' . random_int(1000000, 9999999));

        $admin = $onboarding->createSchool($data, $plan);
        Auth::login($admin);

        return redirect('/admin/crest/billing')
            ->with('successmessage', 'Your school is live on a ' . ($plan->display_name ?? 'trial') . ' plan.');
    }
}
