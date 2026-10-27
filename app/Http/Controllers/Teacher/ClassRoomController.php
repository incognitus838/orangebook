<?php

namespace App\Http\Controllers\Teacher;

use App\Helpers\SiteHelper;
use App\Http\Controllers\Controller;
use App\Models\AcademicTerm;
use App\Models\Attendance;
use App\Models\Homework;
use App\Models\HomeworkApproval;
use App\Models\Notes;
use App\Models\SchoolAcademicSetting;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\Teacherlink;
use Carbon\Carbon;
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

    public function show(Request $request, $id)
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

        $day = $request->query('date', now()->toDateString());
        $session = $request->query('session', 'forenoon');
        if (!in_array($session, ['forenoon', 'afternoon'], true)) {
            $session = 'forenoon';
        }

        $marks = Attendance::where('standardLink_id', $class->id)
            ->whereDate('date', $day)
            ->where('session', $session)
            ->get()
            ->keyBy('user_id');

        $present = $marks->whereIn('mark', ['present', 'late'])->count();
        $total = $students->count();
        $percent = $total > 0 ? round(($present / $total) * 100) : 0;

        $weekDays = collect();
        $cursor = Carbon::parse($day)->startOfDay();
        while ($weekDays->count() < 5) {
            if ($cursor->isWeekday()) {
                $weekDays->push($cursor->toDateString());
            }
            $cursor->subDay();
        }
        $weekDays = $weekDays->reverse()->values();

        $weekMarks = Attendance::where('standardLink_id', $class->id)
            ->where('session', $session)
            ->where(function ($q) use ($weekDays) {
                foreach ($weekDays as $d) {
                    $q->orWhereDate('date', $d);
                }
            })
            ->get()
            ->groupBy('user_id');

        $homeworks = Homework::with('subject')
            ->where('standardLink_id', $class->id)
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

        $subjectIds = Teacherlink::where('standardLink_id', $class->id);
        if (!$isFormTeacher) {
            $subjectIds->where('teacher_id', Auth::id());
        }
        $subjects = Subject::whereIn('id', $subjectIds->pluck('subject_id'))->orderBy('name')->get();

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
            'today' => $day,
            'session' => $session,
            'weekDays' => $weekDays,
            'weekMarks' => $weekMarks,
            'subjects' => $subjects,
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
            $note = trim((string) $request->input('remarks.' . $userId));
            if (in_array($mark, ['late', 'absent', 'excused'], true) && $note === '') {
                return back()->with('failmessage', 'Give a reason for late, absent or excused.')->withInput();
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
                    'remarks' => $note !== '' ? $note : null,
                    'recorded_by' => Auth::id(),
                ]
            );
        }

        return redirect('/teacher/class/' . $class->id . '?date=' . $request->date . '&session=' . $request->session)
            ->with('successmessage', 'Attendance saved for ' . $class->StandardSection . '.');
    }

    public function storeHomework(Request $request, $id)
    {
        $class = $this->authorizedClass($id);
        $data = $request->validate([
            'description' => 'required|string|max:2000',
            'submission_date' => 'required|date|after_or_equal:today',
            'subject_id' => 'nullable|integer',
        ]);
        $schoolId = Auth::user()->school_id;
        $year = SiteHelper::getAcademicYear($schoolId);

        $work = new Homework();
        $work->school_id = $schoolId;
        $work->academic_year_id = $year->id;
        $work->standardLink_id = $class->id;
        $work->subject_id = !empty($data['subject_id']) ? $data['subject_id'] : null;
        $work->teacher_id = Auth::id();
        $work->description = $data['description'];
        $work->date = now()->toDateString();
        $work->submission_date = $data['submission_date'];
        $work->status = 'publish';
        $work->created_by = Auth::id();
        $work->updated_by = Auth::id();
        $work->save();

        $approval = new HomeworkApproval();
        $approval->homework_id = $work->id;
        $approval->status = 'approved';
        $approval->approved_by = Auth::id();
        $approval->approved_at = now()->toDateString();
        $approval->comments = 'Set from My Class';
        $approval->save();

        return redirect('/teacher/class/' . $class->id)
            ->with('successmessage', 'Homework set. Due ' . Carbon::parse($work->submission_date)->format('d M Y') . '.');
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
