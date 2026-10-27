<?php

namespace App\Services;

use App\Helpers\SiteHelper;
use App\Models\NoticeBoard;
use App\Models\SendMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AnnouncementService
{
    public function __construct(
        private SchoolBoardService $board,
        private CircularMailer $mailer,
        private RecipientService $recipients,
    ) {
    }

    public function post(User $author, Request $request): NoticeBoard
    {
        $delivery = $request->input('delivery', 'board');
        $max = $delivery === 'email' ? 5000 : 240;
        $data = $request->validate([
            'title' => 'required|string|max:120',
            'body' => 'required|string|max:'.$max,
            'delivery' => 'nullable|in:board,email',
            'everyone' => 'nullable',
            'groups' => 'nullable|array',
            'group_ids' => 'nullable|array',
            'class_id' => 'nullable|integer',
            'class_who' => 'nullable|string',
            'user_ids' => 'nullable|array',
        ]);
        $payload = $this->recipients->payloadFromRequest($request);
        $people = $this->recipients->fromRequest($request, $author->school_id);
        if ($people->isEmpty() && empty($payload['everyone'])) {
            throw ValidationException::withMessages(['everyone' => 'Choose who should see this.']);
        }
        $notice = $this->board->addNotice($author, [
            'title' => $data['title'],
            'body' => $data['body'],
            'class_id' => $payload['class_id'] ?? null,
            'audience' => $payload,
        ]);
        $notice->full_body = $data['body'];
        $notice->created_by = $author->id;
        if ($delivery === 'email') {
            $count = $this->mailer->sendTo(
                $author,
                $people,
                $data['title'],
                $data['body'],
                $payload['class_id'] ?? null,
                $payload,
                false
            );
            $mailId = SendMail::where('school_id', $author->school_id)
                ->where('user_id', $author->id)
                ->where('subject', $data['title'])
                ->orderByDesc('id')
                ->value('id');
            $notice->send_mail_id = $mailId;
            $notice->save();
            session()->flash('successmessage', 'Posted to the board'.($count ? ' and sent to '.$count.' '.($count === 1 ? 'person' : 'people') : '').'.');
        } else {
            $notice->save();
            session()->flash('successmessage', 'Posted to the board.');
        }

        return $notice;
    }

    public function feed(int $schoolId, Request $request)
    {
        $year = SiteHelper::getAcademicYear($schoolId);

        return NoticeBoard::with('standardLink', 'author')
            ->where('school_id', $schoolId)
            ->when($year, fn ($q) => $q->where('academic_year_id', $year->id))
            ->when($request->query('channel') === 'email', fn ($q) => $q->whereNotNull('send_mail_id'))
            ->when($request->query('channel') === 'board', fn ($q) => $q->whereNull('send_mail_id'))
            ->when($request->filled('class_id'), fn ($q) => $q->where('standardLink_id', $request->class_id))
            ->when($request->filled('author'), fn ($q) => $q->where('created_by', $request->author))
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();
    }

    public function audienceLabel(?array $audience, $class = null): string
    {
        if (! $audience || ! empty($audience['everyone'])) {
            return 'Everyone';
        }
        $bits = [];
        if (! empty($audience['groups'])) {
            $bits[] = implode(', ', $audience['groups']);
        }
        if ($class) {
            $bits[] = $class->StandardSection;
        }
        if (! empty($audience['user_ids'])) {
            $bits[] = count($audience['user_ids']).' people';
        }

        return $bits ? implode(' · ', $bits) : 'Named people';
    }
}
