<?php

namespace Tests\Feature;

use App\Models\StaffInvite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Hall;
use Tests\TestCase;

class StaffImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_invite_creates_invites_not_passwords(): void
    {
        $hall = Hall::open();
        $this->actingAs($hall['admin'])->post('/admin/teachers/import/preview', [
            'mode' => 'bulk',
            'grid' => "Bola Idris,bola@school.test,08030000000,teaching",
        ])->assertOk();
        $this->post('/admin/teachers/import/confirm')->assertRedirect('/admin/teachers');
        $this->assertSame(1, StaffInvite::where('school_id', $hall['school']->id)->where('email', 'bola@school.test')->count());
        $this->assertSame(0, User::where('email', 'bola@school.test')->count());
    }

    public function test_duplicate_email_is_skipped_and_tenant_scoped(): void
    {
        $a = Hall::open();
        $b = Hall::open();
        $this->actingAs($a['admin'])->post('/admin/teachers/import/preview', [
            'mode' => 'bulk',
            'grid' => "Bola Idris,bola@school.test\nBola Idris,bola@school.test\nAda,ada@other.test",
        ])->assertOk();
        $this->post('/admin/teachers/import/confirm');
        $this->assertSame(2, StaffInvite::where('school_id', $a['school']->id)->count());
        $this->assertSame(0, StaffInvite::where('school_id', $b['school']->id)->count());
    }

    public function test_csv_bad_row_reports_and_imports_the_rest(): void
    {
        $hall = Hall::open();
        $csv = "name,email,phone,role\nGood Teacher,good@school.test,,teaching\nBad,not-an-email,,teaching\n";
        $path = sys_get_temp_dir().'/ob-staff.csv';
        file_put_contents($path, $csv);
        $file = new \Illuminate\Http\UploadedFile($path, 'staff.csv', 'text/csv', null, true);
        $this->actingAs($hall['admin'])->post('/admin/teachers/import/preview', [
            'mode' => 'csv',
            'file' => $file,
        ])->assertOk()->assertSee('Bad email');
        $this->post('/admin/teachers/import/confirm');
        $this->assertSame(1, StaffInvite::where('school_id', $hall['school']->id)->count());
        $this->assertTrue(StaffInvite::where('email', 'good@school.test')->exists());
    }
}
