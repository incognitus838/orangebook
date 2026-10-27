<?php

namespace App\Http\Middleware;

use Closure;

class BlockUnusedForkRoutes
{
    protected $blocked = [
        'holidays', 'holiday',
        'libraryactivity',
        'visitorlog', 'calllog', 'postalrecord',
        'classwall', 'feeds', 'feed',
        'phonenumbers',
        'activity',
        'tasks', 'task',
        'lessonplans', 'lessonplan',
        'assignments', 'assignment',
        'leaves', 'leave', 'myleaves', 'studentLeaves', 'studentLeave',
        'leavetypes', 'leavetype',
        'sentmessages', 'emergency', 'feedbacks', 'feedback',
        'magazines', 'magazine',
        'payroll',
        'quiz', 'exam', 'examrules',
        'video-conference', 'chats', 'chat',
        'addon',
    ];

    public function handle($request, Closure $next)
    {
        foreach ($request->segments() as $segment) {
            if (in_array($segment, $this->blocked, true)) {
                abort(404);
            }
        }

        return $next($request);
    }
}
