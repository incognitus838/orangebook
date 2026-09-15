<?php

namespace Database\Seeders;

use DB;
use Illuminate\Database\Seeder;
use App\Models\School;

class AcademicYearsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $schools = School::where('status', 1)->get();

        foreach ($schools as $school) {
            DB::table('academic_years')->insert([
                'school_id'   => $school->id,
                'name'        => '2025/2026',
                'description' => 'Current Nigerian academic session',
                'start_date'  => '2025-09-08',
                'end_date'    => '2026-07-17',
                'status'      => 1,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            DB::table('academic_years')->insert([
                'school_id'   => $school->id,
                'name'        => '2026/2027',
                'description' => 'Next academic session',
                'start_date'  => '2026-09-07',
                'end_date'    => '2027-07-16',
                'status'      => 2,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }
}