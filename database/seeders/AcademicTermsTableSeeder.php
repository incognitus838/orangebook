<?php

namespace Database\Seeders;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\School;
use Illuminate\Database\Seeder;

class AcademicTermsTableSeeder extends Seeder
{
    public function run(): void
    {
        $schools = School::where('status', 1)->get();

        foreach ($schools as $school) {
            $year = AcademicYear::where('school_id', $school->id)->where('status', 1)->first();
            if (!$year) {
                continue;
            }

            $terms = [
                ['name' => 'First Term', 'slug' => 'first', 'order' => 1, 'start' => '2025-09-08', 'end' => '2025-12-12', 'current' => true],
                ['name' => 'Second Term', 'slug' => 'second', 'order' => 2, 'start' => '2026-01-12', 'end' => '2026-04-03', 'current' => false],
                ['name' => 'Third Term', 'slug' => 'third', 'order' => 3, 'start' => '2026-04-20', 'end' => '2026-07-17', 'current' => false],
            ];

            foreach ($terms as $term) {
                AcademicTerm::create([
                    'school_id' => $school->id,
                    'academic_year_id' => $year->id,
                    'name' => $term['name'],
                    'slug' => $term['slug'],
                    'term_order' => $term['order'],
                    'start_date' => $term['start'],
                    'end_date' => $term['end'],
                    'is_current' => $term['current'],
                ]);
            }
        }
    }
}
