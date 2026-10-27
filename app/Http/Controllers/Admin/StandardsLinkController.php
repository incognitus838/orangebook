<?php

/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */

namespace App\Http\Controllers\Admin;

use App\Http\Resources\StandardLink as StandardLinkResource;
use App\Http\Resources\Teacher as TeacherResource;
use App\Http\Requests\StandardDetailUpdateRequest;
use App\Http\Requests\StandardDetailRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Models\Users\TeacherUser;
use App\Traits\AcademicProcess;
use Illuminate\Http\Request;
use App\Models\ExamSchedule;
use App\Models\AcademicYear;
use App\Models\StandardLink;
use App\Traits\LogActivity;
use App\Models\Teacherlink;
use App\Models\TempTimetable;
use App\Helpers\SiteHelper;
use App\Models\Attendance;
use App\Models\FeePayment;
use App\Models\Timetable;
use App\Models\Standard;
use App\Models\Subject;
use App\Models\Section;
use App\Models\Events;
use App\Traits\Common;
use App\Models\User;
use App\Models\Fee;
use App\Models\SubjectCatalog;
use App\Services\ClassStructureService;
use App\Support\LevelPresets;
use Carbon\Carbon;
use Exception;
use PDF;
use Log;
use DB;
use Illuminate\Validation\ValidationException;

/**
 * Class StandardsLinkController
 *
 * Manages standard–section links including
 * class configuration, subject assignment,
 * teacher mapping, student ID cards, and
 * lifecycle operations for academic standards.
 *
 * @package App\Http\Controllers\Admin
 */
class StandardsLinkController extends Controller
{
    use AcademicProcess;
    use LogActivity;
    use Common;

    /**
     * Display a listing of standard links.
     *
     * Lists all standards with sections for the
     * current academic year ordered by standard
     * and section.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $schoolId = Auth::user()->school_id;
        $year = SiteHelper::getAcademicYear($schoolId);
        $orderIds = Standard::where('school_id', $schoolId)->orderBy('order')->pluck('id');

        $classes = collect();
        if ($year && $orderIds->isNotEmpty()) {
            $classes = StandardLink::with(['standard', 'section', 'teacher', 'teacherlink.subject', 'teacherlink.teacher'])
                ->where('school_id', $schoolId)
                ->where('academic_year_id', $year->id)
                ->orderByRaw('FIELD(standard_id,' . $orderIds->implode(',') . ')')
                ->orderBy('section_id')
                ->get();
        }

        $counts = $classes->isEmpty()
            ? collect()
            : \App\Models\StudentAcademic::whereIn('standardLink_id', $classes->pluck('id'))
                ->selectRaw('standardLink_id, count(*) as n')
                ->groupBy('standardLink_id')
                ->pluck('n', 'standardLink_id');

        $subjects = $year
            ? Subject::where('school_id', $schoolId)->where('academic_year_id', $year->id)->orderBy('name')->get()->unique('name')->values()
            : collect();

        $tab = in_array($request->query('tab'), ['classes', 'levels', 'subjects'], true)
            ? $request->query('tab')
            : 'classes';
        $school = Auth::user()->school;
        $catalog = $year
            ? SubjectCatalog::where('school_id', $schoolId)->where('academic_year_id', $year->id)->where('status', 1)->orderBy('name')->get()
            : collect();

        return view('/admin/school/standardlinks/index', [
            'classes' => $classes,
            'counts' => $counts,
            'standards' => Standard::where('school_id', $schoolId)->orderBy('order')->get(),
            'sections' => Section::where('school_id', $schoolId)->orderBy('name')->get(),
            'teachers' => User::where('school_id', $schoolId)->whereIn('usergroup_id', [5, 13])->where('status', 'active')->orderBy('name')->get(),
            'subjects' => $subjects,
            'catalog' => $catalog,
            'tab' => $tab,
            'school' => $school,
            'presets' => LevelPresets::variants($school),
        ]);
    }

    public function storeSimple(Request $request)
    {
        $data = $request->validate([
            'standard_id' => 'required|integer',
            'section_ids' => 'required|array|min:1',
            'section_ids.*' => 'integer',
            'class_teacher_id' => 'nullable|integer',
        ]);
        $school = Auth::user()->school;
        $teacherId = $data['class_teacher_id'] ?? null;
        if ($teacherId) {
            User::where('id', $teacherId)->where('school_id', $school->id)->where('usergroup_id', 5)->firstOrFail();
        }
        try {
            $created = app(ClassStructureService::class)->addClasses($school, (int) $data['standard_id'], $data['section_ids'], $teacherId ? (int) $teacherId : null);
        } catch (ValidationException $e) {
            return back()->with('failmessage', collect($e->errors())->flatten()->first())->withInput();
        }
        $names = collect($created)->map->StandardSection->implode(', ');

        return redirect('/admin/standardlinks')->with('successmessage', $names.' added.');
    }

    public function assignTeacher(Request $request, $id)
    {
        $data = $request->validate([
            'class_teacher_id' => 'nullable|integer',
        ]);
        $class = StandardLink::where('id', $id)->where('school_id', Auth::user()->school_id)->firstOrFail();
        $teacherId = $data['class_teacher_id'] ?? null;
        if ($teacherId) {
            User::where('id', $teacherId)
                ->where('school_id', Auth::user()->school_id)
                ->where('usergroup_id', 5)
                ->firstOrFail();
        }
        $class->class_teacher_id = $teacherId;
        $class->save();
        return redirect('/admin/standardlinks')->with('successmessage', $class->StandardSection . ' updated.');
    }

    public function storeLevel(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:40', 'is_entry' => 'nullable|boolean']);
        try {
            app(ClassStructureService::class)->addLevel(Auth::user()->school, $data['name'], (bool) ($data['is_entry'] ?? false));
        } catch (ValidationException $e) {
            return back()->with('failmessage', collect($e->errors())->flatten()->first())->withInput();
        }
        return redirect('/admin/standardlinks?tab=levels')->with('successmessage', $data['name'].' added.');
    }

    public function renameLevel(Request $request, $id)
    {
        $data = $request->validate(['name' => 'required|string|max:40', 'is_entry' => 'nullable|boolean']);
        $level = Standard::where('id', $id)->where('school_id', Auth::user()->school_id)->firstOrFail();
        $level->name = $data['name'];
        $level->is_entry = (bool) ($data['is_entry'] ?? false);
        $level->save();
        return redirect('/admin/standardlinks?tab=levels')->with('successmessage', $level->name.' saved.');
    }

    public function moveLevel($id, $dir)
    {
        $level = Standard::where('id', $id)->where('school_id', Auth::user()->school_id)->firstOrFail();
        app(ClassStructureService::class)->moveLevel($level, $dir === 'up' ? -1 : 1);
        return redirect('/admin/standardlinks?tab=levels');
    }

    public function archiveLevel($id)
    {
        $level = Standard::where('id', $id)->where('school_id', Auth::user()->school_id)->firstOrFail();
        try {
            app(ClassStructureService::class)->archiveLevel($level);
        } catch (ValidationException $e) {
            return back()->with('failmessage', collect($e->errors())->flatten()->first());
        }
        return redirect('/admin/standardlinks?tab=levels')->with('successmessage', $level->name.' archived.');
    }

    public function storeArm(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:40']);
        try {
            app(ClassStructureService::class)->addArm(Auth::user()->school, $data['name']);
        } catch (ValidationException $e) {
            return back()->with('failmessage', collect($e->errors())->flatten()->first())->withInput();
        }
        return redirect('/admin/standardlinks?tab=levels')->with('successmessage', $data['name'].' added.');
    }

    public function renameArm(Request $request, $id)
    {
        $data = $request->validate(['name' => 'required|string|max:40']);
        $arm = Section::where('id', $id)->where('school_id', Auth::user()->school_id)->firstOrFail();
        $arm->name = $data['name'];
        $arm->save();
        return redirect('/admin/standardlinks?tab=levels')->with('successmessage', $arm->name.' saved.');
    }

    public function archiveArm($id)
    {
        $arm = Section::where('id', $id)->where('school_id', Auth::user()->school_id)->firstOrFail();
        $arm->status = 0;
        $arm->save();
        return redirect('/admin/standardlinks?tab=levels')->with('successmessage', $arm->name.' archived.');
    }

    public function applyPreset(Request $request)
    {
        $data = $request->validate(['variant' => 'nullable|string']);
        $n = app(ClassStructureService::class)->applyPreset(Auth::user()->school, $data['variant'] ?? 'default');
        return redirect('/admin/standardlinks?tab=levels')->with('successmessage', $n === 0 ? 'Those levels are already on the roll.' : $n.' added from the preset.');
    }

    public function copyYear()
    {
        try {
            $n = app(ClassStructureService::class)->copyFromLastYear(Auth::user()->school);
        } catch (ValidationException $e) {
            return back()->with('failmessage', collect($e->errors())->flatten()->first());
        }
        return redirect('/admin/standardlinks')->with('successmessage', 'Copied '.$n.' class records from last year.');
    }

    public function storeCatalog(Request $request)
    {
        $data = $request->validate([
            'name' => 'nullable|string|max:80',
            'code' => 'nullable|string|max:16',
            'type' => 'nullable|in:core,elective',
            'bulk' => 'nullable|string',
        ]);
        $service = app(ClassStructureService::class);
        $school = Auth::user()->school;
        $names = [];
        if (trim((string) ($data['bulk'] ?? '')) !== '') {
            $names = preg_split('/\r\n|\r|\n/', $data['bulk']);
        } elseif (! empty($data['name'])) {
            $names = [$data['name']];
        }
        $added = 0;
        foreach ($names as $name) {
            $name = trim($name);
            if ($name === '') {
                continue;
            }
            try {
                $service->addCatalogSubject($school, $name, $data['code'] ?? null, $data['type'] ?? 'core');
                $added++;
            } catch (ValidationException $e) {
                continue;
            }
        }
        return redirect('/admin/standardlinks?tab=subjects')->with('successmessage', $added.' subject'.($added === 1 ? '' : 's').' added.');
    }

    public function applyCatalog(Request $request)
    {
        $data = $request->validate([
            'catalog_ids' => 'required|array|min:1',
            'catalog_ids.*' => 'integer',
            'class_ids' => 'required|array|min:1',
            'class_ids.*' => 'integer',
            'teacher_id' => 'nullable|integer',
        ]);
        $made = app(ClassStructureService::class)->applySubjects(
            Auth::user()->school,
            $data['catalog_ids'],
            $data['class_ids'],
            $data['teacher_id'] ?? null
        );
        return redirect('/admin/standardlinks?tab=subjects')->with('successmessage', $made.' class subject links created.');
    }

    public function archiveCatalog($id)
    {
        $year = SiteHelper::getAcademicYear(Auth::user()->school_id);
        $row = SubjectCatalog::where('id', $id)->where('school_id', Auth::user()->school_id)->where('academic_year_id', $year->id)->firstOrFail();
        $row->status = 0;
        $row->save();
        return redirect('/admin/standardlinks?tab=subjects')->with('successmessage', $row->name.' archived.');
    }

    public function assignSubjectTeacher(Request $request, $id, $linkId)
    {
        $data = $request->validate(['teacher_id' => 'nullable|integer']);
        $class = StandardLink::where('id', $id)->where('school_id', Auth::user()->school_id)->firstOrFail();
        $link = Teacherlink::where('id', $linkId)->where('standardLink_id', $class->id)->firstOrFail();
        if (! empty($data['teacher_id'])) {
            User::where('id', $data['teacher_id'])->where('school_id', Auth::user()->school_id)->where('usergroup_id', 5)->firstOrFail();
        }
        $link->teacher_id = $data['teacher_id'] ?: null;
        $link->save();
        return redirect('/admin/standardlinks')->with('successmessage', 'Teacher updated on '.$class->StandardSection.'.');
    }

    public function addSubject(Request $request, $id)
    {
        $data = $request->validate([
            'subject_name' => 'required|string|max:80',
            'teacher_id' => 'nullable|integer',
        ]);
        $schoolId = Auth::user()->school_id;
        $year = SiteHelper::getAcademicYear($schoolId);
        $class = StandardLink::where('id', $id)->where('school_id', $schoolId)->firstOrFail();
        $teacherId = $data['teacher_id'] ?? null;
        if ($teacherId) {
            User::where('id', $teacherId)->where('school_id', $schoolId)->where('usergroup_id', 5)->firstOrFail();
        }
        $name = trim($data['subject_name']);
        $subject = Subject::where('school_id', $schoolId)
            ->where('academic_year_id', $year->id)
            ->where('standard_id', $class->standard_id)
            ->where('section_id', $class->section_id)
            ->whereRaw('LOWER(name) = ?', [strtolower($name)])
            ->first();
        if (!$subject) {
            $subject = Subject::create([
                'school_id' => $schoolId,
                'academic_year_id' => $year->id,
                'standard_id' => $class->standard_id,
                'section_id' => $class->section_id,
                'name' => $name,
                'code' => strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $name).'SUB', 0, 8)),
                'type' => 'core',
                'status' => 1,
            ]);
        }
        $exists = Teacherlink::where('standardLink_id', $class->id)->where('subject_id', $subject->id)->exists();
        if ($exists) {
            return redirect('/admin/standardlinks')->with('failmessage', $subject->name.' is already on '.$class->StandardSection.'.');
        }
        Teacherlink::create([
            'school_id' => $schoolId,
            'academic_year_id' => $year->id,
            'standardLink_id' => $class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacherId,
        ]);
        return redirect('/admin/standardlinks')->with('successmessage', $subject->name.' added to '.$class->StandardSection.'.');
    }

    public function removeSubject($id, $linkId)
    {
        $class = StandardLink::where('id', $id)->where('school_id', Auth::user()->school_id)->firstOrFail();
        $link = Teacherlink::where('id', $linkId)->where('standardLink_id', $class->id)->firstOrFail();
        $label = optional($link->subject)->name ?? 'Subject';
        $link->delete();
        return redirect('/admin/standardlinks')->with('successmessage', $label.' removed from '.$class->StandardSection.'.');
    }

    /**
     * Fetch required data for standard link creation.
     *
     * Provides academic year, standards, sections,
     * subjects, teachers, and existing standard links.
     *
     * @return array
     */
    public function list()
    {
        $array = [];

        $academic_year = SiteHelper::getAcademicYear(Auth::user()->school_id);

        $subjectlist = Subject::where([
            ['school_id', Auth::user()->school_id],
            ['academic_year_id', $academic_year->id],
            ['type', '!=', 'exam']
        ])->get()->groupBy(['standard_id', 'section_id']);

        // $standardlist = DB::table('standards')
        //     ->where('school_id', Auth::user()->school_id)
        //     ->orderByRaw('FIELD(name,"prekg","lkg","ukg","1","2","3","4","5","6","7","8","9","10","11","12")')
        //     ->get();
        $standardlist = Standard::active()
            ->where('school_id', Auth::user()->school_id)
            ->orderBy('id', 'ASC')
            ->get();

        $sectionlist = Section::where('school_id', Auth::user()->school_id)
            ->orderBy('name', 'ASC')
            ->get();

        $standardLinks = SiteHelper::getStandardLinkList(Auth::user()->school_id);

        $teacher = TeacherUser::with('userprofile')->where([
            ['school_id', Auth::user()->school_id],
            ['usergroup_id', 5],
            ['status', 'active']
        ])->get()->sortBy('userprofile.firstname');

        $teacherlist = TeacherResource::collection($teacher);

        $array['academic_year_id'] = $academic_year->id;
        $array['standardlink']     = $standardLinks;
        $array['standardlist']     = $standardlist;
        $array['sectionlist']      = $sectionlist;
        $array['subjectlist']      = $subjectlist;
        $array['teacherlist']      = $teacherlist;

        return $array;
    }

    /**
     * Fetch standard details by standard ID.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStandard(Request $request)
    {
        $standard = Standard::where('id', request('standard_id'))->first();
        return response()->json($standard);
    }

    /**
     * Show standard link creation view.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('/admin/school/standardlinks/create');
    }

    /**
     * Store a newly created standard link.
     *
     * Assigns subjects, sections, teachers,
     * and logs the activity.
     *
     * @param StandardDetailRequest $request
     * @return array
     */
    public function store(StandardDetailRequest $request)
    {
        try
        {
            $school_id = Auth::user()->school_id;
            $academic_year = SiteHelper::getAcademicYear($school_id);

            $standard = $this->createStandardLink(
                $school_id,
                $academic_year->id,
                $request
            );

            $message = trans('messages.add_success_msg', ['module' => 'Standard Details']);

            $ip = $this->getRequestIP();
            $this->doActivityLog(
                $standard,
                Auth::user(),
                ['ip' => $ip, 'details' => $_SERVER['HTTP_USER_AGENT']],
                LOGNAME_ADD_STANDARD_DETAIL,
                $message
            );

            return ['success' => $message];
        }
        catch (Exception $e)
        {
            Log::info($e->getMessage());
            dd($e->getMessage());
        }
    }

    /**
     * Fetch standard link details for edit API.
     *
     * @param int $id
     * @return array
     */
    public function editList($id)
    {
        $array = [];

        $academic_year = SiteHelper::getAcademicYear(Auth::user()->school_id);
        $standardLink = StandardLink::with('teacherlink')->where('id', $id)->first();

        $subjectlist = Subject::where([
            ['school_id', Auth::user()->school_id],
            ['academic_year_id', $academic_year->id],
            ['standard_id', $standardLink->standard_id],
            ['section_id', $standardLink->section_id],
            ['type', '!=', 'exam']
        ])->get();

        $teacher = TeacherUser::with('userprofile')->where([
            ['school_id', Auth::user()->school_id],
            ['usergroup_id', 5],
            ['status', 'active']
        ])->get()->sortBy('userprofile.firstname');

        $teacherlist = TeacherResource::collection($teacher);
        $teacherLink = $standardLink->getTeacherLinkDetails();

        $array['subjectlist']     = $subjectlist;
        $array['teacherlist']     = $teacherlist;
        $array['standard']        = $standardLink->standard_id;
        $array['standard_id']     = $standardLink->StandardName;
        $array['section_id']      = $standardLink->section->name;
        $array['stream']          = $standardLink->stream;
        $array['class_teacher_id']= $standardLink->class_teacher_id;
        $array['no_of_students']  = $standardLink->no_of_students;
        $array['inputs']          = $teacherLink['inputs'];

        return $array;
    }

    /**
     * Show standard link edit view.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $standardLink = StandardLink::where('id', $id)->first();
        return view('/admin/school/standardlinks/edit', ['standardLink' => $standardLink]);
    }

    /**
     * Update standard link details.
     *
     * @param StandardDetailUpdateRequest $request
     * @param int $id
     * @return array
     */
    public function update(StandardDetailUpdateRequest $request, $id)
    {
        try
        {
            $academic_year = SiteHelper::getAcademicYear(Auth::user()->school_id);
            $school_id = Auth::user()->school_id;

            $standardLink = StandardLink::where('id', $id)->first();

            $this->editStandardLink(
                $school_id,
                $academic_year->id,
                $id,
                $request
            );

            $message = trans('messages.update_success_msg', ['module' => 'Standard Details']);

            $ip = $this->getRequestIP();
            $this->doActivityLog(
                $standardLink,
                Auth::user(),
                ['ip' => $ip, 'details' => $_SERVER['HTTP_USER_AGENT']],
                LOGNAME_EDIT_STANDARD_DETAIL,
                $message
            );

            return ['success' => $message];
        }
        catch (Exception $e)
        {
            Log::info($e->getMessage());
        }
    }

    /**
     * Update standard link status.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function updateStatus(Request $request, $id)
    {
        try
        {
            $standard = StandardLink::where('id', $id)->first();
            $standard->status = $request->status;
            $standard->save();

            $message = trans('messages.update_status_success_msg', ['module' => 'Standard Details']);

            $ip = $this->getRequestIP();
            $this->doActivityLog(
                $standard,
                Auth::user(),
                ['ip' => $ip, 'details' => $_SERVER['HTTP_USER_AGENT']],
                LOGNAME_UPDATE_STATUS_STANDARD_DETAIL,
                $message
            );

            return redirect('/admin/standardlinks')->with('successmessage', $message);
        }
        catch (Exception $e)
        {
            Log::info($e->getMessage());
        }
    }

    /**
     * Generate student ID card view for a class.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function idcard($id)
    {
        $academic = SiteHelper::getAcademicYear(Auth::user()->school_id);
        $standardLink = StandardLink::where('id', $id)->first();
        $students = SiteHelper::getClassStudents(
            Auth::user()->school_id,
            $academic->id,
            $standardLink->id
        );

        return view('admin.id-card.id-card-new', compact('standardLink', 'students', 'academic'));
    }
    /**
 * Generate and stream student ID cards as a PDF.
 *
 * This method retrieves the current academic year for the authenticated user's school,
 * fetches the selected class/standard link, loads all students of that class,
 * and generates an ID card PDF using a Blade view.
 *
 * @param int $id
 *        The ID of the StandardLink (class/section) for which ID cards are generated.
 *
 * @return \Symfony\Component\HttpFoundation\StreamedResponse
 *         Streams the generated PDF file directly to the browser.
 *
 * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
 *         If the given StandardLink ID does not exist.
 */
public function printidcard($id)
{
    $academic = SiteHelper::getAcademicYear(Auth::user()->school_id);

    $standardLink = StandardLink::where('id', $id)->first();

    $students = SiteHelper::getClassStudents(
        Auth::user()->school_id,
        $academic_year->id,
        $standardLink->id
    );

    $pdf = PDF::loadView(
        'admin/id-card/idcard-print',
        compact('exam', 'students', 'academic')
    );

    return $pdf->stream('result.pdf', ['Attachment' => 0]);
}


    /**
     * Delete a standard link and its dependencies.
     *
     * @param int $id
     * @return mixed
     */
    public function destroy($id)
    {
        try
        {
            $standard = StandardLink::where('id', $id)->first();
            $teacherlinks = Teacherlink::where('standardLink_id', $id);

            if (class_exists('Gegok12\Timetable\Models\TempTimetable'))
            {
                $temptimetable = Gegok12\Timetable\Models\TempTimetable::where('standardLink_id', $id);
            }

            if (Gate::allows('standardlink', $standard))
            {
                if (class_exists('Gegok12\Timetable\Models\TempTimetable'))
                {
                    $temptimetable->delete();
                }

                $teacherlinks->delete();
                $standard->delete();

                $message = trans('messages.delete_success_msg', ['module' => 'Standard Details']);

                $ip = $this->getRequestIP();
                $this->doActivityLog(
                    $standard,
                    Auth::user(),
                    ['ip' => $ip, 'details' => $_SERVER['HTTP_USER_AGENT']],
                    LOGNAME_DELETE_STANDARD_DETAIL,
                    $message
                );

                return $message;
            }
            else
            {
                abort(403);
            }
        }
        catch (Exception $e)
        {
            Log::info($e->getMessage());
        }
    }
}
