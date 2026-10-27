<?php

namespace Tests\Feature;

use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\User;
use App\Services\LearnerEnrollmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Hall;
use Tests\TestCase;

class ParentListTest extends TestCase
{
    use RefreshDatabase;

    public function test_class_filter_returns_only_that_class_and_is_tenant_scoped(): void
    {
        $a = Hall::open();
        $b = Hall::open();
        $enroll = app(LearnerEnrollmentService::class);
        $enroll->enroll($a['school'], [
            'first_name' => 'Uche',
            'last_name' => 'Abdullahi',
            'parent_name' => 'Mrs Okoro',
            'parent_email' => 'okoro@school.test',
        ], $a['class']);
        $enroll->enroll($b['school'], [
            'first_name' => 'Tunde',
            'last_name' => 'Bakare',
            'parent_name' => 'Mr Bakare',
            'parent_email' => 'bakare@other.test',
        ], $b['class']);

        $this->actingAs($a['admin'])->get('/admin/parents?class_id='.$a['class']->id)
            ->assertOk()
            ->assertSee('Mrs Okoro')
            ->assertDontSee('Mr Bakare');
    }

    public function test_grouped_view_lists_a_parent_under_each_child_class(): void
    {
        $hall = Hall::open();
        $standard2 = Standard::create([
            'school_id' => $hall['school']->id,
            'name' => 'JSS2',
            'slug' => 'jss2-'.$hall['school']->id,
            'order' => '02',
            'status' => 1,
        ]);
        $section = $hall['section'];
        $class2 = StandardLink::create([
            'school_id' => $hall['school']->id,
            'academic_year_id' => $hall['year']->id,
            'standard_id' => $standard2->id,
            'section_id' => $section->id,
            'status' => 1,
        ]);
        $enroll = app(LearnerEnrollmentService::class);
        $one = $enroll->enroll($hall['school'], [
            'first_name' => 'Uche',
            'last_name' => 'A',
            'parent_name' => 'Shared Parent',
            'parent_email' => 'shared@school.test',
        ], $hall['class']);
        $two = $enroll->enroll($hall['school'], [
            'first_name' => 'Ngozi',
            'last_name' => 'B',
            'parent_email' => 'shared@school.test',
            'parent_name' => 'Shared Parent',
        ], $class2);

        $html = $this->actingAs($hall['admin'])->get('/admin/parents?view=grouped')->assertOk()->getContent();
        $this->assertSame(2, substr_count($html, 'Shared Parent'));
        $this->assertNotNull($one['parent']);
        $this->assertSame($one['parent']->id, $two['parent']->id);
    }
}
