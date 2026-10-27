<?php

namespace Tests\Feature;

use App\Models\StudentAcademic;
use App\Models\StudentParentLink;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Support\Hall;
use Tests\TestCase;

class ImpersonateDeskTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_opens_teacher_student_and_parent_desks(): void
    {
        $hall = Hall::open();
        $teacher = $this->person($hall, User::TEACHER_USERGROUP_ID, 'Ada Teacher');
        $student = $this->person($hall, User::STUDENT_USERGROUP_ID, 'Ben Student');
        StudentAcademic::create([
            'school_id' => $hall['school']->id,
            'academic_year_id' => $hall['year']->id,
            'user_id' => $student->id,
            'standardLink_id' => $hall['class']->id,
            'roll_number' => $student->registration_number,
        ]);
        $parent = $this->person($hall, User::PARENT_USERGROUP_ID, 'Cara Parent');
        StudentParentLink::create([
            'school_id' => $hall['school']->id,
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'status' => 1,
        ]);

        $this->actingAs($hall['admin'])->get('/admin/teacher/show/'.rawurlencode($teacher->name))
            ->assertOk()->assertSee('Open desk', false);
        $this->get('/admin/students/'.$student->id)->assertOk()->assertSee('Open desk', false);
        $this->get('/admin/parent/show/'.rawurlencode($parent->name))->assertOk()->assertSee('Open desk', false);
        $this->get('/admin/teachers')->assertOk()->assertSee('Open desk', false);
        $this->get('/admin/students')->assertOk()->assertSee('Open desk', false);
        $this->get('/admin/parents')->assertOk()->assertSee('Open desk', false);

        $this->actingAs($hall['admin'])->post('/admin/people/'.$teacher->id.'/open')
            ->assertRedirect('/teacher/dashboard');
        $this->get('/teacher/dashboard')->assertOk()->assertSee('Ada Teacher', false)->assertSee('You are viewing', false);
        $this->get('/impersonate/stop')->assertRedirect('/admin/dashboard');

        $this->actingAs($hall['admin'])->post('/admin/people/'.$student->id.'/open')
            ->assertRedirect('/student/dashboard');
        $this->get('/student/dashboard')->assertOk()->assertSee('You are viewing', false);
        $this->get('/impersonate/stop')->assertRedirect('/admin/dashboard');

        $this->actingAs($hall['admin'])->post('/admin/people/'.$parent->id.'/open')
            ->assertRedirect('/parent/dashboard');
        $this->get('/parent/dashboard')->assertOk()->assertSee('Ben Student', false)->assertSee('You are viewing', false);

        $this->get('/impersonate/stop')->assertRedirect('/admin/dashboard');
        $this->actingAs($hall['admin'])->get('/admin/dashboard')->assertOk();
    }

    public function test_teacher_student_and_parent_can_log_in(): void
    {
        $hall = Hall::open();
        $teacher = $this->person($hall, User::TEACHER_USERGROUP_ID, 'Ada Teacher');
        $student = $this->person($hall, User::STUDENT_USERGROUP_ID, 'Ben Student');
        StudentAcademic::create([
            'school_id' => $hall['school']->id,
            'academic_year_id' => $hall['year']->id,
            'user_id' => $student->id,
            'standardLink_id' => $hall['class']->id,
            'roll_number' => $student->registration_number,
        ]);
        $parent = $this->person($hall, User::PARENT_USERGROUP_ID, 'Cara Parent');
        StudentParentLink::create([
            'school_id' => $hall['school']->id,
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'status' => 1,
        ]);

        $this->post('/login', ['email' => $teacher->email, 'password' => 'secret123'])
            ->assertRedirect('/teacher/dashboard');
        $this->post('/logout');

        $this->post('/login', ['email' => $student->email, 'password' => 'secret123'])
            ->assertRedirect('/student/dashboard');
        $this->post('/logout');

        $this->post('/login', ['email' => $parent->email, 'password' => 'secret123'])
            ->assertRedirect('/parent/dashboard');
    }

    public function test_cannot_open_another_school_desk(): void
    {
        $a = Hall::open();
        $b = Hall::open();
        $foreign = $this->person($b, User::TEACHER_USERGROUP_ID, 'Other Teacher');

        $this->actingAs($a['admin'])->post('/admin/people/'.$foreign->id.'/open')->assertNotFound();
    }

    private function person(array $hall, int $group, string $name): User
    {
        $user = User::factory()->create([
            'school_id' => $hall['school']->id,
            'usergroup_id' => $group,
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)).'-'.$hall['school']->id.'@desk.test',
            'password' => Hash::make('secret123'),
            'status' => 'active',
        ]);
        Userprofile::create([
            'school_id' => $hall['school']->id,
            'user_id' => $user->id,
            'usergroup_id' => $group,
            'firstname' => explode(' ', $name)[0],
            'lastname' => explode(' ', $name)[1] ?? '',
            'status' => 'active',
        ]);

        return $user;
    }
}
