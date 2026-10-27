<?php

/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */

namespace App\Http\Controllers\Admin;

use App\Http\Resources\AcademicYear as AcademicYearResource;
use App\Http\Requests\AcademicYearUpdateRequest;
use App\Http\Requests\AcademicYearAddRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Traits\LogActivity;
use App\Helpers\SiteHelper;
use App\Traits\Common;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Str;
use Log;

/**
 * Class AcademicYearController
 *
 * Controller for managing academic years: listing, creating,
 * updating and deleting academic year records for the school.
 *
 * @package App\Http\Controllers\Admin
 */
class AcademicYearController extends Controller
{
    use LogActivity;
    use Common;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function list()
    {
        //
        $academicYears = AcademicYear::where('school_id', Auth::user()->school_id)->orderBy('name', 'ASC')->get();
        $academic_year = AcademicYear::where([['status', 1], ['school_id', Auth::user()->school_id]])->first();

        $array = [];

        $array['academic_years']    = AcademicYearResource::collection($academicYears);
        $array['academic_year_id']  = $academic_year->id;

        return $array;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $schoolId = Auth::user()->school_id;
        $academicYears = AcademicYear::where('school_id', $schoolId)->orderByDesc('start_date')->get();
        $currentYear = $academicYears->firstWhere('status', 1) ?: SiteHelper::getAcademicYear($schoolId);
        $terms = collect();
        if ($currentYear) {
            $this->ensureTerms($schoolId, $currentYear);
            $terms = AcademicTerm::where('school_id', $schoolId)
                ->where('academic_year_id', $currentYear->id)
                ->orderBy('term_order')
                ->get();
        }
        return view('/admin/school/academics/index', [
            'school' => Auth::user()->school,
            'academicYears' => $academicYears,
            'currentYear' => $currentYear,
            'terms' => $terms,
        ]);
    }

    public function storeYear(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:40',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'required|in:0,1,2',
        ]);
        $schoolId = Auth::user()->school_id;
        if ((int) $data['status'] === 1) {
            AcademicYear::where('school_id', $schoolId)->where('status', 1)->update(['status' => 0]);
        }
        $year = AcademicYear::create([
            'school_id' => $schoolId,
            'name' => $data['name'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'status' => (int) $data['status'],
        ]);
        $this->ensureTerms($schoolId, $year);
        return redirect('/admin/academics')->with('successmessage', $data['name'].' is on the roll.');
    }

    public function makeCurrent($id)
    {
        $schoolId = Auth::user()->school_id;
        $year = AcademicYear::where('id', $id)->where('school_id', $schoolId)->firstOrFail();
        AcademicYear::where('school_id', $schoolId)->where('status', 1)->update(['status' => 0]);
        $year->status = 1;
        $year->save();
        $this->ensureTerms($schoolId, $year);
        return redirect('/admin/academics')->with('successmessage', $year->name.' is the current year.');
    }

    public function makeTermCurrent($id)
    {
        $schoolId = Auth::user()->school_id;
        $year = SiteHelper::getAcademicYear($schoolId);
        $term = AcademicTerm::where('id', $id)
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $year->id)
            ->firstOrFail();
        AcademicTerm::where('school_id', $schoolId)
            ->where('academic_year_id', $year->id)
            ->update(['is_current' => false]);
        $term->is_current = true;
        $term->save();
        return redirect('/admin/academics')->with('successmessage', $term->name.' is the current term.');
    }

    private function ensureTerms(int $schoolId, AcademicYear $year): void
    {
        $names = [1 => 'First Term', 2 => 'Second Term', 3 => 'Third Term'];
        foreach ($names as $order => $name) {
            AcademicTerm::firstOrCreate(
                [
                    'school_id' => $schoolId,
                    'academic_year_id' => $year->id,
                    'term_order' => $order,
                ],
                [
                    'name' => $name,
                    'slug' => Str::slug($name),
                    'is_current' => $order === 1,
                ]
            );
        }
        $hasCurrent = AcademicTerm::where('school_id', $schoolId)
            ->where('academic_year_id', $year->id)
            ->where('is_current', true)
            ->exists();
        if (!$hasCurrent) {
            AcademicTerm::where('school_id', $schoolId)
                ->where('academic_year_id', $year->id)
                ->where('term_order', 1)
                ->update(['is_current' => true]);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function createList()
    {
        //
        $array = [];

        $array['statuslist'] =  SiteHelper::getAcademicYearStatusList();

        return $array;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
        return view('/admin/school/academics/create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(AcademicYearAddRequest $request)
    {
        //
        try {
            $curr_year = date('Y', strtotime($request->start_date));
            $next_year = date('Y', strtotime($request->end_date));
            $academic = new AcademicYear;

            $academic->school_id    = Auth::user()->school_id;
            //$academic->name         = $request->name;
            $academic->name         = $curr_year . '-' . $next_year;
            $academic->description  = $request->description;
            $academic->start_date   = date('Y-m-d', strtotime($request->start_date));
            $academic->end_date     = date('Y-m-d', strtotime($request->end_date));
            if ($request->status == 'current') {
                $academic->status       = 1;
            } elseif ($request->status == 'new') {
                $academic->status       = 2;
            } elseif ($request->status == 'old') {
                $academic->status       = 0;
            }

            $academic->save();

            $message = trans('messages.add_success_msg', ['module' => 'Academic Year']);

            $ip = $this->getRequestIP();
            $this->doActivityLog(
                $academic,
                Auth::user(),
                ['ip' => $ip, 'details' => $_SERVER['HTTP_USER_AGENT']],
                LOGNAME_ADD_ACADEMIC_YEAR,
                $message
            );

            $res['success'] = $message;
            return $res;
        } catch (Exception $e) {
            Log::info($e->getMessage());
            //dd($e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function editList($id)
    {
        //
        $academicYear = AcademicYear::where([['id', $id], ['school_id', Auth::user()->school_id]])->first();

        $array = [];

        $array['description']   = $academicYear->description;
        $array['start_date']    = date('Y-m-d', strtotime($academicYear->start_date));
        $array['end_date']      = date('Y-m-d', strtotime($academicYear->end_date));
        if ($academicYear->status == 1) {
            $array['status']       = 'current';
        } elseif ($academicYear->status == 2) {
            $array['status']       = 'new';
        } elseif ($academicYear->status == 0) {
            $array['status']       = 'old';
        }
        $array['statuslist'] =  SiteHelper::getAcademicYearStatusList();

        return $array;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
        $academic_year = AcademicYear::where([['id', $id], ['school_id', Auth::user()->school_id]])->first();

        if (Gate::allows('academic', $academic_year)) {
            return view('/admin/school/academics/show', ['academic_year' => $academic_year]);
        } else {
            abort(403);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
        $academicYear = AcademicYear::where([['id', $id], ['school_id', Auth::user()->school_id]])->first();

        if (Gate::allows('academic', $academicYear)) {
            return view('/admin/school/academics/edit', ['academicYear' => $academicYear]);
        } else {
            abort(403);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(AcademicYearUpdateRequest $request, $id)
    {
        //
        try {
            $academic = AcademicYear::where('id', $id)->first();

            //$academic->name         = $request->name;
            $academic->description  = $request->description;
            $academic->start_date   = date('Y-m-d', strtotime($request->start_date));
            $academic->end_date     = date('Y-m-d', strtotime($request->end_date));
            if ($request->status == 'current') {
                $academic->status       = 1;
            } elseif ($request->status == 'new') {
                $academic->status       = 2;
            } elseif ($request->status == 'old') {
                $academic->status       = 0;
            }

            $academic->save();

            $message = trans('messages.update_success_msg', ['module' => 'Academic Year']);

            $ip = $this->getRequestIP();
            $this->doActivityLog(
                $academic,
                Auth::user(),
                ['ip' => $ip, 'details' => $_SERVER['HTTP_USER_AGENT']],
                LOGNAME_EDIT_ACADEMIC_YEAR,
                $message
            );

            $res['success'] = $message;
            return $res;
        } catch (Exception $e) {
            Log::info($e->getMessage());
            //dd($e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateStatus(Request $request)
    {
        //
        try {
            $prev_current_academic_year = AcademicYear::where([['status', 1], ['school_id', Auth::user()->school_id]])->first();

            $prev_current_academic_year->status = 0;

            $prev_current_academic_year->save();


            $new_current_academic_year = AcademicYear::where('id', $request->academic_year_id)->first();

            $new_current_academic_year->status = 1;

            $new_current_academic_year->save();

            Cache::flush(); //reset cache to change all details of current academic year

            $message = trans('messages.update_status_success_msg', ['module' => 'Academic Year']);

            $ip = $this->getRequestIP();
            $this->doActivityLog(
                $new_current_academic_year,
                Auth::user(),
                ['ip' => $ip, 'details' => $_SERVER['HTTP_USER_AGENT']],
                LOGNAME_UPDATE_STATUS_ACADEMIC_YEAR,
                $message
            );

            $res['success'] = $message;
            return $res;
        } catch (Exception $e) {
            Log::info($e->getMessage());
            //dd($e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
        try {
            $academic = AcademicYear::where('id', $id)->first();
            if (Gate::allows('academic', $academic)) {
                $academic->delete();

                $message = trans('messages.delete_success_msg', ['module' => 'Academic Year']);

                $ip = $this->getRequestIP();
                $this->doActivityLog(
                    $academic,
                    Auth::user(),
                    ['ip' => $ip, 'details' => $_SERVER['HTTP_USER_AGENT']],
                    LOGNAME_DELETE_ACADEMIC_YEAR,
                    $message
                );

                $res['success'] = $message;
                return $res;
            } else {
                abort(403);
            }
        } catch (Exception $e) {
            Log::info($e->getMessage());
            //dd($e->getMessage());
        }
    }
}
