<?php

namespace Tests\Feature;

use App\Models\Section;
use App\Models\StandardLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Support\Hall;
use Tests\TestCase;

class LearnerImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_paste_creates_learners_and_is_tenant_scoped(): void
    {
        $a = Hall::open();
        $b = Hall::open();
        $this->actingAs($a['admin'])->post('/admin/students/import/preview', [
            'mode' => 'bulk',
            'class_id' => $a['class']->id,
            'grid' => "Ada\tOkoro\tfemale\nTunde\tBakare\tmale",
        ])->assertOk();
        $this->post('/admin/students/import/confirm')->assertRedirect('/admin/students/slips');
        $this->assertSame(2, User::where('school_id', $a['school']->id)->where('usergroup_id', User::STUDENT_USERGROUP_ID)->count());
        $this->assertSame(0, User::where('school_id', $b['school']->id)->where('usergroup_id', User::STUDENT_USERGROUP_ID)->count());
    }

    public function test_csv_import_matches_this_school_class_names(): void
    {
        $a = Hall::open();
        $b = Hall::open();
        $csv = "first_name,last_name,gender,class\nAda,Okoro,female,JSS1 A\nBad,Row,female,Unknown\n";
        $path = sys_get_temp_dir().'/ob-learners.csv';
        file_put_contents($path, $csv);
        $file = new UploadedFile($path, 'learners.csv', 'text/csv', null, true);

        $this->actingAs($a['admin'])->post('/admin/students/import/preview', [
            'mode' => 'csv',
            'file' => $file,
        ])->assertOk()->assertSee('Unknown class');
        $this->post('/admin/students/import/confirm')->assertRedirect('/admin/students/slips');
        $this->assertSame(1, User::where('school_id', $a['school']->id)->where('usergroup_id', User::STUDENT_USERGROUP_ID)->count());
        $this->assertSame(0, User::where('school_id', $b['school']->id)->where('usergroup_id', User::STUDENT_USERGROUP_ID)->count());
    }

    public function test_intake_auto_balances_across_arms(): void
    {
        $hall = Hall::open();
        $sectionB = Section::create(['school_id' => $hall['school']->id, 'name' => 'B', 'status' => 1]);
        StandardLink::create([
            'school_id' => $hall['school']->id,
            'academic_year_id' => $hall['year']->id,
            'standard_id' => $hall['standard']->id,
            'section_id' => $sectionB->id,
            'status' => 1,
        ]);

        $this->actingAs($hall['admin'])->post('/admin/students/import/preview', [
            'mode' => 'intake',
            'level_id' => $hall['standard']->id,
            'allocate' => 'auto',
            'grid' => "Ada\tOkoro\tfemale\nTunde\tBakare\tmale\nNgozi\tEze\tfemale\nChinedu\tOkeke\tmale",
        ])->assertOk();
        $this->post('/admin/students/import/confirm')->assertRedirect('/admin/students/slips');
        $this->assertSame(4, User::where('school_id', $hall['school']->id)->where('usergroup_id', User::STUDENT_USERGROUP_ID)->count());
        $counts = \App\Models\StudentAcademic::where('school_id', $hall['school']->id)
            ->selectRaw('standardLink_id, count(*) as n')
            ->groupBy('standardLink_id')
            ->pluck('n');
        $this->assertSame([2, 2], $counts->sort()->values()->all());
    }

    public function test_legacy_import_url_redirects(): void
    {
        $hall = Hall::open();
        $this->actingAs($hall['admin'])->get('/admin/import')->assertRedirect('/admin/students/import');
        $this->actingAs($hall['admin'])->get('/admin/downloadformat')->assertRedirect('/admin/students/import/template');
    }
}
