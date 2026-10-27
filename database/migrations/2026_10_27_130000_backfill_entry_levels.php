<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $schools = DB::table('schools')->select('id', 'kind')->get();
        foreach ($schools as $school) {
            $already = DB::table('standards')
                ->where('school_id', $school->id)
                ->where('is_entry', 1)
                ->exists();
            if ($already) {
                continue;
            }

            $kind = $school->kind ?? 'school';
            $names = match ($kind) {
                'ngo' => ['Cohort 1'],
                'training' => ['Beginner', 'Cohort 1'],
                default => ['JSS1', 'SS1', 'Primary 1', 'Creche', 'Nursery 1'],
            };

            DB::table('standards')
                ->where('school_id', $school->id)
                ->whereIn('name', $names)
                ->update(['is_entry' => 1]);

            $still = DB::table('standards')
                ->where('school_id', $school->id)
                ->where('is_entry', 1)
                ->exists();
            if (! $still) {
                $first = DB::table('standards')
                    ->where('school_id', $school->id)
                    ->where('status', 1)
                    ->orderBy('order')
                    ->first();
                if ($first) {
                    DB::table('standards')->where('id', $first->id)->update(['is_entry' => 1]);
                }
            }
        }
    }

    public function down(): void
    {
        // Entry flags are data, not schema.
    }
};
