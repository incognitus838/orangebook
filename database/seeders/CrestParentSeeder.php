<?php

namespace Database\Seeders;

use App\Helpers\SiteHelper;
use App\Models\Attendance;
use App\Models\Notes;
use App\Models\ParentProfile;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\StudentParentLink;
use App\Models\User;
use App\Models\Userprofile;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CrestParentSeeder extends Seeder
{
    public function run(): void
    {
        $schoolId = 1;
        $year = SiteHelper::getAcademicYear($schoolId);
        $jss2 = Standard::where('school_id', $schoolId)->where('name', 'JSS2')->first();
        if (!$year || !$jss2) {
            return;
        }

        $class = StandardLink::where('school_id', $schoolId)
            ->where('academic_year_id', $year->id)
            ->where('standard_id', $jss2->id)
            ->whereHas('section', fn ($q) => $q->where('name', 'A'))
            ->first();
        if (!$class) {
            return;
        }

        $studentId = StudentAcademic::where('standardLink_id', $class->id)->orderBy('roll_number')->value('user_id');
        if (!$studentId) {
            return;
        }

        $parent = User::updateOrCreate(
            ['email' => 'parent@crest.ng'],
            [
                'school_id' => $schoolId,
                'usergroup_id' => 7,
                'name' => 'Funke Abdullahi',
                'mobile_no' => '08035550202',
                'password' => Hash::make('password'),
                'status' => 'active',
                'email_verified' => true,
                'email_verified_at' => now(),
            ]
        );

        Userprofile::updateOrCreate(
            ['user_id' => $parent->id],
            [
                'school_id' => $schoolId,
                'usergroup_id' => 7,
                'firstname' => 'Funke',
                'lastname' => 'Abdullahi',
                'address' => 'Ikeja, Lagos, Nigeria',
            ]
        );

        ParentProfile::updateOrCreate(
            ['user_id' => $parent->id],
            [
                'school_id' => $schoolId,
                'relation' => 'mother',
            ]
        );

        StudentParentLink::updateOrCreate(
            ['parent_id' => $parent->id, 'student_id' => $studentId],
            ['school_id' => $schoolId, 'status' => 1]
        );

        $teacherId = $class->class_teacher_id;
        $note = new Notes();
        $note->school_id = $schoolId;
        $note->entity_name = 'student_remark';
        $note->entity_id = $studentId;
        $note->notes = 'Works well in class. Keep practising English comprehension at home.';
        $note->created_by = $teacherId;
        $note->updated_by = $teacherId;
        $note->save();

        foreach (range(0, 4) as $i) {
            $date = Carbon::today()->subDays($i)->toDateString();
            $mark = $i === 1 ? 'late' : ($i === 3 ? 'absent' : 'present');
            Attendance::updateOrCreate(
                [
                    'school_id' => $schoolId,
                    'academic_year_id' => $year->id,
                    'standardLink_id' => $class->id,
                    'user_id' => $studentId,
                    'date' => $date,
                    'session' => 'forenoon',
                ],
                [
                    'status' => in_array($mark, ['present', 'late'], true) ? 1 : 0,
                    'mark' => $mark,
                    'remarks' => $mark === 'absent' ? 'Fever' : null,
                    'recorded_by' => $teacherId,
                ]
            );
        }
    }
}
