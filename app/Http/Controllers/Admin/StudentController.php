<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Admin;

use App\Http\Requests\UserProfileUpdateRequest;
use App\Http\Requests\UserProfileAddRequest;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Models\StudentParentLink;
use App\Traits\MemberProcess;
use App\Traits\RegisterUser;
use App\Models\StudentAcademic;
use App\Models\Users\StudentUser;
use App\Models\StandardLink;
use App\Models\Subscription;
use Illuminate\Http\Request;
use App\Helpers\SiteHelper;
use App\Traits\LogActivity;
use App\Models\ActivityLog;
use App\Models\Userprofile;
use App\Models\Standard;
use App\Traits\Common;
use App\Models\User;
use App\Models\Group;
use App\Services\LearnerEnrollmentService;
use Carbon\Carbon;
use Exception;
use Hash;

/**
 * Class StudentController
 *
 * Handles student management including
 * listing, creation, update, deletion,
 * attendance, academic mapping, and
 * blocked student handling.
 *
 * @package App\Http\Controllers\Admin
 */
class StudentController extends Controller
{
    use RegisterUser; 
    use MemberProcess;
    use LogActivity;
    use Common;
    
    /**
     * Filter and fetch students list.
     *
     * Applies standard-based default filtering
     * and returns filtered student collection.
     *
     * @param Request $request
     * @return mixed
     */
    public function find(Request $request)
    {
        //
        $school_id = Auth::user()->school_id;
        $academic_year = SiteHelper::getAcademicYear($school_id);

        $lowest_standard = Standard::where('school_id',$school_id)->orderBy('order')->first();

        $standard = StandardLink::where([['school_id',$school_id],['academic_year_id',$academic_year->id],['standard_id',$lowest_standard->id]])->first();

        if(count((array)\Request::getQueryString()) == 0)
        {
            $request['standard'] = $standard->id;
        }

        return $this->MemberFilter($request,Auth::user()->school_id,6,'active');
    }
    /**
     * Display student listing page.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $schoolId = Auth::user()->school_id;
        $year = SiteHelper::getAcademicYear($schoolId);
        $classes = $year
            ? StandardLink::with('standard', 'section')
                ->where('school_id', $schoolId)
                ->where('academic_year_id', $year->id)
                ->get()
            : collect();

        $students = User::where('school_id', $schoolId)
            ->where('usergroup_id', User::STUDENT_USERGROUP_ID)
            ->when($request->query('status') === 'blocked', fn ($q) => $q->where('status', '!=', 'active'), fn ($q) => $q->where('status', 'active'))
            ->with(['studentAcademicLatest.standardLink.standard', 'studentAcademicLatest.standardLink.section'])
            ->when($request->filled('class_id'), function ($q) use ($request) {
                $q->whereHas('studentAcademicLatest', fn ($s) => $s->where('standardLink_id', $request->class_id));
            })
            ->when($request->boolean('no_parent'), function ($q) {
                $q->whereDoesntHave('parents');
            })
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = $request->q;
                $q->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', '%'.$term.'%')
                        ->orWhere('email', 'like', '%'.$term.'%')
                        ->orWhere('mobile_no', 'like', '%'.$term.'%')
                        ->orWhere('registration_number', 'like', '%'.$term.'%');
                });
            })
            ->orderBy('name')
            ->paginate(50)
            ->withQueryString();

        return view('/admin/member/index', [
            'students' => $students,
            'classes' => $classes,
            'classId' => $request->query('class_id'),
            'search' => $request->query('q'),
            'noParent' => $request->boolean('no_parent'),
        ]);
    }

    /**
     * Show student creation form.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
      $schoolId = Auth::user()->school_id;
      $year = SiteHelper::getAcademicYear($schoolId);
      $classes = $year
          ? StandardLink::with('standard', 'section')->where('school_id', $schoolId)->where('academic_year_id', $year->id)->get()
          : collect();

      $school = Auth::user()->school;

      return view('/admin/member/create', [
          'classes' => $classes,
          'school' => $school,
      ]);
    }

    /**
     * Load supporting data for student creation.
     *
     * @return array
     */
    // public function member()
    // {
    //   $academic_year  = SiteHelper::getAcademicYear(Auth::user()->school_id);

    //   $array = [];
        
    //   $array['academic_year_id']  =   $academic_year->id;
    //   $array['countrylist']       =   SiteHelper::getCountries();
    //   $array['statelist']         =   SiteHelper::getStates();
    //   $array['citylist']          =   SiteHelper::getCities();
    //   $array['standardLinklist']  =   SiteHelper::getStandardLinkList(Auth::user()->school_id);
    //   $array['blood_groups']      =   SiteHelper::getBloodGroups();
    //   $array['castelist']         =   SiteHelper::getCasteList();
    //   $array['transportlist']     =   SiteHelper::getTransportList();
    //   $array['date_of_birth']     =   date('Y-m-d',strtotime('-4 years',strtotime(date('Y'))));
    //   $array['joining_date']      =   date('Y-m-d');
        
    //   return $array;
    // }
    public function member()
    {
        $academic_year = SiteHelper::getAcademicYear(Auth::user()->school_id);

        $standardlinks = collect(
            SiteHelper::getStandardLinkList(Auth::user()->school_id)
        )->map(function ($item) {

            $item['groups'] = Group::where('standardLink_id', $item['id'])
                ->select('id', 'group_name')
                ->get();

            return $item;
        });

        $array = [];

        $array['academic_year_id'] = $academic_year->id;
        $array['countrylist']      = SiteHelper::getCountries();
        $array['statelist']        = SiteHelper::getStates();
        $array['citylist']         = SiteHelper::getCities();

        // updated
        $array['standardLinklist'] = $standardlinks;

        $array['blood_groups']     = SiteHelper::getBloodGroups();
        $array['castelist']        = SiteHelper::getCasteList();
        $array['transportlist']    = SiteHelper::getTransportList();
        $array['date_of_birth']    = date('Y-m-d', strtotime('-4 years', strtotime(date('Y'))));
        $array['joining_date']     = date('Y-m-d');

        return $array;
    }

    /**
     * Validate student creation request.
     *
     * @param UserProfileAddRequest $request
     * @return void
     */
    public function validationUser(UserProfileAddRequest $request)
    {
      //
    }

    /**
     * Store newly created student.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $school = Auth::user()->school;
        $data = $request->validate([
            'first_name' => 'required|string|max:40',
            'last_name' => 'nullable|string|max:40',
            'other_names' => 'nullable|string|max:40',
            'gender' => 'nullable|in:male,female',
            'date_of_birth' => 'nullable|date',
            'class_id' => 'required|integer',
            'admission_no' => 'nullable|string|max:40',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:20',
            'parent_name' => 'nullable|string|max:80',
            'parent_email' => 'nullable|email',
            'parent_phone' => 'nullable|string|max:20',
            'relationship' => 'nullable|string|max:40',
        ]);
        $class = StandardLink::where('id', $data['class_id'])->where('school_id', $school->id)->firstOrFail();
        $result = app(LearnerEnrollmentService::class)->enroll($school, $data, $class);
        $slips = [LearnerEnrollmentService::slip($result['user'], $result['pin'], $class->StandardSection)];
        if ($result['parent'] && $result['parent_pin']) {
            $slips[] = LearnerEnrollmentService::slip($result['parent'], $result['parent_pin'], 'Parent');
        }
        session(['login_slips' => $slips]);

        if ($result['pin'] === '') {
            return redirect('/admin/students/'.$result['user']->id)->with('successmessage', $result['user']->name.' is now also in '.$class->StandardSection.'.');
        }

        return redirect('/admin/students/slips')->with('successmessage', $result['user']->name.' added to '.$class->StandardSection.'. Print the login slip now; the PIN is shown once.');
    }

    public function slips()
    {
        $slips = session('login_slips', []);
        $school = Auth::user()->school;

        return view('admin.member.slips', [
            'slips' => $slips,
            'school' => $school,
        ]);
    }

    /**
     * Fetch student data for edit API.
     *
     * @param string $name
     * @return array
     */
    public function editStudent($name)
    {
      //
      $user             = StudentUser::where('name',$name)->first();
      $userprofile      = Userprofile::where('user_id',$user->id)->first();
      $studentAcademic  = $user->studentAcademicLatest;
        
      $array = [];

      $array['firstname']                 = $userprofile->firstname;
      $array['lastname']                  = $userprofile->lastname;
      $array['date_of_birth']             = date('Y-m-d',strtotime($userprofile->date_of_birth));
      $array['gender']                    = $userprofile->gender;
      $array['blood_group']               = $userprofile->blood_group;
      $array['aadhar_number']             = $userprofile->aadhar_number==NULL ? '':$userprofile->aadhar_number;
      $array['city_id']                   = $userprofile->city_id;
      $array['state_id']                  = $userprofile->state_id;
      $array['country_id']                = $userprofile->country_id;
      $array['pincode']                   = $userprofile->pincode==NULL ? '':$userprofile->pincode;
      $array['birth_place']               = $userprofile->birth_place;
      $array['native_place']              = $userprofile->native_place;
      $array['mother_tongue']             = $userprofile->mother_tongue;
      $array['caste']                     = $userprofile->caste;
      $array['sub_caste']                 = $userprofile->sub_caste;
      $array['avatar']                    = $userprofile->AvatarPath;
      $array['notes']                     = $userprofile->notes;
      $array['registration_number']       = $user->registration_number==NULL ? $userprofile->registration_number:$user->registration_number;
      $array['EMIS_number']               = $userprofile->EMIS_number==NULL ? '':$userprofile->EMIS_number;
      $array['joining_date']              = $userprofile->joining_date==NULL ? '':date('Y-m-d',strtotime($userprofile->joining_date));

      $array['standardLink_id']           = $studentAcademic->standardLink_id;
      $array['roll_number']               = $studentAcademic->roll_number==NULL ? '':$studentAcademic->roll_number;
      $array['id_card_number']            = $studentAcademic->id_card_number==NULL ? '':$studentAcademic->id_card_number;
      $array['board_registration_number'] = $studentAcademic->board_registration_number==NULL ? '':$studentAcademic->board_registration_number;
      $array['mode_of_transport']         = $studentAcademic->mode_of_transport;
      $array['driver_name']               = $studentAcademic->transport_details['driver_name'];
      $array['driver_contact_number']     = $studentAcademic->transport_details['driver_contact_number'];
      $array['siblings']                  = $studentAcademic->siblings;
      $array['siblings_count']            = $studentAcademic->siblings_count;

      for($i = 0 ; $i < $studentAcademic->siblings_count ; $i++)
      {
        $array['sibling_details'][$i]['sibling_relation']       = $studentAcademic->sibling_details[$i]['sibling_relation'];
        $array['sibling_details'][$i]['sibling_name']           = $studentAcademic->sibling_details[$i]['sibling_name'];
        // $array['sibling_details'][$i]['sibling_date_of_birth']  = date('Y-m-d',strtotime($studentAcademic->sibling_details[$i]['sibling_date_of_birth']));
        $dateValue = $studentAcademic->sibling_details[$i]['sibling_date_of_birth'];

        $array['sibling_details'][$i]['sibling_date_of_birth'] =
            \Carbon\Carbon::parse(is_array($dateValue) ? $dateValue['date'] : $dateValue)
                ->format('Y-m-d');
        
        $array['sibling_details'][$i]['sibling_standard']       = $studentAcademic->sibling_details[$i]['sibling_standard'];
      }

      $array['countrylist']       =   SiteHelper::getCountries();
      $array['statelist']         =   SiteHelper::getStates();
      $array['citylist']          =   SiteHelper::getCities();
      $array['standardLinklist']  =   SiteHelper::getStandardLinkList(Auth::user()->school_id);
      $array['blood_groups']      =   SiteHelper::getBloodGroups();
      $array['castelist']         =   SiteHelper::getCasteList();
      $array['transportlist']     =   SiteHelper::getTransportList();
      $array['today']             =   date('Y-m-d');

      return $array;
    }

    /**
     * Show student edit form.
     *
     * @param string $name
     * @return \Illuminate\Http\Response
     */
    public function edit($name)
    {
      return redirect('/admin/student/show/'.rawurlencode($name));
    }

    /**
     * Validate student update request.
     *
     * @param UserProfileUpdateRequest $request
     * @param string $name
     * @return void
     */
    public function editValidationUser(UserProfileUpdateRequest $request,$name)
    {
      //
    }

    /**
     * Update student details.
     *
     * @param Request $request
     * @param string $name
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request,$name)
    {
      //
      try
      {
        $user = StudentUser::where('name',$name)->first();

        $userprofile = Userprofile::where('user_id',$user->id)->first();

        $school_id = Auth::user()->school_id;

        $academic_year = SiteHelper::getAcademicYear($school_id);
            
        if($request->hasFile('avatar'))
        { 
          $file = $request->file('avatar');
          $folder=Auth::user()->school->slug.'/member/avatar';
          $path = $this->uploadFile($folder,$file); 
        }
        else
        {
          $path= $userprofile->avatar;
        }

        $userprofile = $this->UpdateUser($request , $school_id , $academic_year->id ,$user->id , $path);

        $message=trans('messages.update_success_msg',['module' => 'Student']);

        $ip= $this->getRequestIP();
        $this->doActivityLog(
          $userprofile,
          Auth::user(),
          ['ip' => $ip, 'details' => $_SERVER['HTTP_USER_AGENT'] ],
          LOGNAME_EDIT_STUDENT,
          $message
        ); 
        \Session::put('successmessage',$message);
        return redirect()->back();
      }
      catch(Exception $e)
      {
        //dd($e->getMessage());
      } 
    }
    
    /**
     * Delete student and related records.
     *
     * @param string $name
     * @return \Illuminate\Http\Response
     */
    public function destroy($name)
    {
        try
      {
        $user = StudentUser::with('userprofile')->where('name',$name)->first();

         $studentacademic = StudentAcademic::where('user_id',$user->id);
       if($studentacademic!=null){
         $studentacademic->delete();
         }
         $studentparentlink = StudentParentLink::where('student_id',$user->id);
         if($studentparentlink!=null){
         $studentparentlink->delete();
         }
         $userprofile = Userprofile::where('user_id',$user->id);
         $userprofile->delete();
         $user->delete();


        $message=trans('messages.delete_success_msg',['module' => 'Student']);

        $ip= $this->getRequestIP();
        $this->doActivityLog(
          $user,
          Auth::user(),
          ['ip' => $ip, 'details' => $_SERVER['HTTP_USER_AGENT'] ],
          LOGNAME_DELETE_STUDENT,
          $message
        ); 
        \Session::put('successmessage',$message);
        return redirect('/admin/students');
      }
      catch(Exception $e)
      {
        //dd($e->getMessage());
      } 
    }
    
    /**
     * Display blocked students list.
     *
     * @return \Illuminate\Http\Response
     */
    public function blockedstudents()
    {
        return redirect('/admin/students?status=blocked');
    }
}
