<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Admin;

use App\Http\Requests\TeacherQualificationAddRequest;
use App\Http\Requests\TeacherAddressAddRequest;
use App\Http\Requests\TeacherProfileAddRequest;
use App\Http\Requests\TeacherAvatarAddRequest;
use App\Http\Requests\TeacherNoteAddRequest;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Traits\RegisterUser;
use App\Models\Subscription;
use Illuminate\Http\Request;
use App\Helpers\SiteHelper;
use App\Traits\LogActivity;
use App\Models\Userprofile;
use App\Models\TeacherProfile;
use App\Traits\Common;
use App\Models\User;
use Exception;

class TeacherAddController extends Controller
{ 
    use RegisterUser; 
    use LogActivity;
    use Common;

    public function member()
    {
      $school_id      = Auth::user()->school_id;
      $academic_year  = SiteHelper::getAcademicYear($school_id);
      $teacher_count  = User::where([['school_id',$school_id],['usergroup_id',5]])->count();
      $date_of_birth  = date('Y-m-d',strtotime('-25 years',strtotime(date('Y'))));

      $array = [];

      $array['academic_year_id']  =   $academic_year->id;
      $array['countrylist']       =   SiteHelper::getCountries();
      $array['statelist']         =   SiteHelper::getStates();
      $array['citylist']          =   SiteHelper::getCities();
      $array['qualificationlist'] =   SiteHelper::getAdditionalCertificates();
      $array['pglist']            =   SiteHelper::getPGList();
      $array['uglist']            =   SiteHelper::getUGList();
      $array['date_of_birth']     =   $date_of_birth;
      $array['employee_id']       =   $this->employeenumber();
      $array['designationlist']   =   SiteHelper::getTeachingDesignations();
      $array['nonteachinglist']   =   SiteHelper::getNonTeachingDesignations();
      $array['blood_groups']      =   SiteHelper::getBloodGroups();
      $array['maritalList']       =   SiteHelper::getMaritalList();
      $array['HODList']           =   SiteHelper::getHODList($school_id);
      $array['principalList']     =   SiteHelper::getPrincipalList($school_id);
        
      return $array;
    }

    protected function employeenumber()
    {
        $employee_id;
        if(count(TeacherProfile::where([['school_id',Auth::user()->school_id],['employee_id','!=',null]])->get())<=0)
        {
           $employee_id="EMP001";
        }
        else{
          $employee_number=TeacherProfile::where([['school_id',Auth::user()->school_id],['employee_id','!=',null]])->orderBy("id","DESC")->take(1)->first()->employee_id;
          $employee_id=++$employee_number;
        } 
        return $employee_id;
        
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
      $schoolId = Auth::user()->school_id;
      $year = SiteHelper::getAcademicYear($schoolId);
      $classes = $year
          ? \App\Models\StandardLink::with('standard', 'section')->where('school_id', $schoolId)->where('academic_year_id', $year->id)->get()
          : collect();

      return view('/admin/teacher/create', ['classes' => $classes]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function validationProfile(TeacherProfileAddRequest $request)
    {
      //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function validationAvatar(TeacherAvatarAddRequest $request)
    {
      //
      try
      {
        $image_parts    = explode(";base64,",$request->avatar);
        $image_type_aux = explode("image/",$image_parts[0]);
        $image_type     = $image_type_aux[1];
        $image_base64   = base64_decode($image_parts[1]);
        $location       = Auth::user()->school->slug.'/uploads/admin/teacher/avatar/';
        $file           =  uniqid() .'.jpg';
        $upload_path    = $location.$file;
        $this->putContents($upload_path, $image_base64);

        \Session::put('avatar_path',$upload_path);
      }
      catch(Exception $e)
      {
        //dd($e->getMessage());
      }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function validationQualification(TeacherQualificationAddRequest $request)
    {
      //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function validationNote(TeacherNoteAddRequest $request)
    {
      //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function validationAddress(TeacherAddressAddRequest $request)
    {
      //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, \App\Services\SchoolJoinService $join)
    {
        $data = $request->validate([
            'name' => 'required|string|max:80',
            'email' => 'required|email',
            'phone' => 'nullable|string|max:20',
            'role' => 'nullable|in:teaching,nonteaching',
            'class_id' => 'nullable|integer',
            'subjects' => 'nullable|string|max:240',
        ]);
        $school = Auth::user()->school;
        if (User::where('email', $data['email'])->exists()) {
            return back()->with('failmessage', 'That email already has a login.')->withInput();
        }
        $classId = null;
        if (! empty($data['class_id'])) {
            $classId = \App\Models\StandardLink::where('id', $data['class_id'])->where('school_id', $school->id)->value('id');
        }
        $join->inviteStaff(Auth::user(), [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'usergroup_id' => ($data['role'] ?? 'teaching') === 'nonteaching' ? User::NON_TEACHING_USERGROUP_ID : User::TEACHER_USERGROUP_ID,
            'class_id' => $classId,
            'subjects' => array_values(array_filter(array_map('trim', preg_split('/[;,]/', (string) ($data['subjects'] ?? ''))))),
        ]);

        return redirect('/admin/teachers')->with('successmessage', $data['name'].' has an invite. They set their own password from the mail.');
    }
}
