<?php

namespace App\Support;

use App\Models\School;

class CircularTemplates
{
    public static function all(?School $school = null): array
    {
        $kind = $school->kind ?? 'school';
        return match ($kind) {
            'ngo' => self::ngo($school),
            'training' => self::training($school),
            default => self::school($school),
        };
    }

    private static function school(?School $school): array
    {
        return [
            'resumption' => [
                'label' => 'First Term begins',
                'subject' => 'First Term resumes on Monday — {school_name}',
                'body' => "Dear {parent_name},\n\nFirst Term resumes on Monday. Students should be in school by 7:45am in full uniform, with their writing materials.\n\nThank you.\n\n{school_name}",
            ],
            'results' => [
                'label' => 'Results published',
                'subject' => '{student_name}’s report card is ready — {school_name}',
                'body' => "Dear {parent_name},\n\nThe report card for {student_name} ({class}) is now on the parent desk. Please log in to view scores, attendance and the form teacher’s comment.\n\n{school_name}",
            ],
            'pta' => [
                'label' => 'PTA meeting',
                'subject' => 'PTA meeting — {school_name}',
                'body' => "Dear {parent_name},\n\nYou are invited to the PTA meeting in the school hall this Saturday at 10:00am. Please come with your ward’s report folder.\n\n{school_name}",
            ],
            'absent' => [
                'label' => 'Child marked absent',
                'subject' => '{student_name} was marked absent today — {school_name}',
                'body' => "Dear {parent_name},\n\n{student_name} ({class}) was marked absent this morning. If this is a mistake, please reply to this mail or call the school office.\n\n{school_name}",
            ],
            'fees' => [
                'label' => 'School fees reminder',
                'subject' => 'School fees reminder — {school_name}',
                'body' => "Dear {parent_name},\n\nOutstanding fees for {student_name} should be paid before the next resumption. See the bursar for the school account details.\n\n{school_name}",
            ],
        ];
    }

    private static function ngo(?School $school): array
    {
        return [
            'session' => [
                'label' => 'Session begins',
                'subject' => 'Session starts on Monday — {school_name}',
                'body' => "Dear {parent_name},\n\nThe next session starts on Monday. Please be at the centre by 8:00am with your materials.\n\n{school_name}",
            ],
            'progress' => [
                'label' => 'Progress published',
                'subject' => 'Your progress record is ready — {school_name}',
                'body' => "Dear {parent_name},\n\nYour progress for {class} is now on your desk. Please log in to view scores and comments.\n\n{school_name}",
            ],
            'meeting' => [
                'label' => 'Programme meeting',
                'subject' => 'Programme meeting — {school_name}',
                'body' => "Dear {parent_name},\n\nThere is a programme meeting this Saturday at 10:00am. Attendance is expected.\n\n{school_name}",
            ],
            'absent' => [
                'label' => 'Marked absent',
                'subject' => 'You were marked absent today — {school_name}',
                'body' => "Dear {parent_name},\n\nYou were marked absent this morning ({class}). If this is a mistake, please reply to this mail or call the office.\n\n{school_name}",
            ],
            'stipend' => [
                'label' => 'Stipend / contribution',
                'subject' => 'Contribution reminder — {school_name}',
                'body' => "Dear {parent_name},\n\nThis is a reminder about the contribution for this session. See the office for details.\n\n{school_name}",
            ],
        ];
    }

    private static function training(?School $school): array
    {
        return [
            'cohort' => [
                'label' => 'Cohort starts',
                'subject' => 'Your cohort starts on Monday — {school_name}',
                'body' => "Dear {parent_name},\n\nYour cohort starts on Monday. Please be in by 8:00am with your tools and notebook.\n\n{school_name}",
            ],
            'results' => [
                'label' => 'Assessment published',
                'subject' => 'Your assessment is ready — {school_name}',
                'body' => "Dear {parent_name},\n\nYour assessment for {class} is now on your desk. Please log in to view scores.\n\n{school_name}",
            ],
            'cancelled' => [
                'label' => 'Class cancelled',
                'subject' => 'Today’s class is cancelled — {school_name}',
                'body' => "Dear {parent_name},\n\nToday’s class ({class}) is cancelled. We will write with the new date.\n\n{school_name}",
            ],
            'absent' => [
                'label' => 'Marked absent',
                'subject' => 'You were marked absent today — {school_name}',
                'body' => "Dear {parent_name},\n\nYou were marked absent this morning ({class}). If this is a mistake, please reply or call the studio.\n\n{school_name}",
            ],
            'fees' => [
                'label' => 'Tuition reminder',
                'subject' => 'Tuition reminder — {school_name}',
                'body' => "Dear {parent_name},\n\nOutstanding tuition should be paid before the next module. See the office for the account details.\n\n{school_name}",
            ],
        ];
    }
}
