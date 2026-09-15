<?php

namespace Database\Seeders;

use DB;
use Illuminate\Database\Seeder;
use App\Models\AcademicYear;
use App\Helpers\SiteHelper;
use App\Models\School;

class HolidaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $schools = School::where('status', 1)->get();

        $holidays_2026 = [
            ['New Year', '2026-01-01', '2026-01-01'],
            ['Good Friday', '2026-04-03', '2026-04-03'],
            ['Easter Monday', '2026-04-06', '2026-04-06'],
            ['Workers’ Day', '2026-05-01', '2026-05-01'],
            ['Democracy Day', '2026-06-12', '2026-06-12'],
            ['Eid-el-Kabir', '2026-05-28', '2026-05-29'],
            ['Eid-el-Maulud', '2026-08-26', '2026-08-26'],
            ['Independence Day', '2026-10-01', '2026-10-01'],
            ['Christmas', '2026-12-25', '2026-12-25'],
            ['Boxing Day', '2026-12-26', '2026-12-26'],
        ];

        $holidays_2027 = [
            ['New Year', '2027-01-01', '2027-01-01'],
            ['Good Friday', '2027-03-26', '2027-03-26'],
            ['Easter Monday', '2027-03-29', '2027-03-29'],
            ['Workers’ Day', '2027-05-01', '2027-05-01'],
            ['Democracy Day', '2027-06-12', '2027-06-12'],
            ['Independence Day', '2027-10-01', '2027-10-01'],
            ['Christmas', '2027-12-25', '2027-12-25'],
            ['Boxing Day', '2027-12-26', '2027-12-26'],
        ];

        foreach ($schools as $school) {

            $academic_year = AcademicYear::where([
                ['school_id', $school->id],
                ['status', 1]
            ])->first();

            foreach ([$holidays_2026, $holidays_2027] as $yearHolidays) {
                foreach ($yearHolidays as $h) {
                    DB::table('events')->insert([
                        'school_id' => $school->id,
                        'academic_year_id' => $academic_year->id,
                        'select_type' => 'school',
                        'title' => $h[0],
                        'category' => 'holidays',
                        'start_date' => $h[1] . ' 00:00:00',
                        'end_date' => $h[2] . ' 00:00:00',
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }
        }       
    }
}