<?php

namespace Tests\Feature;

use App\Models\NoticeBoard;
use App\Models\SendMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Hall;
use Tests\TestCase;

class AnnouncementTest extends TestCase
{
    use RefreshDatabase;

    public function test_board_only_creates_no_mail(): void
    {
        $hall = Hall::open();
        $this->actingAs($hall['admin'])->post('/admin/announcements', [
            'title' => 'Assembly',
            'body' => 'Be in the hall at 8.',
            'delivery' => 'board',
            'everyone' => '1',
        ])->assertRedirect('/admin/announcements');
        $this->assertSame(1, NoticeBoard::where('school_id', $hall['school']->id)->count());
        $this->assertSame(0, SendMail::where('school_id', $hall['school']->id)->count());
        $this->assertNull(NoticeBoard::first()->send_mail_id);
    }

    public function test_board_and_email_creates_both_and_links_them(): void
    {
        $hall = Hall::open();
        $this->actingAs($hall['admin'])->post('/admin/announcements', [
            'title' => 'Fees',
            'body' => 'Fees are due next week.',
            'delivery' => 'email',
            'everyone' => '1',
        ])->assertRedirect('/admin/announcements');
        $notice = NoticeBoard::where('school_id', $hall['school']->id)->first();
        $this->assertNotNull($notice);
        $this->assertNotNull($notice->send_mail_id);
        $this->assertTrue(SendMail::where('school_id', $hall['school']->id)->where('id', $notice->send_mail_id)->exists());
    }

    public function test_audience_stays_in_this_school_and_old_urls_redirect(): void
    {
        $a = Hall::open();
        $b = Hall::open();
        $this->actingAs($a['admin'])->post('/admin/announcements', [
            'title' => 'Only A',
            'body' => 'Hello',
            'delivery' => 'board',
            'everyone' => '1',
        ]);
        $this->actingAs($b['admin'])->get('/admin/announcements')->assertOk()->assertDontSee('Only A');
        $this->actingAs($a['admin'])->get('/admin/mail')->assertRedirect('/admin/announcements');
        $this->actingAs($a['admin'])->get('/admin/notices')->assertRedirect('/admin/announcements');
        $foreign = NoticeBoard::where('school_id', $a['school']->id)->first();
        $this->actingAs($b['admin'])->post('/admin/announcements/'.$foreign->id.'/archive')->assertNotFound();
    }
}
