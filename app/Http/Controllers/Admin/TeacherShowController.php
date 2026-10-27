<?php

/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */

namespace App\Http\Controllers\Admin;

use App\Http\Resources\TeacherTimeTable as TeacherTimeTableResource;
use App\Http\Resources\TeacherClasses as TeacherClassesResource;
use App\Http\Resources\API\BookLending as BookLendingResource;
use App\Http\Resources\TeacherDetail as TeacherDetailResource;
use App\Http\Resources\LeaveHistory as LeaveHistoryResource;
use App\Http\Resources\ActivityLog as ActivityLogResource;
use App\Models\TeacherLeaveApplication;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Users\TeacherUser;
use Illuminate\Http\Request;
use App\Models\ActivityLog;
use App\Helpers\SiteHelper;
use App\Models\User;
use App\Models\AcademicYear;
use PDF;

class TeacherShowController extends Controller
{
  /**
   * Display the specified resource.
   *
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function showDetails($name)
  {
    //
    $users = TeacherUser::with('standardLink')->where('name', $name)->get();
    $users = TeacherDetailResource::collection($users);

    return $users;
  }

  /**
   * Display the specified resource.
   *
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function showTimetable($name)
  {
    //
    $users = TeacherUser::with('teacherlink')->where('name', $name)->get();
    if (count($users[0]['teacherlink']) > 0) {
      $users = TeacherTimeTableResource::collection($users);
    } else {
      $users = null;
    }
    return $users;
  }

  /**
   * Display the specified resource.
   *
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function showClasses($name)
  {
    //
    $user = TeacherUser::with('teacherlink')->where('name', $name)->first();
    $users = TeacherClassesResource::collection($user->teacherlink);

    return $users;
  }

  /**
   * Display the specified resource.
   *
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function showClassTeacher($name)
  {
    //
    $user = TeacherUser::with('standardLink')->where('name', $name)->first();
    $array['standard']  = $user->standardLink->StandardName;
    $array['section']   = $user->standardLink->section->name;

    return $array;
  }

  /**
   * Display the specified resource.
   *
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function showLeaveHistory($name)
  {
    //
    $user = TeacherUser::where('name', $name)->first();
    $school_id = Auth::user()->school_id;
    $academic_year = SiteHelper::getAcademicYear($school_id);
    $leave = TeacherLeaveApplication::where([
      ['user_id', $user->id],
      ['school_id', $school_id],
      ['academic_year_id', $academic_year->id]
    ])->paginate(5);
    $leave = LeaveHistoryResource::collection($leave);

    return $leave;
  }

  public function showActivity($name)
  {
    //
    $user = TeacherUser::with('userprofile')->where('name', $name)->first();
    // dd($user->members[0]['id']);
    $activitylog = ActivityLog::where('causer_id', $user->id)->orWhere('subject_id', $user->members[0]['id'])->paginate(5);
    $activitylog = ActivityLogResource::collection($activitylog);

    return $activitylog;
  }

  public function showActivityLog($name)
  {
    //
    $user = TeacherUser::with('userprofile')->where('name', $name)->first();
    $activitylog = ActivityLog::where('causer_id', $user->userprofile->id)->orWhere('causer_id', $user->members[0]['id'])->paginate(5);
    $activitylog = ActivityLogResource::collection($activitylog);

    return $activitylog;
  }

  /**
   * Display the specified resource.
   *
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function show($name)
  {
    $user = User::where('name', $name)
        ->where('school_id', Auth::user()->school_id)
        ->with(['standardLink.standard', 'standardLink.section', 'teacherlink.subject', 'teacherlink.standardLink.standard', 'teacherlink.standardLink.section'])
        ->firstOrFail();
    $year = SiteHelper::getAcademicYear(Auth::user()->school_id);
    $classes = $year
        ? \App\Models\StandardLink::with('standard', 'section')->where('school_id', Auth::user()->school_id)->where('academic_year_id', $year->id)->get()
        : collect();

    return view('/admin/teacher/show', ['user' => $user, 'classes' => $classes]);
  }

  public function updateSimple(Request $request, $name)
  {
    $user = User::where('name', $name)->where('school_id', Auth::user()->school_id)->firstOrFail();
    $data = $request->validate([
        'name' => 'required|string|max:80|unique:users,name,'.$user->id,
        'class_id' => 'nullable|integer',
        'email' => 'required|email|max:120|unique:users,email,'.$user->id,
        'phone' => 'nullable|string|max:20',
    ]);
    $user->name = $data['name'];
    $user->email = $data['email'];
    $user->mobile_no = $data['phone'] ?: $user->mobile_no;
    $user->save();
    $parts = preg_split('/\s+/', trim($data['name']), 2);
    $profile = \App\Models\Userprofile::where('user_id', $user->id)->first();
    if ($profile) {
        $profile->firstname = $parts[0];
        $profile->lastname = $parts[1] ?? $profile->lastname;
        $profile->save();
    }
    if (!empty($data['class_id'])) {
        $class = \App\Models\StandardLink::where('id', $data['class_id'])->where('school_id', Auth::user()->school_id)->firstOrFail();
        $class->class_teacher_id = $user->id;
        $class->save();
    }
    return redirect('/admin/teacher/show/'.rawurlencode($user->name))->with('successmessage', $user->name.' saved.');
  }

  public function showidcard($name)
  {

    $academic = SiteHelper::getAcademicYear(Auth::user()->school_id);

    $teacher = User::where('name', $name)->first();

    return view('/admin/teacher/showidcard', ['teacher' => $teacher, 'academic' => $academic]);
  }

  public function showprintidcard($name)
  {
    $academic = SiteHelper::getAcademicYear(Auth::user()->school_id);
    $teacher = User::where('name', $name)->first();

    $pdf = PDF::loadView('admin/teacher/show-idcardprint', ['teacher' => $teacher, 'academic' => $academic]);

    return $pdf->stream('result.pdf', array('Attachment' => 0));
  }

  public function showBookLent($name)
  {

    $teacher = User::with('lending')->where('name', $name)->first();

    $lent = BookLendingResource::collection($teacher->lending);

    return $lent;
  }
}
