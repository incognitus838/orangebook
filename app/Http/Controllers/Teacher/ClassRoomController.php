<?php

namespace App\Http\Controllers\Teacher;

use App\Helpers\SiteHelper;
use App\Http\Controllers\Controller;
use App\Models\AcademicTerm;
use App\Models\Attendance;
use App\Models\Homework;
use App\Models\Notes;
use App\Models\SchoolAcademicSetting;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ClassRoomController extends Controller
{
    public function index()
    {
        $formClass = $this->formClass();
        if ($formClass) {
            return redirect('/teacher/class/' . $formClass->id);
        }

        $ids = SiteHelper::teacherClassIds(Auth::user()->school_id, Auth::id());
        $first = $ids->first();
        if ($first) {
            return redirect('/teacher/class/' . $first);
        }

        return view('teacher.classroom.empty');
    }

    public function show($id)
    {
        $class = $this->authorizedClass($id);
        $schoolId = Auth::user()->school_id;
        $year = SiteHelper::getAcademicYear($schoolId);

        $students = StudentAcademic::with('user.userprofile')
            ->where('standardLink_id', $class->id)
            ->whereHas('user', function ($q) {
                $q->where('status', 'active');
            })
            ->orderBy('roll_number')
            ->get();

        $today = now()->toDateString();
        $marks = Attendance::where('standardLink_id', $class->id)
            ->whereDate('date', $today)
            ->where('session', 'forenoon')
            ->get()
            ->keyBy('user_id');

        $present = $marks->whereIn('mark', ['present', 'late'])->count();
        $total = $students->count();
        $percent = $total > 0 ? round(($present / $total) * 100) : 0;

        $homeworks = Homework::where('standardLink_id', $class->id)
            ->where('teacher_id', Auth::id())
            ->orderByDesc('id')
            ->take(8)
            ->get();

        $remarks = Notes::where('school_id', $schoolId)
            ->where('entity_name', 'student_remark')
            ->whereIn('entity_id', $students->pluck('user_id'))
            ->orderByDesc('id')
            ->get()
            ->groupBy('entity_id');

        $term = AcademicTerm::where('school_id', $schoolId)
            ->where('academic_year_id', $year->id)
            ->where('is_current', true)
            ->first();

        $settings = SchoolAcademicSetting::where('school_id', $schoolId)
            ->where('academic_year_id', $year->id)
            ->first();

        $isFormTeacher = (int) $class->class_teacher_id === (int) Auth::id();

        return view('teacher.classroom.show', [
            'class' => $class,
            'students' => $students,
            'marks' => $marks,
            'percent' => $percent,
            'homeworks' => $homeworks,
            'remarks' => $remarks,
            'term' => $term,
            'settings' => $settings,
            'isFormTeacher' => $isFormTeacher,
            'today' => $today,
        ]);
    }

    public function storeAttendance(Request $request, $id)
    {
        $class = $this->authorizedClass($id);
        $request->validate([
            'date' => 'required|date',
            'session' => 'required|in:forenoon,afternoon',
            'marks' => 'required|array',
        ]);

        $schoolId = Auth::user()->school_id;
        $year = SiteHelper::getAcademicYear($schoolId);
        $allowed = ['present', 'absent', 'late', 'excused'];

        foreach ($request->input('marks') as $userId => $mark) {
            if (!in_array($mark, $allowed, true)) {
                continue;
            }

            $status = in_array($mark, ['present', 'late'], true) ? 1 : 0;

            Attendance::updateOrCreate(
                [
                    'school_id' => $schoolId,
                    'academic_year_id' => $year->id,
                    'standardLink_id' => $class->id,
                    'user_id' => $userId,
                    'date' => $request->date,
                    'session' => $request->session,
                ],
                [
                    'status' => $status,
                    'mark' => $mark,
                    'remarks' => $request->input('remarks.' . $userId),
                    'recorded_by' => Auth::id(),
                ]
            );
        }

        return redirect('/teacher/class/' . $class->id)
            ->with('successmessage', 'Attendance saved for ' . $class->StandardSection . '.');
    }

    public function storeRemark(Request $request, $id, $studentId)
    {
        $class = $this->authorizedClass($id);
        $request->validate([
            'notes' => 'required|string|max:500',
        ]);

        $belongs = StudentAcademic::where('standardLink_id', $class->id)
            ->where('user_id', $studentId)
            ->exists();
        if (!$belongs) {
            abort(403);
        }

        $note = new Notes();
        $note->school_id = Auth::user()->school_id;
        $note->notes = $request->notes;
        $note->entity_id = $studentId;
        $note->entity_name = 'student_remark';
        $note->created_by = Auth::id();
        $note->updated_by = Auth::id();
        $note->save();

        return redirect('/teacher/class/' . $class->id)
            ->with('successmessage', 'Remark saved.');
    }

    private function authorizedClass($id): StandardLink
    {
        $class = StandardLink::with('standard', 'section', 'teacher')->findOrFail($id);
        if (!Gate::allows('standardlink', $class)) {
            abort(403);
        }
        return $class;
    }

    private function formClass()
    {
        $year = SiteHelper::getAcademicYear(Auth::user()->school_id);
        return StandardLink::where([
            ['school_id', Auth::user()->school_id],
            ['academic_year_id', $year->id],
            ['class_teacher_id', Auth::id()],
        ])->first();
    }
}
