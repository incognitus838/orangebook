<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SendMail;
use App\Services\CircularMailer;
use App\Support\CircularTemplates;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CircularController extends Controller
{
    public function __construct(private CircularMailer $mailer)
    {
    }

    public function create()
    {
        return view('crest.mail.compose', $this->formData('admin') + $this->mailMeta('admin'));
    }

    public function store(Request $request, \App\Services\RecipientService $recipients)
    {
        $data = $request->validate([
            'subject' => 'required|string|max:180',
            'body' => 'required|string|max:5000',
            'everyone' => 'nullable',
            'groups' => 'nullable|array',
            'group_ids' => 'nullable|array',
            'class_id' => 'nullable|integer',
            'class_who' => 'nullable|string',
            'user_ids' => 'nullable|array',
        ]);
        $people = $recipients->fromRequest($request, Auth::user()->school_id);
        if ($people->isEmpty()) {
            return back()->with('failmessage', 'Choose who should receive this circular.')->withInput();
        }
        $payload = $recipients->payloadFromRequest($request);
        $count = $this->mailer->sendTo(
            Auth::user(),
            $people,
            $data['subject'],
            $data['body'],
            $payload['class_id'] ?? null,
            $payload
        );
        $word = $count === 1 ? 'person' : 'people';
        return redirect('/admin/mail')
            ->with('successmessage', $count.' '.$word.' this morning.');
    }

    public function log()
    {
        $rows = SendMail::where('school_id', Auth::user()->school_id)
            ->where('user_id', Auth::id())
            ->orderByDesc('id')
            ->take(50)
            ->get();
        return view('crest.mail.log', ['rows' => $rows, 'role' => 'admin']);
    }

    public function count(Request $request, \App\Services\RecipientService $recipients)
    {
        $n = $recipients->fromRequest($request, Auth::user()->school_id)->count();
        return response()->json(['count' => $n]);
    }

    private function formData(string $role): array
    {
        return [
            'role' => $role,
            'templates' => CircularTemplates::all(Auth::user()->school),
            'classes' => $this->mailer->classOptions(Auth::user()),
            'parents' => $this->mailer->parentOptions(Auth::user()),
            'people' => \App\Models\User::where('school_id', Auth::user()->school_id)->where('status', 'active')->orderBy('name')->get(['id', 'name', 'email', 'usergroup_id']),
            'groups' => \App\Models\PeopleGroup::where('school_id', Auth::user()->school_id)->orderBy('name')->get(),
            'fullTarget' => true,
            'action' => url('/admin/mail'),
            'logUrl' => url('/admin/mail/log'),
            'countUrl' => url('/admin/mail/count'),
        ];
    }

    private function mailMeta(string $role): array
    {
        return [
            'mailReady' => $this->mailer->smtpReady(),
            'recent' => SendMail::where('school_id', Auth::user()->school_id)
                ->where('user_id', Auth::id())
                ->orderByDesc('id')
                ->take(8)
                ->get(),
        ];
    }
}
