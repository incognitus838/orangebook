<?php

namespace Tests\Feature;

use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Support\Hall;
use Tests\TestCase;

class LearnerIdentityTest extends TestCase
{
    use RefreshDatabase;

    public function test_two_schools_can_share_a_learner_name(): void
    {
        $a = Hall::open(['name' => 'Ikeja Secondary']);
        $b = Hall::open(['name' => 'Yaba Skills']);

        $this->actingAs($a['admin'])->post('/admin/student/add', [
            'first_name' => 'Uche',
            'last_name' => 'Abdullahi',
            'class_id' => $a['class']->id,
        ])->assertRedirect('/admin/students/slips');

        $this->actingAs($b['admin'])->post('/admin/student/add', [
            'first_name' => 'Uche',
            'last_name' => 'Abdullahi',
            'class_id' => $b['class']->id,
        ])->assertRedirect('/admin/students/slips');

        $this->assertSame(2, User::where('name', 'Uche Abdullahi')->count());
    }

    public function test_email_and_phone_are_optional_and_no_fake_phone(): void
    {
        $hall = Hall::open();
        $this->actingAs($hall['admin'])->post('/admin/student/add', [
            'first_name' => 'Tunde',
            'last_name' => 'Bakare',
            'gender' => 'male',
            'class_id' => $hall['class']->id,
        ])->assertRedirect('/admin/students/slips');

        $user = User::where('school_id', $hall['school']->id)->where('name', 'Tunde Bakare')->first();
        $this->assertNotNull($user);
        $this->assertNull($user->email);
        $this->assertNull($user->mobile_no);
        $this->assertMatchesRegularExpression('/^[A-Z]{3}\/\d{2}\/\d{4}$/', $user->registration_number);
        $this->assertFalse(Hash::check('password', $user->password));
        $slips = session('login_slips');
        $this->assertNotEmpty($slips[0]['pin']);
        $this->assertTrue(Hash::check($slips[0]['pin'], $user->password));
    }

    public function test_student_page_is_id_based_and_tenant_scoped(): void
    {
        $a = Hall::open();
        $b = Hall::open();
        $this->actingAs($a['admin'])->post('/admin/student/add', [
            'first_name' => 'Ngozi',
            'last_name' => 'Eze',
            'class_id' => $a['class']->id,
        ]);
        $student = User::where('school_id', $a['school']->id)->where('name', 'Ngozi Eze')->first();

        $this->actingAs($a['admin'])->get('/admin/students/'.$student->id)->assertOk();
        $this->actingAs($a['admin'])->get('/admin/student/show/'.rawurlencode($student->name))
            ->assertRedirect('/admin/students/'.$student->id);
        $this->actingAs($b['admin'])->get('/admin/students/'.$student->id)->assertNotFound();
    }

    public function test_learner_logs_in_with_admission_number_and_pin(): void
    {
        $hall = Hall::open();
        $this->actingAs($hall['admin'])->post('/admin/student/add', [
            'first_name' => 'Chinedu',
            'last_name' => 'Okeke',
            'class_id' => $hall['class']->id,
        ]);
        $learner = User::where('name', 'Chinedu Okeke')->first();
        $pin = session('login_slips')[0]['pin'];
        $this->post('/logout');

        $this->post('/login', [
            'email' => $learner->registration_number,
            'password' => $pin,
        ])->assertRedirect('/student/dashboard');
        $this->assertAuthenticatedAs($learner);
    }

    public function test_training_learner_can_join_two_courses(): void
    {
        $hall = Hall::open(['kind' => 'training', 'name' => 'Yaba Skills']);
        $level2 = Standard::create([
            'school_id' => $hall['school']->id,
            'name' => 'Intermediate',
            'slug' => 'intermediate-'.$hall['school']->id,
            'order' => '02',
            'status' => 1,
        ]);
        $class2 = StandardLink::create([
            'school_id' => $hall['school']->id,
            'academic_year_id' => $hall['year']->id,
            'standard_id' => $level2->id,
            'section_id' => $hall['section']->id,
            'status' => 1,
        ]);

        $this->actingAs($hall['admin'])->post('/admin/student/add', [
            'first_name' => 'Ada',
            'last_name' => 'Okoro',
            'class_id' => $hall['class']->id,
            'admission_no' => 'YAB/26/0099',
        ])->assertRedirect('/admin/students/slips');

        $this->actingAs($hall['admin'])->post('/admin/student/add', [
            'first_name' => 'Ada',
            'last_name' => 'Okoro',
            'class_id' => $class2->id,
            'admission_no' => 'YAB/26/0099',
        ]);

        $learner = User::where('school_id', $hall['school']->id)->where('name', 'Ada Okoro')->first();
        $this->assertNotNull($learner);
        $this->assertSame(1, User::where('school_id', $hall['school']->id)->where('name', 'Ada Okoro')->count());
        $this->assertSame(2, StudentAcademic::where('user_id', $learner->id)->count());
    }
}
