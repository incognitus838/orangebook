<?php

namespace Tests\Feature;

use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\Subject;
use App\Models\SubjectCatalog;
use App\Models\Teacherlink;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Support\Hall;
use Tests\TestCase;

class ClassSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_level_arm_and_apply_subjects(): void
    {
        $hall = Hall::open();
        $this->actingAs($hall['admin']);

        $this->post('/admin/standardlinks/levels', ['name' => 'JSS2', 'is_entry' => 0])->assertRedirect();
        $this->post('/admin/standardlinks/arms', ['name' => 'B'])->assertRedirect();
        $jss2 = Standard::where('school_id', $hall['school']->id)->where('name', 'JSS2')->first();
        $this->post('/admin/standardlinks', [
            'standard_id' => $jss2->id,
            'section_ids' => [$hall['section']->id],
        ])->assertRedirect('/admin/standardlinks');

        $cat = $this->post('/admin/standardlinks/catalog', [
            'bulk' => "English\nMathematics\nBasic Science\nCivic Education\nYoruba",
        ]);
        $ids = SubjectCatalog::where('school_id', $hall['school']->id)->pluck('id')->all();
        $this->assertNotEmpty($ids, 'catalog empty: '.$cat->headers->get('Location').' '.json_encode(session()->all()));
        $classes = StandardLink::where('school_id', $hall['school']->id)->pluck('id')->all();
        $apply = $this->post('/admin/standardlinks/catalog/apply', [
            'catalog_ids' => $ids,
            'class_ids' => $classes,
        ]);
        $apply->assertRedirect();

        $this->assertSame(count($ids) * count($classes), Teacherlink::whereIn('standardLink_id', $classes)->count());
    }

    public function test_duplicate_subject_on_a_class_is_refused(): void
    {
        $hall = Hall::open();
        $this->actingAs($hall['admin']);
        $this->post('/admin/standardlinks/catalog', ['name' => 'English']);
        $cat = SubjectCatalog::where('school_id', $hall['school']->id)->first();
        $this->post('/admin/standardlinks/catalog/apply', [
            'catalog_ids' => [$cat->id],
            'class_ids' => [$hall['class']->id],
        ]);
        $this->post('/admin/standardlinks/catalog/apply', [
            'catalog_ids' => [$cat->id],
            'class_ids' => [$hall['class']->id],
        ]);
        $this->assertSame(1, Teacherlink::where('standardLink_id', $hall['class']->id)->count());
    }

    public function test_class_setup_is_tenant_scoped(): void
    {
        $a = Hall::open();
        $b = Hall::open();
        $this->actingAs($a['admin'])->post('/admin/standardlinks/levels', ['name' => 'SS1']);
        $this->actingAs($b['admin']);
        $level = Standard::where('school_id', $a['school']->id)->where('name', 'SS1')->first();
        $this->post('/admin/standardlinks/levels/'.$level->id, ['name' => 'Hacked'])->assertNotFound();
        $this->assertSame('SS1', $level->fresh()->name);
    }

    public function test_teacher_list_shows_every_subject(): void
    {
        $hall = Hall::open();
        $teacher = User::factory()->teacher()->create([
            'school_id' => $hall['school']->id,
            'email' => 'subjects-'.$hall['school']->id.'@school.test',
            'password' => Hash::make('secret123'),
            'status' => 'active',
            'name' => 'Ngozi Subjects',
        ]);
        Userprofile::create([
            'school_id' => $hall['school']->id,
            'user_id' => $teacher->id,
            'usergroup_id' => User::TEACHER_USERGROUP_ID,
            'firstname' => 'Ngozi',
            'lastname' => 'Subjects',
            'status' => 'active',
        ]);
        foreach (['English', 'Mathematics', 'Civic Education'] as $name) {
            $subject = Subject::create([
                'school_id' => $hall['school']->id,
                'academic_year_id' => $hall['year']->id,
                'standard_id' => $hall['standard']->id,
                'section_id' => $hall['section']->id,
                'name' => $name,
                'type' => 'core',
                'status' => 1,
            ]);
            Teacherlink::create([
                'school_id' => $hall['school']->id,
                'academic_year_id' => $hall['year']->id,
                'standardLink_id' => $hall['class']->id,
                'subject_id' => $subject->id,
                'teacher_id' => $teacher->id,
            ]);
        }

        $this->actingAs($hall['admin'])->get('/admin/teachers')
            ->assertOk()
            ->assertSee('ENGLISH', false)
            ->assertSee('MATHEMATICS', false)
            ->assertSee('CIVIC EDUCATION', false);
    }
}
