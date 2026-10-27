<?php

namespace App\Http\Controllers\Admin\Setting;

use App\Http\Controllers\Controller;
use App\Helpers\SiteHelper;
use App\Models\SchoolAcademicSetting;
use App\Services\ResultsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GeneralController extends Controller
{
    public function create(ResultsService $results)
    {
        $year = SiteHelper::getAcademicYear(Auth::user()->school_id);
        $settings = $year
            ? $results->settings(Auth::user()->school_id, $year->id)
            : null;
        return view('admin.settings.marks', [
            'year' => $year,
            'settings' => $settings,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'pass_mark' => 'required|integer|min:0|max:100',
            'ca_max' => 'required|integer|min:0|max:100',
            'midterm_max' => 'required|integer|min:0|max:100',
            'exam_max' => 'required|integer|min:0|max:100',
        ]);
        $year = SiteHelper::getAcademicYear(Auth::user()->school_id);
        if (!$year) {
            return back()->with('failmessage', 'Set a current academic year first.');
        }
        $settings = SchoolAcademicSetting::firstOrCreate(
            ['school_id' => Auth::user()->school_id, 'academic_year_id' => $year->id],
            ['grading_scale' => 'waec', 'grade_bands' => SchoolAcademicSetting::waecBands()]
        );
        $settings->pass_mark = $data['pass_mark'];
        $settings->ca_max = $data['ca_max'];
        $settings->midterm_max = $data['midterm_max'];
        $settings->exam_max = $data['exam_max'];
        $settings->ca_weight = $data['ca_max'];
        $settings->exam_weight = $data['exam_max'];
        $settings->save();
        return redirect('/admin/marks')->with('successmessage', 'Mark totals for '.$year->name.' saved.');
    }
}
