<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Teacher;

use App\Http\Resources\Teacher\Notice as NoticeResource;
use App\Http\Resources\StandardLink as StandardLinkResource; //new
use App\Http\Resources\backgroundImagesResource;  //new
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\BackgroundImage; //new
use Illuminate\Http\Request;
use App\Models\StandardLink;
use App\Models\Teacherlink;
use App\Models\NoticeBoard;
use App\Helpers\SiteHelper;

class NoticeBoardController extends Controller
{

    public function list(Request $request)
    {
        //
        $school_id = Auth::user()->school_id;
        $academic_year = SiteHelper::getAcademicYear($school_id);

        $standardLinks = StandardLink::where([
                ['school_id',$school_id],
                ['academic_year_id',$academic_year->id],
                ['class_teacher_id',Auth::id()]
            ])->pluck('id')->toArray();

        $teacherlinks = Teacherlink::where([
            ['school_id',$school_id],
            ['academic_year_id',$academic_year->id],
            ['teacher_id',Auth::id()]
        ])->pluck('standardLink_id')->toArray();

        $standards = array_merge($standardLinks,$teacherlinks);
        array_push($standards, null);

        $notices = NoticeBoard::where([['school_id',$school_id],['academic_year_id',$academic_year->id]])->where([['expire_date','>=',date('Y-m-d')],['status',1]])->orWhereIn('standardLink_id',$standards);//check with standards

        if(count((array)\Request::getQueryString())>0)
        {
            if($request->showExpired == 'true')
            { 
                $notices = $notices->orWhere([['status',0],['expire_date','<=',date('Y-m-d')]]);
            }
            if($request->standardLink_id != '')
            { 
                $notices = $notices->where('standardLink_id',$request->standardLink_id);
            }

            if($request->search != '')
            { 
                $notices = $notices->where('title','LIKE','%'.$request->search.'%')->orWhere('description','LIKE','%'.$request->search.'%');
            }
        }

        $notices = $notices->paginate(10);

        $notices = NoticeResource::collection($notices);
        
        return $notices;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(\App\Services\SchoolBoardService $board)
    {
        $classes = $board->teacherClasses(Auth::user());
        return view('crest.board.notices', [
            'layout' => 'layouts.teacher.layout',
            'notices' => $board->noticesForViewer(Auth::user()),
            'classes' => $classes,
            'action' => url('/teacher/notices'),
            'canAdd' => true,
        ]);
    }

    public function storeNotice(\Illuminate\Http\Request $request, \App\Services\SchoolBoardService $board)
    {
        $data = $request->validate([
            'title' => 'required|string|max:120',
            'body' => 'required|string|max:240',
            'class_id' => 'nullable|integer',
        ]);
        $board->addNotice(Auth::user(), $data);
        return redirect('/teacher/notices')->with('successmessage', 'Notice posted.');
    }

    //new 
    public function noticelist()
    {
        //
        $standardLink = StandardLink::with('standard','section')->where('school_id',Auth::user()->school_id)->get();
        $backgroundimages=BackgroundImage::where('school_id',Auth::user()->school_id)->latest()->get();
        $backgroundimages=backgroundImagesResource::collection($backgroundimages);
        $standardLink = StandardLinkResource::collection($standardLink);

        $array = [];

        $array['standardLinklist']=$standardLink;
        $array['backgroundimages']=$backgroundimages;
        
        return $array;
    }   
}