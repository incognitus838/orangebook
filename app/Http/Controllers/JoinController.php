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
            'child_name' => 'required|string|max:80',
            'admission_no' => 'nullable|string|max:40',
            'name' => 'required|string|max:80',
            'email' => 'required|email',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:6',
        ]);

        if (!$join->findSchool($data['join_code'])) {
            return back()->with('failmessage', 'That join code does not match a school.')->withInput();
        }

        $matches = $join->findStudents($data['join_code'], $data['child_name'], $data['admission_no'] ?? null);
        if ($matches->count() > 1) {
            return back()->with('failmessage', 'More than one student has that name. Add the admission number from the report card.')->withInput();
        }
        $student = $matches->first();
        if (!$student) {
            return back()->with('failmessage', 'Those details do not match a student in this school. Check the join code and the child’s name.')->withInput();
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

    public function studentCreate()
    {
        return view('join.student', [
            'code' => request('code'),
        ]);
    }

    public function studentStore(Request $request, SchoolJoinService $join)
    {
        $data = $request->validate([
            'join_code' => 'required|string|max:24',
            'child_name' => 'required|string|max:80',
            'admission_no' => 'required|string|max:40',
            'password' => 'required|string|min:6',
        ]);
        try {
            $student = $join->claimStudent($data['join_code'], $data['child_name'], $data['admission_no'], $data['password']);
        } catch (\Throwable $e) {
            return back()->with('failmessage', $e->getMessage())->withInput();
        }
        Auth::login($student);
        return redirect('/student/dashboard')->with('successmessage', 'Welcome, '.$student->name.'.');
    }
}
