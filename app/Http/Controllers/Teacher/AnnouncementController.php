<?php

namespace App\Http\Controllers\Teacher;

class AnnouncementController extends \App\Http\Controllers\Admin\AnnouncementController
{
    protected string $role = 'teacher';
    protected string $base = '/teacher/announcements';
}
