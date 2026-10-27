<?php

namespace App\Http\Controllers\Teacher;

use App\Helpers\SiteHelper;
use App\Http\Controllers\Controller;
use App\Models\AcademicTerm;
use App\Models\AssessmentScore;
use App\Models\AssessmentScoreAudit;
use App\Models\SchoolAcademicSetting;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\Teacherlink;
use App\Models\TermResult;
use App\Services\ResultsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ResultsController extends Controller
{
    public function __construct(private ResultsService $results)
    {
    }

    public function index()
    {
        $schoolId = Auth::user()->school_id;
        $year = SiteHelper::getAcademicYear($schoolId);
        $formClass = StandardLink::where([
            ['school_id', $schoolId],
            ['academic_year_id', $year->id],
            ['class_teacher_id', Auth::id()],
        ])->first();
        if ($formClass) {
            return redirect('/teacher/class/' . $formClass->id . '/results');
        }
        $first = SiteHelper::teacherClassIds($schoolId, Auth::id())->first();
        if ($first) {
            return redirect('/teacher/class/' . $first . '/results');
        }
        return view('teacher.classroom.empty');
    }

    public function show(Request $request, $id)
    {
        $class = $this->class($id);
        $schoolId = Auth::user()->school_id;
        $year = SiteHelper::getAcademicYear($schoolId);
        $term = AcademicTerm::where('school_id', $schoolId)->where('academic_year_id', $year->id)->where('is_current', true)->firstOrFail();
        $settings = $this->results->settings($schoolId, $year->id);

        $subjects = $this->teacherSubjects($class);
        $subjectId = (int) ($request->get('subject_id') ?: optional($subjects->first())->id);
        if ($subjectId && !$subjects->contains('id', $subjectId)) {
            abort(403);
        }

        $students = StudentAcademic::with('user')
            ->where('standardLink_id', $class->id)
            ->orderBy('roll_number')
            ->get();

        $scores = AssessmentScore::where('academic_term_id', $term->id)
            ->where('standardLink_id', $class->id)
            ->where('subject_id', $subjectId)
            ->get()
            ->keyBy('student_id');

        $audits = AssessmentScoreAudit::whereIn('assessment_score_id', $scores->pluck('id'))
            ->with('changer')
            ->orderByDesc('id')
            ->take(20)
            ->get();

        $termResults = TermResult::where('academic_term_id', $term->id)
            ->where('standardLink_id', $class->id)
            ->get()
            ->keyBy('student_id');

        $gaps = $this->results->classGaps($term->id, $class->id);

        return view('teacher.results.show', [
            'class' => $class,
            'term' => $term,
            'settings' => $settings,
            'subjects' => $subjects,
            'subjectId' => $subjectId,
            'students' => $students,
            'scores' => $scores,
            'audits' => $audits,
            'termResults' => $termResults,
            'isFormTeacher' => (int) $class->class_teacher_id === (int) Auth::id(),
            'gaps' => $gaps,
        ]);
    }

    public function store(Request $request, $id)
    {
        $class = $this->class($id);
        $year = SiteHelper::getAcademicYear(Auth::user()->school_id);
        $term = AcademicTerm::where('school_id', Auth::user()->school_id)->where('academic_year_id', $year->id)->where('is_current', true)->firstOrFail();
        $subjects = $this->teacherSubjects($class);
        $subjectId = (int) $request->input('subject_id');
        if (!$subjects->contains('id', $subjectId)) {
            abort(403);
        }

        $this->results->saveScores([
            'school_id' => Auth::user()->school_id,
            'academic_year_id' => $year->id,
            'academic_term_id' => $term->id,
            'standardLink_id' => $class->id,
            'subject_id' => $subjectId,
        ], $request->input('rows', []), Auth::id());

        return redirect('/teacher/class/' . $class->id . '/results?subject_id=' . $subjectId)
            ->with('successmessage', 'Scores saved. Totals, grades and remarks were recalculated.');
    }

    public function publish($id)
    {
        $class = $this->class($id);
        if ((int) $class->class_teacher_id !== (int) Auth::id()) {
            abort(403);
        }
        $year = SiteHelper::getAcademicYear(Auth::user()->school_id);
        $term = AcademicTerm::where('school_id', Auth::user()->school_id)->where('academic_year_id', $year->id)->where('is_current', true)->firstOrFail();
        try {
            $this->results->publishClass(Auth::user()->school_id, $term->id, $class->id);
        } catch (\RuntimeException $e) {
            return redirect('/teacher/class/' . $class->id . '/results')
                ->with('failmessage', $e->getMessage() . '. Fill them before publish.');
        }

        return redirect('/teacher/class/' . $class->id . '/results')
            ->with('successmessage', $term->name . ' results published. Students and parents can now open the report card.');
    }

    public function pdf($id, $studentId)
    {
        $class = $this->class($id);
        $student = StudentAcademic::where('standardLink_id', $class->id)
            ->where('user_id', $studentId)
            ->with('user')
            ->firstOrFail()
            ->user;
        $report = $this->results->reportForStudent($student);
        if (!$report['termResult'] || !$report['termResult']->published_at) {
            return redirect('/teacher/class/' . $class->id . '/results')
                ->with('failmessage', 'Publish the term first.');
        }
        $pdf = Pdf::loadView('student.results.report', $report)->setPaper('a4');
        $name = 'report-card-' . str_replace(' ', '-', strtolower($student->name)) . '.pdf';
        return $pdf->download($name);
    }

    private function class($id): StandardLink
    {
        $class = StandardLink::with('standard', 'section')->findOrFail($id);
        if (!Gate::allows('standardlink', $class)) {
            abort(403);
        }
        return $class;
    }

    private function teacherSubjects(StandardLink $class)
    {
        $query = Teacherlink::where('standardLink_id', $class->id);
        if ((int) $class->class_teacher_id !== (int) Auth::id()) {
            $query->where('teacher_id', Auth::id());
        }
        return Subject::whereIn('id', $query->pluck('subject_id'))->orderBy('name')->get();
    }
}
