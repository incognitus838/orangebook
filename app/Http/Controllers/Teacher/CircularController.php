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
            'templates' => CircularTemplates::all(),
            'classes' => $this->mailer->classOptions(Auth::user()),
            'parents' => $this->mailer->parentOptions(Auth::user()),
            'audiences' => [
                'class' => 'Parents of my class',
                'parent' => 'One parent',
            ],
            'action' => url('/teacher/mail'),
            'logUrl' => url('/teacher/mail/log'),
            'countUrl' => url('/teacher/mail/count'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'audience' => 'required|in:class,parent',
            'class_id' => 'nullable|integer',
            'parent_id' => 'nullable|integer',
            'subject' => 'required|string|max:180',
            'body' => 'required|string|max:5000',
        ]);
        if ($data['audience'] === 'class' && empty($data['class_id'])) {
            return back()->with('failmessage', 'Choose a class.')->withInput();
        }
        if ($data['audience'] === 'parent' && empty($data['parent_id'])) {
            return back()->with('failmessage', 'Choose a parent.')->withInput();
        }
        $count = $this->mailer->send(
            Auth::user(),
            $data['audience'],
            $data['subject'],
            $data['body'],
            $data['class_id'] ?? null,
            $data['parent_id'] ?? null
        );
        return redirect('/teacher/mail')
            ->with('successmessage', $count . ' message' . ($count === 1 ? '' : 's') . ' sent and logged.');
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
