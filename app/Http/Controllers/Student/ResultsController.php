<?php

namespace App\Http\Controllers\Student;

use App\Helpers\SiteHelper;
use App\Http\Controllers\Controller;
use App\Models\AcademicTerm;
use App\Models\AssessmentScore;
use App\Models\Attendance;
use App\Models\School;
use App\Models\TermResult;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class ResultsController extends Controller
{
    public function index()
    {
        $data = $this->reportData();
        return view('student.results.index', $data);
    }

    public function pdf()
    {
        $data = $this->reportData();
        if (!$data['termResult'] || !$data['termResult']->published_at) {
            abort(404);
        }
        $pdf = Pdf::loadView('student.results.report', $data)->setPaper('a4');
        $name = 'report-card-' . str_replace(' ', '-', strtolower($data['term']->name)) . '.pdf';
        return $pdf->download($name);
    }

    private function reportData(): array
    {
        $user = Auth::user();
        $year = SiteHelper::getAcademicYear($user->school_id);
        $term = AcademicTerm::where('school_id', $user->school_id)
            ->where('academic_year_id', $year->id)
            ->where('is_current', true)
            ->first();
        $classId = optional($user->studentAcademicLatest)->standardLink_id;

        $scores = collect();
        $termResult = null;
        if ($term && $classId) {
            $scores = AssessmentScore::with('subject')
                ->where('academic_term_id', $term->id)
                ->where('standardLink_id', $classId)
                ->where('student_id', $user->id)
                ->where('published', true)
                ->get();
            $termResult = TermResult::where('academic_term_id', $term->id)
                ->where('student_id', $user->id)
                ->first();
        }
        $formTeacher = optional(optional($user->studentAcademicLatest)->standardLink)->teacher;

        $rows = Attendance::where('user_id', $user->id)->where('academic_year_id', $year->id)->get();
        $totalDays = $rows->count();
        $presentDays = $rows->filter(function ($a) {
            return in_array($a->mark, ['present', 'late'], true) || ((int) $a->status === 1 && !$a->mark);
        })->count();

        return [
            'school' => School::find($user->school_id),
            'student' => $user,
            'term' => $term,
            'year' => $year,
            'className' => optional(optional($user->studentAcademicLatest)->standardLink)->StandardSection,
            'scores' => $scores,
            'termResult' => $termResult,
            'attendancePercent' => $totalDays ? round(($presentDays / $totalDays) * 100) : null,
            'formTeacher' => $formTeacher ?? null,
        ];
    }
}
