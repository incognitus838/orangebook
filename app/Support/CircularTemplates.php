<?php

namespace App\Support;

class CircularTemplates
{
    public static function all(): array
    {
        return [
            'resumption' => [
                'label' => 'First Term begins',
                'subject' => 'First Term resumes on Monday — {school_name}',
                'body' => "Dear {parent_name},\n\nThis is to inform you that First Term resumes on Monday. Students should be in school by 7:45am in full uniform, with their writing materials.\n\nThank you.\n\n{school_name}",
            ],
            'results' => [
                'label' => 'Results published',
                'subject' => '{student_name}’s report card is ready — {school_name}',
                'body' => "Dear {parent_name},\n\nThe report card for {student_name} ({class}) is now on the Crest parent portal. Please log in to view scores, attendance and the form teacher’s comment.\n\n{school_name}",
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
                'body' => "Dear {parent_name},\n\nThis is a reminder that outstanding fees for {student_name} should be paid before the next resumption. See the bursar for the school account details.\n\n{school_name}",
            ],
        ];
    }
}
