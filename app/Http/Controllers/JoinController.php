<?php

namespace App\Http\Controllers;

use App\Services\SchoolJoinService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JoinController extends Controller
{
    public function create()
    {
        return view('join.parent', [
            'code' => request('code'),
        ]);
    }

    public function store(Request $request, SchoolJoinService $join)
    {
        $data = $request->validate([
            'join_code' => 'required|string|max:24',
            'admission_no' => 'required|string|max:40',
            'date_of_birth' => 'required|date',
            'name' => 'required|string|max:80',
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        $student = $join->findStudent($data['join_code'], $data['admission_no'], $data['date_of_birth']);
        if (!$student) {
            return back()->with('failmessage', 'Those details do not match a student in this school. Check the join code, admission number and date of birth.')->withInput();
        }

        try {
            $parent = $join->registerParent($student, $data);
        } catch (\Throwable $e) {
            return back()->with('failmessage', $e->getMessage())->withInput();
        }

        Auth::login($parent);
        return redirect('/parent/dashboard')
            ->with('successmessage', 'You are linked to ' . $student->name . '.');
    }
}
