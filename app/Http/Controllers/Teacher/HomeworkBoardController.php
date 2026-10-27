<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Services\SchoolBoardService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeworkBoardController extends Controller
{
    public function index(SchoolBoardService $board)
    {
        $classes = $board->teacherClasses(Auth::user());

        return view('crest.board.homework', [
            'layout' => 'layouts.teacher.layout',
            'works' => $board->homeworkForTeacher(Auth::user()),
            'classes' => $classes,
            'subjects' => $board->subjectsForClasses($classes->pluck('id')),
            'action' => url('/teacher/homeworks'),
            'canAdd' => $classes->isNotEmpty(),
        ]);
    }

    public function store(Request $request, SchoolBoardService $board)
    {
        $data = $request->validate([
            'class_id' => 'required|integer',
            'subject_id' => 'nullable|integer',
            'description' => 'required|string|max:2000',
            'submission_date' => 'required|date|after_or_equal:today',
        ]);
        $work = $board->addHomework(Auth::user(), $data);

        return redirect('/teacher/homeworks')
            ->with('successmessage', 'Homework set. Due ' . Carbon::parse($work->submission_date)->format('d M Y') . '.');
    }
}
