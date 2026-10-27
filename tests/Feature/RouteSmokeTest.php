<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Support\Hall;
use Tests\TestCase;

class RouteSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_get_routes_do_not_return_500(): void
    {
        $hall = Hall::open();
        $this->actingAs($hall['admin']);
        foreach ([
            '/admin/dashboard',
            '/admin/students',
            '/admin/student/add',
            '/admin/students/bulk',
            '/admin/students/import',
            '/admin/students/intake',
            '/admin/students/slips',
            '/admin/teachers',
            '/admin/teacher/add',
            '/admin/teachers/bulk',
            '/admin/teachers/import',
            '/admin/parents',
            '/admin/parent/add',
            '/admin/parents/import',
            '/admin/standardlinks',
            '/admin/announcements',
            '/admin/homeworks',
            '/admin/events',
            '/admin/reports',
            '/admin/admissions',
            '/admin/promotions',
            '/admin/marks',
            '/admin/schooldetails',
            '/admin/academics',
            '/admin/billing',
            '/admin/student/export',
            '/admin/teacher/export',
            '/admin/staff/export',
            '/admin/students/blockedstudents',
            '/admin/absentees/teacher',
            '/admin/people',
            '/admin/invite-teacher',
            '/admin/link-parent',
            '/admin/import',
            '/admin/import/teacher',
            '/admin/downloadformat',
            '/admin/settings/standards',
            '/admin/mail',
            '/admin/notices',
            '/admin/staffs',
            '/admin/staff/add',
            '/admin/holidays',
        ] as $url) {
            $status = $this->get($url)->getStatusCode();
            $this->assertNotSame(500, $status, $url.' returned 500');
        }
    }

    public function test_other_desks_and_id_routes_do_not_return_500(): void
    {
        $hall = Hall::open();
        $teacher = User::factory()->teacher()->create([
            'school_id' => $hall['school']->id,
            'email' => 't-'.$hall['school']->id.'@school.test',
            'password' => Hash::make('secret123'),
            'status' => 'active',
        ]);
        Userprofile::create([
            'school_id' => $hall['school']->id,
            'user_id' => $teacher->id,
            'usergroup_id' => User::TEACHER_USERGROUP_ID,
            'firstname' => 'Bola',
            'lastname' => 'Idris',
            'status' => 'active',
        ]);
        $this->actingAs($hall['admin'])->post('/admin/student/add', [
            'first_name' => 'Uche',
            'last_name' => 'Abdullahi',
            'class_id' => $hall['class']->id,
            'parent_name' => 'Mrs Okoro',
            'parent_email' => 'okoro-'.$hall['school']->id.'@school.test',
        ]);
        $student = User::where('school_id', $hall['school']->id)->where('usergroup_id', User::STUDENT_USERGROUP_ID)->first();
        $parent = User::where('school_id', $hall['school']->id)->where('usergroup_id', User::PARENT_USERGROUP_ID)->first();

        $this->actingAs($hall['admin']);
        $this->assertNotSame(500, $this->get('/admin/students/'.$student->id)->getStatusCode());

        $this->actingAs($teacher);
        foreach (['/teacher/dashboard', '/teacher/class', '/teacher/results', '/teacher/announcements', '/teacher/homeworks', '/teacher/events', '/teacher/mail', '/teacher/notices'] as $url) {
            $this->assertNotSame(500, $this->get($url)->getStatusCode(), $url);
        }

        $this->actingAs($student);
        foreach (['/student/dashboard', '/student/notices', '/student/homeworks', '/student/events'] as $url) {
            $this->assertNotSame(500, $this->get($url)->getStatusCode(), $url);
        }

        $this->actingAs($parent);
        foreach (['/parent/dashboard', '/parent/circulars'] as $url) {
            $this->assertNotSame(500, $this->get($url)->getStatusCode(), $url);
        }
    }
}
