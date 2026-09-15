<?php

namespace App\Http\Controllers\Parent;

use App\Helpers\SiteHelper;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Parent\Concerns\FindsChildren;
use App\Models\Attendance;
use App\Models\Notes;
use App\Services\ResultsService;
use Barryvdh\DomPDF\Facade\Pdf;

class ChildController extends Controller
{
    use FindsChildren;

    public function __construct(private ResultsService $results)
    {
    }

    public function show($id)
    {
        $child = $this->childOrFail($id);
        $report = $this->results->reportForStudent($child);
        $remarks = Notes::where('entity_name', 'student_remark')
            ->where('entity_id', $child->id)
            ->orderByDesc('id')
            ->take(10)
            ->get();

        return view('parent.child', [
            'child' => $child,
            'report' => $report,
            'remarks' => $remarks,
        ]);
    }

    public function results($id)
    {
        $child = $this->childOrFail($id);
        $report = $this->results->reportForStudent($child);
        return view('parent.results', $report + ['child' => $child]);
    }

    public function pdf($id)
    {
        $child = $this->childOrFail($id);
        $report = $this->results->reportForStudent($child);
        if (!$report['termResult'] || !$report['termResult']->published_at) {
            abort(404);
        }
        $pdf = Pdf::loadView('student.results.report', $report)->setPaper('a4');
        $name = 'report-card-' . str_replace(' ', '-', strtolower($child->name)) . '.pdf';
        return $pdf->download($name);
    }

    public function attendance($id)
    {
        $child = $this->childOrFail($id);
        $year = SiteHelper::getAcademicYear($child->school_id);
        $rows = Attendance::where('user_id', $child->id)
            ->where('academic_year_id', $year->id)
            ->orderByDesc('date')
            ->take(40)
            ->get();

        $present = $rows->filter(fn ($a) => in_array($a->mark, ['present', 'late'], true) || ((int) $a->status === 1 && !$a->mark))->count();
        $percent = $rows->count() ? round(($present / $rows->count()) * 100) : null;

        return view('parent.attendance', [
            'child' => $child,
            'rows' => $rows,
            'percent' => $percent,
        ]);
    }
}
