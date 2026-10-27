<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */

namespace App\Http\Controllers\Admin;

use App\Http\Resources\ParentDetail as ParentDetailResource;
use App\Http\Resources\ActivityLog as ActivityLogResource;
use App\Http\Resources\Feedback as FeedbackResource;
use App\Http\Resources\Children as ChildrenResource;
use App\Http\Resources\User as UserResource;
use App\Http\Requests\ParentUpdateRequest;
use App\Http\Requests\ParentAddRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Http\Controllers\Controller;
use App\Models\StudentParentLink;
use App\Models\Users\ParentUser;
use App\Models\Qualification;
use App\Traits\MemberProcess;
use Illuminate\Http\Request;
use App\Traits\RegisterUser;
use App\Models\Subscription;
use App\Helpers\SiteHelper;
use App\Traits\LogActivity;
use App\Models\Userprofile;
use App\Models\ActivityLog;
use App\Models\Feedback;
use App\Traits\Common;
use App\Models\User;
use Exception;
use Log;

/**
 * Class ParentController
 *
 * Handles parent management including listing, creation,
 * viewing, editing, updating, deletion, and related resources
 * such as children, feedback, and activity logs.
 *
 * @package App\Http\Controllers\Admin
 */
class ParentController extends Controller
{
    use MemberProcess;
    use RegisterUser;
    use LogActivity;
    use Common;

    /**
     * Get filtered list of parents.
     *
     * @param \Illuminate\Http\Request $request
     * @return mixed
     */
    public function list(Request $request)
    {
        //
        return $this->ParentFilter($request, Auth::user()->school_id, 7);
    }

    /**
     * Display parent index page.
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        if (!Auth::user()->school->usesParents()) {
            return redirect('/admin/students');
        }
        $schoolId = Auth::user()->school_id;
        $year = SiteHelper::getAcademicYear($schoolId);
        $classes = $year
            ? \App\Models\StandardLink::with('standard', 'section')
                ->where('school_id', $schoolId)
                ->where('academic_year_id', $year->id)
                ->get()
                ->sortBy(fn ($c) => sprintf('%03d-%s', $c->standard->order ?? '99', $c->section->name ?? ''))
                ->values()
            : collect();
        $levels = \App\Models\Standard::where('school_id', $schoolId)->where('status', 1)->orderBy('order')->get();

        $query = User::where('school_id', $schoolId)
            ->where('usergroup_id', User::PARENT_USERGROUP_ID)
            ->where('status', 'active')
            ->with(['childUsers.studentAcademicLatest.standardLink.standard', 'childUsers.studentAcademicLatest.standardLink.section'])
            ->when($request->boolean('no_child'), fn ($q) => $q->whereDoesntHave('childUsers'))
            ->when($request->filled('class_id'), function ($q) use ($request) {
                $q->whereHas('childUsers.studentAcademicLatest', fn ($s) => $s->where('standardLink_id', $request->class_id));
            })
            ->when($request->filled('standard_id'), function ($q) use ($request) {
                $q->whereHas('childUsers.studentAcademicLatest.standardLink', fn ($s) => $s->where('standard_id', $request->standard_id));
            })
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = $request->q;
                $q->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', '%'.$term.'%')
                        ->orWhere('email', 'like', '%'.$term.'%')
                        ->orWhere('mobile_no', 'like', '%'.$term.'%');
                });
            });

        $sort = $request->query('sort', 'name');
        if ($sort === 'recent') {
            $query->orderByDesc('created_at');
        } else {
            $query->orderBy('name');
        }

        if ($request->query('export') === 'csv') {
            return $this->exportCsv($query->get());
        }

        $grouped = $request->query('view') === 'grouped';
        if ($grouped || $sort === 'class') {
            $all = $query->get();
            if ($sort === 'class') {
                $all = $all->sortBy(function ($parent) {
                    $orders = $parent->childUsers->map(function ($child) {
                        $link = optional($child->studentAcademicLatest)->standardLink;
                        return sprintf('%03d-%s', optional($link?->standard)->order ?? '99', optional($link?->section)->name ?? '');
                    })->sort();

                    return ($orders->first() ?? 'zzz').$parent->name;
                })->values();
            }
            if ($grouped) {
                $groups = $classes->map(function ($class) use ($all) {
                    $parents = $all->filter(function ($parent) use ($class) {
                        return $parent->childUsers->contains(fn ($child) => (int) optional($child->studentAcademicLatest)->standardLink_id === (int) $class->id);
                    })->values();

                    return ['class' => $class, 'parents' => $parents];
                });

                return view('/admin/parent/index', [
                    'parents' => $all,
                    'groups' => $groups,
                    'grouped' => true,
                    'classes' => $classes,
                    'levels' => $levels,
                    'search' => $request->query('q'),
                    'classId' => $request->query('class_id'),
                    'standardId' => $request->query('standard_id'),
                    'sort' => $sort,
                    'noChild' => $request->boolean('no_child'),
                ]);
            }
            $page = \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage();
            $parents = new \Illuminate\Pagination\LengthAwarePaginator(
                $all->forPage($page, 50)->values(),
                $all->count(),
                50,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        } else {
            $parents = $query->paginate(50)->withQueryString();
        }

        return view('/admin/parent/index', [
            'parents' => $parents,
            'groups' => collect(),
            'grouped' => false,
            'classes' => $classes,
            'levels' => $levels,
            'search' => $request->query('q'),
            'classId' => $request->query('class_id'),
            'standardId' => $request->query('standard_id'),
            'sort' => $sort,
            'noChild' => $request->boolean('no_child'),
        ]);
    }

    public function exportCsv($parents)
    {
        $out = "parent name,email,phone,child,class\n";
        foreach ($parents as $parent) {
            if ($parent->childUsers->isEmpty()) {
                $out .= $this->csvLine([$parent->name, $parent->email, $parent->mobile_no, '', '']);
                continue;
            }
            foreach ($parent->childUsers as $child) {
                $out .= $this->csvLine([
                    $parent->name,
                    $parent->email,
                    $parent->mobile_no,
                    $child->name,
                    optional(optional($child->studentAcademicLatest)->standardLink)->StandardSection,
                ]);
            }
        }

        return response($out, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="parents.csv"',
        ]);
    }

    public function importForm()
    {
        if (! Auth::user()->school->usesParents()) {
            return redirect('/admin/students');
        }

        return view('admin.parent.import');
    }

    public function importTemplate()
    {
        return response("parent_name,email,phone,child,relationship\n", 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="parents-template.csv"',
        ]);
    }

    public function importPreview(Request $request)
    {
        if (! Auth::user()->school->usesParents()) {
            return redirect('/admin/students');
        }
        $school = Auth::user()->school;
        $text = $request->hasFile('file') ? $request->file('file')->get() : (string) $request->input('grid', '');
        $lines = preg_split('/\r\n|\r|\n/', trim($text));
        if ($lines && isset($lines[0]) && stripos($lines[0], 'parent') !== false) {
            array_shift($lines);
        }
        $rows = [];
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $cols = preg_split('/\t|,/', $line);
            $childKey = trim($cols[3] ?? '');
            $child = User::where('school_id', $school->id)->where('usergroup_id', User::STUDENT_USERGROUP_ID)
                ->where(function ($q) use ($childKey) {
                    $q->where('registration_number', $childKey)->orWhereRaw('LOWER(name) = ?', [strtolower($childKey)]);
                })->first();
            $email = strtolower(trim($cols[1] ?? ''));
            $ok = $child && $email && filter_var($email, FILTER_VALIDATE_EMAIL);
            $rows[] = [
                'parent_name' => trim($cols[0] ?? ''),
                'parent_email' => $email,
                'parent_phone' => trim($cols[2] ?? ''),
                'child' => $childKey,
                'relationship' => trim($cols[4] ?? 'parent'),
                'student_id' => $child?->id,
                '_ok' => $ok,
                '_error' => $ok ? null : ($child ? 'Bad email' : 'Unknown child'),
            ];
        }
        session(['parent_import' => $rows]);

        return view('admin.parent.import_preview', ['rows' => $rows]);
    }

    public function importConfirm()
    {
        $school = Auth::user()->school;
        $rows = session('parent_import');
        if (! $rows) {
            return redirect('/admin/parents')->with('failmessage', 'That import expired.');
        }
        $enroll = app(\App\Services\LearnerEnrollmentService::class);
        $n = 0;
        foreach ($rows as $row) {
            if (empty($row['_ok']) || empty($row['student_id'])) {
                continue;
            }
            $student = User::where('school_id', $school->id)->where('id', $row['student_id'])->first();
            if (! $student) {
                continue;
            }
            $enroll->linkParent($school, $student, $row);
            $n++;
        }
        session()->forget('parent_import');

        return redirect('/admin/parents')->with('successmessage', $n.' parents linked.');
    }

    private function csvLine(array $cols): string
    {
        return implode(',', array_map(function ($v) {
            $v = (string) $v;

            return '"'.str_replace('"', '""', $v).'"';
        }, $cols))."\n";
    }

    /**
     * Show parent creation form.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $schoolId = Auth::user()->school_id;
        $students = User::where('school_id', $schoolId)->where('usergroup_id', 6)->where('status', 'active')->orderBy('name')->get();
        $refName = request('ref_name');
        $selectedId = null;
        if ($refName) {
            $selectedId = optional(User::where('school_id', $schoolId)->where('name', $refName)->first())->id;
        }
        return view('admin/parent/create', [
            'students' => $students,
            'selectedId' => $selectedId,
        ]);
    }

    public function storeSimple(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:80',
            'email' => 'required|email|max:120',
            'phone' => 'nullable|string|max:20',
            'student_id' => 'required|integer',
        ]);
        $schoolId = Auth::user()->school_id;
        $student = User::where('school_id', $schoolId)->where('usergroup_id', 6)->where('id', $data['student_id'])->firstOrFail();
        $pin = (string) random_int(100000, 999999);
        $parent = User::firstOrCreate(
            ['email' => $data['email']],
            [
                'school_id' => $schoolId,
                'usergroup_id' => User::PARENT_USERGROUP_ID,
                'name' => $data['name'],
                'mobile_no' => $data['phone'] ?: null,
                'password' => bcrypt($pin),
                'status' => 'active',
                'email_verified' => true,
                'email_verified_at' => now(),
            ]
        );
        if ((int) $parent->school_id !== (int) $schoolId) {
            return back()->with('failmessage', 'That email belongs to another school.')->withInput();
        }
        if ($data['phone']) {
            $parent->mobile_no = $data['phone'];
            $parent->save();
        }
        $parts = preg_split('/\s+/', trim($data['name']), 2);
        Userprofile::firstOrCreate(
            ['user_id' => $parent->id],
            ['school_id' => $schoolId, 'usergroup_id' => 7, 'firstname' => $parts[0], 'lastname' => $parts[1] ?? '', 'status' => 'active']
        );
        \App\Models\ParentProfile::firstOrCreate(
            ['user_id' => $parent->id],
            ['school_id' => $schoolId, 'relation' => 'guardian']
        );
        StudentParentLink::firstOrCreate(
            ['parent_id' => $parent->id, 'student_id' => $student->id],
            ['school_id' => $schoolId, 'status' => 1]
        );
        $msg = $parent->wasRecentlyCreated
            ? $parent->name.' can see '.$student->name.'. PIN '.$pin.' is shown once.'
            : $parent->name.' can see '.$student->name.'.';
        if ($parent->wasRecentlyCreated) {
            session(['login_slips' => [[
                'name' => $parent->name,
                'class' => 'Parent',
                'admission_no' => $parent->email,
                'pin' => $pin,
                'school_code' => Auth::user()->school->join_code,
                'org' => Auth::user()->school->name,
            ]]]);

            return redirect('/admin/students/slips')->with('successmessage', $msg);
        }

        return redirect('/admin/parents')->with('successmessage', $msg);
    }

    /**
     * Get parent add form related lists.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function addList(Request $request)
    {
        $array = [];

        $array['qualificationlist'] = SiteHelper::getQualifications();
        $array['standardLinklist']  = SiteHelper::getStandardLinkList(Auth::user()->school_id);

        if ($request->standardLink_id != null)
        {
            $parent = ParentUser::where('school_id', Auth::user()->school_id)
                          ->ByRole(7)
                          ->ByStandardLinkParentList($request->standardLink_id)
                          ->get();

            $array['parent'] = UserResource::collection($parent);
        }

        return $array;
    }

    /**
     * Validate parent creation request.
     *
     * @param \App\Http\Requests\ParentAddRequest $request
     * @return void
     */
    public function validationParent(ParentAddRequest $request)
    {
        //
    }

    /**
     * Store a newly created parent.
     *
     * @param \App\Http\Requests\ParentAddRequest $request
     * @return mixed
     */
    public function store(ParentAddRequest $request)
    {
        //
        try
        {
            $school_id = Auth::user()->school_id;
            $student_id = '';

            $user = $this->CreateParent($student_id, $request, $school_id, 7);

            $ip = $this->getRequestIP();
            $this->doActivityLog(
                $user,
                Auth::user(),
                ['ip' => $ip, 'details' => $_SERVER['HTTP_USER_AGENT']],
                LOGNAME_ADD_PARENT,
                trans('messages.add_success_msg', ['module' => 'Parent'])
            );

            if ($request->parent == 'add')
            {
                \Session::put('successmessage', trans('messages.add_success_msg', ['module' => 'Parent']));
                return redirect()->back();
            }
            else
            {
                $res['success'] = trans('messages.add_success_msg', ['module' => 'Parent']);
                return $res;
            }
        }
        catch (Exception $e)
        {
            Log::info($e->getMessage());
        }
    }

    /**
     * Display parent details.
     *
     * @param string $name
     * @return \Illuminate\View\View
     */
    public function show($name)
    {
        if (!Auth::user()->school->usesParents()) {
            return redirect('/admin/students');
        }
        $user = $this->findParent($name);
        $children = $user->childUsers()->with('studentAcademicLatest.standardLink.standard', 'studentAcademicLatest.standardLink.section')->orderBy('users.name')->get();
        $linkedIds = $children->pluck('id');
        $students = User::where('school_id', Auth::user()->school_id)
            ->where('usergroup_id', User::STUDENT_USERGROUP_ID)
            ->where('status', 'active')
            ->whereNotIn('id', $linkedIds)
            ->orderBy('name')
            ->get();

        return view('/admin/parent/show', [
            'user' => $user,
            'children' => $children,
            'students' => $students,
        ]);
    }

    public function updateSimple(Request $request, $name)
    {
        if (!Auth::user()->school->usesParents()) {
            return redirect('/admin/students');
        }
        $user = $this->findParent($name);
        $data = $request->validate([
            'name' => 'required|string|max:80|unique:users,name,'.$user->id,
            'email' => 'required|email|max:120|unique:users,email,'.$user->id,
            'phone' => 'nullable|string|max:20',
        ]);
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->mobile_no = $data['phone'] ?: $user->mobile_no;
        $user->save();
        $this->syncProfileName($user, $data['name']);
        return redirect('/admin/parent/show/'.rawurlencode($user->name))->with('successmessage', $user->name.' saved.');
    }

    public function linkChild(Request $request, $name)
    {
        if (!Auth::user()->school->usesParents()) {
            return redirect('/admin/students');
        }
        $user = $this->findParent($name);
        $data = $request->validate([
            'student_id' => 'required|integer',
        ]);
        $student = User::where('school_id', Auth::user()->school_id)
            ->where('usergroup_id', User::STUDENT_USERGROUP_ID)
            ->where('id', $data['student_id'])
            ->firstOrFail();
        $link = StudentParentLink::withTrashed()
            ->where('parent_id', $user->id)
            ->where('student_id', $student->id)
            ->first();
        if ($link) {
            if ($link->trashed()) {
                $link->restore();
            }
            $link->status = 1;
            $link->save();
        } else {
            StudentParentLink::create([
                'school_id' => Auth::user()->school_id,
                'parent_id' => $user->id,
                'student_id' => $student->id,
                'status' => 1,
            ]);
        }
        return redirect('/admin/parent/show/'.rawurlencode($user->name))->with('successmessage', $user->name.' can see '.$student->name.'.');
    }

    public function unlinkChild($name, $student)
    {
        if (!Auth::user()->school->usesParents()) {
            return redirect('/admin/students');
        }
        $user = $this->findParent($name);
        $link = StudentParentLink::where('parent_id', $user->id)
            ->where('student_id', $student)
            ->where('school_id', Auth::user()->school_id)
            ->firstOrFail();
        $studentName = optional(User::find($student))->name ?: 'child';
        $link->delete();
        return redirect('/admin/parent/show/'.rawurlencode($user->name))->with('successmessage', $studentName.' unlinked from '.$user->name.'.');
    }

    private function findParent($name): User
    {
        return User::where('name', $name)
            ->where('school_id', Auth::user()->school_id)
            ->where('usergroup_id', User::PARENT_USERGROUP_ID)
            ->firstOrFail();
    }

    private function syncProfileName(User $user, string $name): void
    {
        $parts = preg_split('/\s+/', trim($name), 2);
        $profile = Userprofile::where('user_id', $user->id)->first();
        if ($profile) {
            $profile->firstname = $parts[0];
            $profile->lastname = $parts[1] ?? $profile->lastname;
            $profile->save();
        }
    }

    /**
     * Display children of a parent.
     *
     * @param string $name
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function showChildren($name)
    {
        //
        $parent = ParentUser::where('name', $name)->first();
        return ChildrenResource::collection($parent->children);
    }

    /**
     * Display feedback conversations for a parent.
     *
     * @param string $name
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function showFeedbacks($name)
    {
        //
        $parent = ParentUser::where('name', $name)->first();
        $conversation = Feedback::where('parent_id', $parent->id)->get();

        return FeedbackResource::collection($conversation);
    }

    /**
     * Display activity logs of a parent.
     *
     * @param string $name
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function showActivityLog($name)
    {
        //
        $user = ParentUser::with('userprofile')->where('name', $name)->first();

        if (Gate::allows('member', $user))
        {
            $activitylog = ActivityLog::where('subject_id', $user->id)->paginate(5);
            return ActivityLogResource::collection($activitylog);
        }
        else
        {
            abort(403);
        }
    }

    /**
     * Get parent data for edit form.
     *
     * @param string $name
     * @return array
     */
    public function editList($name)
    {
        //
        $user = ParentUser::where('name', $name)->first();
        $userprofile = Userprofile::where('user_id', $user->id)->first();
        $parentprofile = $user->getParentDetails();

        $array = [];

        $array['firstname']          = $userprofile->firstname;
        $array['lastname']           = $userprofile->lastname;
        $array['alternate_no']       = $userprofile->alternate_no ?? '';
        $array['profession']         = $parentprofile['profession'] ?? '';
        $array['sub_occupation']     = $parentprofile['sub_occupation'] ?? '';
        $array['designation']        = $parentprofile['designation'] ?? '';
        $array['organization_name']  = $parentprofile['organization_name'] ?? '';
        $array['official_address']   = $parentprofile['official_address'] ?? '';
        $array['annual_income']      = $parentprofile['annual_income'] ?? '';
        $array['relation']           = $parentprofile['relation'] ?? '';
        $array['qualification_id']   = $parentprofile['qualification_id'] ?? '';
        $array['qualification_name'] = $parentprofile['qualification_name'] ?? '';
        $array['qualificationlist']  = SiteHelper::getQualifications();

        return $array;
    }

    /**
     * Show parent edit form.
     *
     * @param string $name
     * @return \Illuminate\View\View
     */
    public function edit($name)
    {
        return redirect('/admin/parent/show/'.rawurlencode($name));
    }

    /**
     * Validate parent update request.
     *
     * @param \App\Http\Requests\ParentUpdateRequest $request
     * @param string $name
     * @return void
     */
    public function editValidationUser(ParentUpdateRequest $request, $name)
    {
        //
    }

    /**
     * Update parent details.
     *
     * @param \App\Http\Requests\ParentUpdateRequest $request
     * @param string $name
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(ParentUpdateRequest $request, $name)
    {
        //
        try
        {
            $user = ParentUser::where('name', $name)->first();
            $school_id = Auth::user()->school_id;

            $userprofile = $this->UpdateParent('', $request, $school_id, $user->id);

            $ip = $this->getRequestIP();
            $this->doActivityLog(
                $userprofile,
                Auth::user(),
                ['ip' => $ip, 'details' => $_SERVER['HTTP_USER_AGENT']],
                LOGNAME_EDIT_PARENT,
                trans('messages.update_success_msg', ['module' => 'Parent'])
            );

            \Session::put('successmessage', trans('messages.update_success_msg', ['module' => 'Parent']));
            return redirect()->back();
        }
        catch (Exception $e)
        {
            Log::info($e->getMessage());
        }
    }

    /**
     * Delete a parent.
     *
     * @param string $name
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($name)
    {
        \DB::beginTransaction();

        try
        {
            $user = ParentUser::where('name', $name)->first();
            $studentparentlink = StudentParentLink::where('parent_id', $user->id)->first();

            if ($studentparentlink)
            {
                $studentparentlink->delete();
            }

            $userprofile = Userprofile::where('user_id', $user->id)->first();
            $userprofile->delete();
            $user->delete();

            $message = trans('messages.delete_success_msg', ['module' => 'Parent']);

            $ip = $this->getRequestIP();
            $this->doActivityLog(
                $user,
                Auth::user(),
                ['ip' => $ip, 'details' => $_SERVER['HTTP_USER_AGENT']],
                LOGNAME_DELETE_PARENT,
                $message
            );

            \Session::put('successmessage', $message);
            \DB::commit();

            return redirect('/admin/parents');
        }
        catch (Exception $e)
        {
            \DB::rollBack();
        }
    }
}
