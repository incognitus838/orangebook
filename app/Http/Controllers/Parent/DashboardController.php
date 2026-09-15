<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Parent\Concerns\FindsChildren;
use App\Models\Notes;
use App\Services\ResultsService;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    use FindsChildren;

    public function __construct(private ResultsService $results)
    {
    }

    public function index()
    {
        $children = $this->children();
        $cards = $children->map(function ($child) {
            $report = $this->results->reportForStudent($child);
            $latestRemark = Notes::where('entity_name', 'student_remark')
                ->where('entity_id', $child->id)
                ->orderByDesc('id')
                ->first();
            return [
                'child' => $child,
                'className' => $report['className'],
                'termResult' => $report['termResult'],
                'attendancePercent' => $report['attendancePercent'],
                'remark' => $latestRemark,
            ];
        });

        return view('parent.dashboard', [
            'parent' => Auth::user(),
            'cards' => $cards,
        ]);
    }
}
