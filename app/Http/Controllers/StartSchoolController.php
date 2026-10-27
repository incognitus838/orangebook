<?php

namespace App\Http\Controllers;

use App\Models\SchoolApplication;
use Illuminate\Http\Request;

class StartSchoolController extends Controller
{
    public function create()
    {
        return view('marketing.start');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'school_name' => 'required|string|max:120',
            'admin_name' => 'required|string|max:80',
            'email' => 'required|email|max:120',
            'phone' => 'nullable|string|max:20',
            'kind' => 'required|in:school,ngo,training',
            'student_count' => 'required|integer|min:1|max:100000',
            'notes' => 'nullable|string|max:2000',
        ]);

        SchoolApplication::create($data + ['status' => 'pending']);

        return redirect('/start')->with('applied_email', $data['email']);
    }
}
