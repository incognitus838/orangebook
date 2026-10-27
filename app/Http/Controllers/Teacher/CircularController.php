<?php

namespace App\Http\Controllers\Teacher;

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
        return view('crest.mail.compose', [
            'role' => 'teacher',
            'templates' => CircularTemplates::all(Auth::user()->school),
            'classes' => $this->mailer->classOptions(Auth::user()),
            'parents' => $this->mailer->parentOptions(Auth::user()),
            'audiences' => Auth::user()->school->usesParents()
                ? [
                    'class' => 'Parents of my class',
                    'parent' => 'One parent',
                ]
                : [
                    'class' => Auth::user()->school->learnerWord().' in my '.strtolower(Auth::user()->school->classWordOne()),
                ],
            'action' => url('/teacher/mail'),
            'logUrl' => url('/teacher/mail/log'),
            'countUrl' => url('/teacher/mail/count'),
            'mailReady' => $this->mailer->smtpReady(),
            'recent' => SendMail::where('school_id', Auth::user()->school_id)
                ->where('user_id', Auth::id())
                ->orderByDesc('id')
                ->take(8)
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $usesParents = Auth::user()->school->usesParents();
        $data = $request->validate([
            'audience' => $usesParents ? 'required|in:class,parent' : 'required|in:class',
            'class_id' => 'nullable|integer',
            'parent_id' => 'nullable|integer',
            'subject' => 'required|string|max:180',
            'body' => 'required|string|max:5000',
        ]);
        if ($data['audience'] === 'class' && empty($data['class_id'])) {
            return back()->with('failmessage', 'Choose a '.strtolower(Auth::user()->school->classWordOne()).'.')->withInput();
        }
        if ($usesParents && $data['audience'] === 'parent' && empty($data['parent_id'])) {
            return back()->with('failmessage', 'Choose a parent.')->withInput();
        }
        if (!$usesParents) {
            $people = app(\App\Services\RecipientService::class)->resolve(Auth::user()->school_id, [
                'class_id' => (int) $data['class_id'],
                'class_who' => 'students',
            ]);
            $count = $this->mailer->sendTo(
                Auth::user(),
                $people,
                $data['subject'],
                $data['body'],
                (int) $data['class_id'],
                ['class_id' => (int) $data['class_id'], 'class_who' => 'students', 'groups' => ['students']]
            );
        } else {
        $count = $this->mailer->send(
            Auth::user(),
            $data['audience'],
            $data['subject'],
            $data['body'],
            $data['class_id'] ?? null,
            $data['parent_id'] ?? null
        );
        }
        $word = $count === 1 ? 'person' : 'people';
        return redirect('/teacher/mail')
            ->with('successmessage', $count.' '.$word.' this morning.');
    }

    public function log()
    {
        $rows = SendMail::where('school_id', Auth::user()->school_id)
            ->where('user_id', Auth::id())
            ->orderByDesc('id')
            ->take(50)
            ->get();
        return view('crest.mail.log', ['rows' => $rows, 'role' => 'teacher']);
    }

    public function count(Request $request)
    {
        $audience = $request->query('audience', 'class');
        if (!in_array($audience, ['class', 'parent'], true)) {
            return response()->json(['count' => 0]);
        }
        $n = $this->mailer->recipients(
            Auth::user(),
            $audience,
            $request->query('class_id') ? (int) $request->query('class_id') : null,
            $request->query('parent_id') ? (int) $request->query('parent_id') : null
        )->count();
        return response()->json(['count' => $n]);
    }
}
