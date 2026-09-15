<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\NoticeBoard;
use App\Models\School;
use Illuminate\Database\Seeder;

class NoticeBoardSeeder extends Seeder
{
    public function run(): void
    {
        if (env('APP_ENV') != 'local' && env('APP_ENV') != 'development') {
            return;
        }

        $schools = School::where('status', 1)->get();
        foreach ($schools as $school) {
            $academic_year = AcademicYear::where([['school_id', $school->id], ['status', 1]])->first();
            if (!$academic_year) {
                continue;
            }
            NoticeBoard::factory(6)->create([
                'school_id' => $school->id,
                'academic_year_id' => $academic_year->id,
                'standardLink_id' => null,
                'type' => 'school',
                'status' => 1,
            ]);
        }
    }
}
