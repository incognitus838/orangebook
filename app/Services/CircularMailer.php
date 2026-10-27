<?php

namespace App\Services;

use App\Helpers\SiteHelper;
use App\Models\NoticeBoard;
use App\Models\SendMail;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\StudentParentLink;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class CircularMailer
{
    public function recipients(User $sender, string $audience, ?int $classId = null, ?int $parentId = null): \Illuminate\Support\Collection
    {
        $schoolId = $sender->school_id;
        $query = User::where('school_id', $schoolId)->where('status', 'active')->whereNotNull('email');

        if ($audience === 'staff') {
            return $query->whereIn('usergroup_id', [5, 8, 10, 11, 13])->get();
        }

        if ($audience === 'teachers') {
            return $query->where('usergroup_id', 5)->get();
        }

        if ($audience === 'parents') {
            $this->assertAdmin($sender);
            return $query->where('usergroup_id', 7)->get();
        }

        if ($audience === 'class') {
            if (!$classId) {
                return collect();
            }
            $this->assertClassAccess($sender, $classId);
            $studentIds = StudentAcademic::where('school_id', $schoolId)
                ->where('standardLink_id', $classId)
                ->pluck('user_id');
            $parentIds = StudentParentLink::where('school_id', $schoolId)
                ->whereIn('student_id', $studentIds)
                ->pluck('parent_id');
            return $query->where('usergroup_id', 7)->whereIn('id', $parentIds)->get();
        }

        if ($audience === 'parent') {
            $parent = $query->where('usergroup_id', 7)->where('id', $parentId)->first();
            if (!$parent) {
                return collect();
            }
            if ($sender->isTeacher()) {
                $allowed = $this->parentIdsForTeacher($sender);
                if (!$allowed->contains($parent->id)) {
                    abort(403);
                }
            }
            return collect([$parent]);
        }

        abort(422, 'Unknown audience');
    }

    public function sendTo(User $sender, $recipients, string $subject, string $body, ?int $classId = null, ?array $audiencePayload = null, bool $postNotice = true): int
    {
        $year = SiteHelper::getAcademicYear($sender->school_id);
        $schoolName = $sender->school->name ?? 'School';
        $className = $classId ? optional(StandardLink::find($classId))->StandardSection : '';
        if ($postNotice && $audiencePayload) {
            $this->postNotice($sender, 'groups', $subject, $body, $classId, $audiencePayload);
        }
        $count = 0;
        foreach ($recipients as $person) {
            if (!$person->email) {
                continue;
            }
            $child = $this->firstChild($person, $classId);
            $text = strtr($body, [
                '{parent_name}' => $person->name,
                '{student_name}' => $child['name'],
                '{class}' => $child['class'] ?: $className,
                '{school_name}' => $schoolName,
            ]);
            $subj = strtr($subject, [
                '{parent_name}' => $person->name,
                '{student_name}' => $child['name'],
                '{class}' => $child['class'] ?: $className,
                '{school_name}' => $schoolName,
            ]);
            $status = 'delivered';
            try {
                $mailer = $this->smtpReady() ? Mail::mailer() : Mail::mailer('log');
                $mailer->raw($text, function ($message) use ($person, $subj, $sender) {
                    $message->to($person->email)->subject($subj);
                    $from = config('mail.from.address');
                    if ($from) {
                        $message->from($from, $sender->school->name ?? config('app.name'));
                    }
                });
            } catch (\Throwable $e) {
                $status = 'failed';
                \Log::warning('Circular mail failed: '.$e->getMessage());
            }
            SendMail::create([
                'school_id' => $sender->school_id,
                'academic_year_id' => $year->id,
                'user_id' => $sender->id,
                'student_id' => $child['id'],
                'from_address' => $sender->email,
                'from' => $sender->name,
                'to' => $person->email,
                'subject' => $subj,
                'message' => $text,
                'status' => $status,
                'type' => 'sent',
                'is_executed' => 1,
                'fired_at' => now(),
                'executed_at' => now(),
            ]);
            $count++;
        }
        return $count;
    }

    public function send(User $sender, string $audience, string $subject, string $body, ?int $classId = null, ?int $parentId = null): int
    {
        $recipients = $this->recipients($sender, $audience, $classId, $parentId);
        $year = SiteHelper::getAcademicYear($sender->school_id);
        $schoolName = $sender->school->name ?? 'School';
        $className = $classId ? optional(StandardLink::find($classId))->StandardSection : '';

        if ($audience !== 'parent') {
            $this->postNotice($sender, $audience, $subject, $body, $classId);
        }

        foreach ($recipients as $person) {
            $child = $this->firstChild($person, $classId);
            $text = strtr($body, [
                '{parent_name}' => $person->name,
                '{student_name}' => $child['name'],
                '{class}' => $child['class'] ?: $className,
                '{school_name}' => $schoolName,
            ]);
            $subj = strtr($subject, [
                '{parent_name}' => $person->name,
                '{student_name}' => $child['name'],
                '{class}' => $child['class'] ?: $className,
                '{school_name}' => $schoolName,
            ]);

            $status = 'delivered';
            try {
                $mailer = $this->smtpReady() ? Mail::mailer() : Mail::mailer('log');
                $mailer->raw($text, function ($message) use ($person, $subj, $sender) {
                    $message->to($person->email)->subject($subj);
                    $from = config('mail.from.address');
                    if ($from) {
                        $message->from($from, $sender->school->name ?? config('app.name'));
                    }
                });
            } catch (\Throwable $e) {
                $status = 'failed';
                \Log::warning('Circular mail failed: '.$e->getMessage());
            }

            SendMail::create([
                'school_id' => $sender->school_id,
                'academic_year_id' => $year->id,
                'user_id' => $sender->id,
                'student_id' => $child['id'],
                'from_address' => $sender->email,
                'from' => $sender->name,
                'to' => $person->email,
                'subject' => $subj,
                'message' => $text,
                'status' => $status,
                'type' => 'sent',
                'is_executed' => 1,
                'fired_at' => now(),
                'executed_at' => now(),
            ]);
        }

        return $recipients->count();
    }

    public function classOptions(User $sender)
    {
        $schoolId = $sender->school_id;
        $year = SiteHelper::getAcademicYear($schoolId);
        $q = StandardLink::with('standard', 'section')
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $year->id);
        if ($sender->isTeacher()) {
            $q->whereIn('id', SiteHelper::teacherClassIds($schoolId, $sender->id));
        }
        return $q->get();
    }

    public function parentOptions(User $sender)
    {
        if ($sender->isTeacher()) {
            $ids = $this->parentIdsForTeacher($sender);
            return User::where('school_id', $sender->school_id)
                ->where('usergroup_id', 7)
                ->whereIn('id', $ids)
                ->orderBy('name')
                ->get();
        }
        return User::where('school_id', $sender->school_id)
            ->where('usergroup_id', 7)
            ->orderBy('name')
            ->get();
    }

    private function parentIdsForTeacher(User $teacher)
    {
        $classIds = SiteHelper::teacherClassIds($teacher->school_id, $teacher->id);
        $studentIds = StudentAcademic::where('school_id', $teacher->school_id)
            ->whereIn('standardLink_id', $classIds)
            ->pluck('user_id');
        return StudentParentLink::where('school_id', $teacher->school_id)
            ->whereIn('student_id', $studentIds)
            ->pluck('parent_id');
    }

    private function firstChild(User $parent, ?int $classId): array
    {
        if ((int) $parent->usergroup_id === 6) {
            return [
                'id' => $parent->id,
                'name' => $parent->name,
                'class' => optional(optional($parent->studentAcademicLatest)->standardLink)->StandardSection ?? '',
            ];
        }
        $link = StudentParentLink::where('parent_id', $parent->id)
            ->when($classId, function ($q) use ($classId) {
                $q->whereHas('userStudent.studentAcademicLatest', fn ($s) => $s->where('standardLink_id', $classId));
            })
            ->first();
        $student = $link?->userStudent;
        return [
            'id' => $student?->id,
            'name' => $student?->name ?? 'your child',
            'class' => optional(optional($student?->studentAcademicLatest)->standardLink)->StandardSection ?? '',
        ];
    }

    public function smtpReady(): bool
    {
        $mailer = config('mail.default') ?: config('mail.driver');
        if (in_array($mailer, ['log', 'array', null, ''], true)) {
            return false;
        }
        return filled(config('mail.username')) || filled(config('mail.mailers.smtp.username'));
    }

    private function postNotice(User $sender, string $audience, string $subject, string $body, ?int $classId, ?array $payload = null): void
    {
        $year = SiteHelper::getAcademicYear($sender->school_id);
        if (!$year) {
            return;
        }
        $classForNotice = $payload['class_id'] ?? (in_array($audience, ['class', 'parent'], true) ? $classId : null);
        $type = in_array($audience, ['teachers', 'staff'], true)
            ? 'teacher'
            : ($classForNotice ? 'class' : 'school');
        NoticeBoard::create([
            'school_id' => $sender->school_id,
            'academic_year_id' => $year->id,
            'standardLink_id' => $classForNotice,
            'type' => $type,
            'title' => $subject,
            'description' => \Illuminate\Support\Str::limit(strip_tags($body), 240, ''),
            'publish_date' => now(),
            'expire_date' => now()->addDays(30),
            'status' => 1,
            'audience' => $payload,
        ]);
    }

    private function assertAdmin(User $sender): void
    {
        if (!$sender->isAdmin()) {
            abort(403);
        }
    }

    private function assertClassAccess(User $sender, ?int $classId): void
    {
        if (!$classId) {
            abort(422, 'Choose a class');
        }
        $class = StandardLink::where('id', $classId)->where('school_id', $sender->school_id)->first();
        if (!$class) {
            abort(403);
        }
        if ($sender->isTeacher() && !SiteHelper::teacherTeachesClass($sender->school_id, $sender->id, $class->id)) {
            abort(403);
        }
    }
}
