<?php

namespace App\Http\Controllers;

use App\Models\StaffInvite;
use App\Services\SchoolJoinService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StaffJoinController extends Controller
{
    public function show(string $token)
    {
        $invite = StaffInvite::where('token', $token)->firstOrFail();
        if (!$invite->isOpen()) {
            return view('join.staff-expired');
        }
        return view('join.staff', compact('invite'));
    }

    public function store(Request $request, string $token, SchoolJoinService $join)
    {
        $invite = StaffInvite::where('token', $token)->firstOrFail();
        $request->validate([
            'password' => 'required|string|min:6|confirmed',
        ]);
        try {
            $user = $join->acceptStaff($invite, $request->password);
        } catch (\Throwable $e) {
            return back()->with('failmessage', $e->getMessage());
        }
        Auth::login($user);
        return redirect('/teacher/dashboard')
            ->with('successmessage', 'Welcome to ' . $invite->school->name . '.');
    }
}
