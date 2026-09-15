<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\School;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $schools = School::with(['subscription.plan'])->orderByDesc('id')->get();
        $plans = Plan::orderBy('order')->get();
        return view('platform.dashboard', compact('schools', 'plans'));
    }

    public function updatePlan(Request $request, $id)
    {
        $school = School::findOrFail($id);
        $plan = Plan::where('name', $request->input('plan'))->firstOrFail();
        $admin = User::where('school_id', $school->id)->where('usergroup_id', 3)->first();

        Subscription::updateOrCreate(
            ['school_id' => $school->id],
            [
                'user_id' => $admin?->id,
                'plan_id' => $plan->id,
                'status' => $plan->name === 'trial' ? 'trial' : 'active',
                'end_date' => now()->addDays((int) $plan->cycle)->toDateString(),
            ]
        );

        return redirect('/platform')->with('successmessage', $school->name . ' moved to ' . $plan->display_name . '.');
    }

    public function toggle($id)
    {
        $school = School::findOrFail($id);
        $school->status = $school->status ? 0 : 1;
        $school->save();
        return redirect('/platform')->with('successmessage', $school->name . ' is now ' . ($school->status ? 'active' : 'suspended') . '.');
    }
}
