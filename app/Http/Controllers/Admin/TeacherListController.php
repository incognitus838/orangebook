<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Traits\MemberProcess;
use Illuminate\Http\Request;
use App\Traits\LogActivity;
use App\Traits\Common;
use App\Models\User;
use App\Models\AcademicYear;
use App\Helpers\SiteHelper;
use Exception;
use PDF;

class TeacherListController extends Controller
{
    use MemberProcess;
    use LogActivity;
    use Common;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function find(Request $request)
    {
      //
      return $this->TeacherFilter($request,Auth::user()->school_id,5);
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $schoolId = Auth::user()->school_id;
        $year = SiteHelper::getAcademicYear($schoolId);
        $classes = $year
            ? \App\Models\StandardLink::with('standard', 'section')
                ->where('school_id', $schoolId)
                ->where('academic_year_id', $year->id)
                ->get()
            : collect();

        $role = $request->query('role');
        $groups = match ($role) {
            'teaching' => [User::TEACHER_USERGROUP_ID],
            'nonteaching' => [User::LIBRARIAN_USERGROUP_ID, User::RECEPTIONIST_USERGROUP_ID, User::ACCOUNTANT_USERGROUP_ID, User::NON_TEACHING_USERGROUP_ID],
            default => [User::TEACHER_USERGROUP_ID, User::LIBRARIAN_USERGROUP_ID, User::RECEPTIONIST_USERGROUP_ID, User::ACCOUNTANT_USERGROUP_ID, User::NON_TEACHING_USERGROUP_ID],
        };

        $teachers = User::where('school_id', $schoolId)
            ->whereIn('usergroup_id', $groups)
            ->where('status', 'active')
            ->with([
                'standardLink.standard',
                'standardLink.section',
                'teacherlink' => function ($q) use ($year) {
                    if ($year) {
                        $q->where('academic_year_id', $year->id);
                    }
                },
                'teacherlink.subject',
                'teacherlink.standardLink.standard',
                'teacherlink.standardLink.section',
            ])
            ->when($request->filled('class_id'), function ($q) use ($request) {
                $classId = (int) $request->class_id;
                $q->where(function ($inner) use ($classId) {
                    $inner->whereHas('standardLink', fn ($s) => $s->where('id', $classId))
                        ->orWhereHas('teacherlink', fn ($t) => $t->where('standardLink_id', $classId));
                });
            })
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = $request->q;
                $q->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', '%'.$term.'%')
                        ->orWhere('email', 'like', '%'.$term.'%')
                        ->orWhere('mobile_no', 'like', '%'.$term.'%');
                });
            })
            ->orderBy('name')
            ->get();

        return view('/admin/teacher/index', [
            'teachers' => $teachers,
            'classes' => $classes,
            'classId' => $request->query('class_id'),
            'search' => $request->query('q'),
            'role' => $role,
            'year' => $year,
        ]);
    }

    public function destroy($name)
    {
        try
        {
            $user = User::where('name',$name)->first();
            $user->delete();

            $message=trans('messages.delete_success_msg',['module' => 'Teacher']);

            $ip= $this->getRequestIP();
            $this->doActivityLog(
                $user,
                Auth::user(),
                ['ip' => $ip, 'details' => $_SERVER['HTTP_USER_AGENT'] ],
                LOGNAME_DELETE_TEACHER,
                $message
            ); 
            \Session::put('successmessage',$message);
            return redirect('/admin/teachers');
        }
        catch(Exception $e)
        {
            //dd($e->getMessage());
        } 
    }
    public function idcard()
    {
   
        $academic = SiteHelper::getAcademicYear(Auth::user()->school_id);
        $teachers    = User::ByRole(5)->where('school_id',Auth::user()->school_id)->get();

        return view('/admin/teacher/idcard', compact('teachers','academic'));
    }

    public function printidcard()
    {
        $academic = SiteHelper::getAcademicYear(Auth::user()->school_id);
        $teachers    = User::ByRole(5)->where('school_id',Auth::user()->school_id)->get();
        $pdf = PDF::loadView('admin/teacher/idcard-print', compact('teachers','academic'));

        return $pdf->stream('result.pdf', array('Attachment'=>0)); 
    }
}