<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\SiteHelper;
use App\Http\Controllers\Controller;
use App\Models\NoticeBoard;
use App\Models\StandardLink;
use App\Models\User;
use App\Services\AnnouncementService;
use App\Support\CircularTemplates;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnnouncementController extends Controller
{
    protected string $role = 'admin';
    protected string $base = '/admin/announcements';

    public function index(Request $request, AnnouncementService $service)
    {
        $school = Auth::user()->school;
        $year = SiteHelper::getAcademicYear($school->id);
        $classes = $year
            ? StandardLink::with('standard', 'section')->where('school_id', $school->id)->where('academic_year_id', $year->id)->get()
            : collect();

        return view('crest.board.announcements', [
            'role' => $this->role,
            'layout' => $this->role === 'teacher' ? 'layouts.teacher.layout' : 'layouts.admin.layout',
            'school' => $school,
            'templates' => CircularTemplates::all($school),
            'classes' => $classes,
            'people' => User::where('school_id', $school->id)->where('status', 'active')->orderBy('name')->get(['id', 'name', 'email', 'usergroup_id']),
            'groups' => \App\Models\PeopleGroup::where('school_id', $school->id)->orderBy('name')->get(),
            'fullTarget' => true,
            'action' => url($this->base),
            'countUrl' => url($this->role === 'teacher' ? '/teacher/mail/count' : '/admin/mail/count'),
            'feed' => $service->feed($school->id, $request),
            'service' => $service,
            'authors' => User::where('school_id', $school->id)->whereIn('id', NoticeBoard::where('school_id', $school->id)->whereNotNull('created_by')->pluck('created_by'))->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, AnnouncementService $service)
    {
        $service->post(Auth::user(), $request);

        return redirect($this->base);
    }

    public function update(Request $request, $id)
    {
        $notice = NoticeBoard::where('id', $id)->where('school_id', Auth::user()->school_id)->firstOrFail();
        $data = $request->validate([
            'body' => 'required|string|max:5000',
        ]);
        $notice->full_body = $data['body'];
        $notice->description = \Illuminate\Support\Str::limit($data['body'], 240, '');
        $notice->save();

        return redirect($this->base)->with('successmessage', 'Board text updated.');
    }

    public function archive($id)
    {
        $notice = NoticeBoard::where('id', $id)->where('school_id', Auth::user()->school_id)->firstOrFail();
        $notice->status = 0;
        $notice->save();

        return redirect($this->base)->with('successmessage', 'Announcement archived.');
    }

    public function destroy($id)
    {
        $notice = NoticeBoard::where('id', $id)->where('school_id', Auth::user()->school_id)->firstOrFail();
        $notice->delete();

        return redirect($this->base)->with('successmessage', 'Announcement deleted.');
    }
}
