<?php

namespace App\Services;

use App\Helpers\SiteHelper;
use App\Models\Events;
use App\Models\Homework;
use App\Models\HomeworkApproval;
use App\Models\NoticeBoard;
use App\Models\StandardLink;
use App\Models\Subject;
use App\Models\Teacherlink;
use App\Models\User;
use Carbon\Carbon;

class SchoolBoardService
{
    public function events(int $schoolId)
    {
        $year = SiteHelper::getAcademicYear($schoolId);
        if (!$year) {
            return collect();
        }

        return Events::where('school_id', $schoolId)
            ->where('academic_year_id', $year->id)
            ->orderBy('start_date')
            ->get();
    }

    public function addEvent(User $user, array $data): Events
    {
        $year = SiteHelper::getAcademicYear($user->school_id);
        $start = Carbon::parse($data['date'])->startOfDay();

        $event = new Events();
        $event->school_id = $user->school_id;
        $event->academic_year_id = $year->id;
        $event->standard_id = $data['class_id'] ?? null;
        $event->batch = 'term';
        $event->select_type = !empty($data['class_id']) ? 'class' : 'school';
        $event->title = $data['title'];
        $event->description = $data['notes'] ?? null;
        $event->category = 'meeting';
        $event->color = '#07111c';
        $event->start_date = $start;
        $event->end_date = $start->copy()->endOfDay();
        $event->allDay = 1;
        $event->status = 'active';
        $event->created_by = $user->id;
        $event->updated_by = $user->id;
        $event->save();

        return $event;
    }

    public function notices(int $schoolId, ?int $classId = null, bool $student = false)
    {
        $year = SiteHelper::getAcademicYear($schoolId);
        if (!$year) {
            return collect();
        }

        return NoticeBoard::with('standardLink')
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $year->id)
            ->when($student, fn ($q) => $q->where('type', '!=', 'teacher'))
            ->when($classId, function ($q) use ($classId) {
                $q->where(function ($inner) use ($classId) {
                    $inner->whereNull('standardLink_id')->orWhere('standardLink_id', $classId);
                });
            })
            ->orderByDesc('publish_date')
            ->orderByDesc('id')
            ->get();
    }

    public function noticesForViewer(\App\Models\User $viewer)
    {
        $classId = $viewer->usergroup_id == 6 ? optional($viewer->studentAcademicLatest)->standardLink_id : null;
        $student = $viewer->usergroup_id == 6;
        $items = $this->notices($viewer->school_id, $classId, $student);
        $gate = app(\App\Services\RecipientService::class);
        return $items->filter(function ($notice) use ($viewer, $gate) {
            $audience = $notice->audience;
            if (!$audience) {
                if ($viewer->usergroup_id == 6) {
                    return $notice->type !== 'teacher';
                }
                return true;
            }
            return $gate->viewerSees($audience, $viewer);
        })->values();
    }

    public function addNotice(User $user, array $data): NoticeBoard
    {
        $year = SiteHelper::getAcademicYear($user->school_id);
        $classId = $data['class_id'] ?? null;
        $audience = $data['audience'] ?? null;
        $type = 'school';
        if (!empty($audience['user_ids'])) {
            $type = 'teacher';
        } elseif ($classId) {
            $type = 'class';
        } elseif (!empty($audience['groups']) && in_array('teachers', (array) $audience['groups'], true) && count($audience['groups']) === 1) {
            $type = 'teacher';
        }

        return NoticeBoard::create([
            'school_id' => $user->school_id,
            'academic_year_id' => $year->id,
            'standardLink_id' => $classId ?: null,
            'type' => $type,
            'title' => $data['title'],
            'description' => \Illuminate\Support\Str::limit($data['body'] ?? $data['title'], 240, ''),
            'full_body' => $data['body'] ?? null,
            'publish_date' => now(),
            'expire_date' => now()->addDays(30),
            'status' => 1,
            'audience' => $audience,
            'created_by' => $user->id,
        ]);
    }

    public function homeworkForTeacher(User $teacher)
    {
        $ids = SiteHelper::teacherClassIds($teacher->school_id, $teacher->id);
        if ($ids->isEmpty()) {
            return collect();
        }

        return Homework::with('subject', 'standardLink')
            ->where('school_id', $teacher->school_id)
            ->whereIn('standardLink_id', $ids)
            ->orderByDesc('id')
            ->get();
    }

    public function homeworkForSchool(int $schoolId)
    {
        $year = SiteHelper::getAcademicYear($schoolId);
        if (!$year) {
            return collect();
        }

        return Homework::with('subject', 'standardLink')
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $year->id)
            ->orderByDesc('id')
            ->get();
    }

    public function homeworkForStudent(User $student)
    {
        $classId = optional($student->studentAcademicLatest)->standardLink_id;
        if (!$classId) {
            return collect();
        }

        return Homework::with('subject')
            ->where('school_id', $student->school_id)
            ->where('standardLink_id', $classId)
            ->where('status', 'publish')
            ->orderByDesc('id')
            ->get();
    }

    public function addHomework(User $user, array $data): Homework
    {
        $year = SiteHelper::getAcademicYear($user->school_id);
        $class = StandardLink::where('id', $data['class_id'])
            ->where('school_id', $user->school_id)
            ->firstOrFail();

        if ($user->isTeacher() && !SiteHelper::teacherTeachesClass($user->school_id, $user->id, $class->id)) {
            abort(403);
        }

        $work = new Homework();
        $work->school_id = $user->school_id;
        $work->academic_year_id = $year->id;
        $work->standardLink_id = $class->id;
        $work->subject_id = !empty($data['subject_id']) ? $data['subject_id'] : null;
        $work->teacher_id = $user->id;
        $work->description = $data['description'];
        $work->date = now()->toDateString();
        $work->submission_date = $data['submission_date'];
        $work->status = 'publish';
        $work->created_by = $user->id;
        $work->updated_by = $user->id;
        $work->save();

        HomeworkApproval::create([
            'homework_id' => $work->id,
            'status' => 'approved',
            'approved_by' => $user->id,
            'approved_at' => now()->toDateString(),
            'comments' => 'Set from Homework',
        ]);

        return $work;
    }

    public function teacherClasses(User $user)
    {
        $schoolId = $user->school_id;
        $year = SiteHelper::getAcademicYear($schoolId);
        $q = StandardLink::with('standard', 'section')
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $year->id);
        if ($user->isTeacher()) {
            $q->whereIn('id', SiteHelper::teacherClassIds($schoolId, $user->id));
        }
        return $q->get();
    }

    public function subjectsForClasses($classIds)
    {
        if (collect($classIds)->isEmpty()) {
            return collect();
        }

        $subjectIds = Teacherlink::whereIn('standardLink_id', $classIds)->pluck('subject_id')->unique();
        return Subject::whereIn('id', $subjectIds)->orderBy('name')->get();
    }
}
