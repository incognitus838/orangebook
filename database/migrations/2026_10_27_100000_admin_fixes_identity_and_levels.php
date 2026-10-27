<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        try {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique(['name']);
            });
        } catch (\Throwable $e) {
            // Index already gone on some environments.
        }

        DB::table('users')->orderBy('id')->chunkById(200, function ($rows) {
            foreach ($rows as $row) {
                $reg = trim((string) $row->registration_number);
                if ($reg !== '') {
                    continue;
                }
                $profile = DB::table('userprofiles')->where('user_id', $row->id)->value('registration_number');
                $reg = trim((string) $profile);
                if ($reg === '') {
                    $reg = 'TMP/'.$row->school_id.'/'.$row->id;
                }
                DB::table('users')->where('id', $row->id)->update(['registration_number' => $reg]);
            }
        });

        $seen = [];
        $dups = DB::table('users')
            ->select('id', 'school_id', 'registration_number')
            ->whereNotNull('registration_number')
            ->orderBy('id')
            ->get();
        foreach ($dups as $row) {
            $key = $row->school_id.'|'.strtolower((string) $row->registration_number);
            if (isset($seen[$key])) {
                DB::table('users')->where('id', $row->id)->update([
                    'registration_number' => $row->registration_number.'-'.$row->id,
                ]);
            } else {
                $seen[$key] = true;
            }
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unique(['school_id', 'registration_number'], 'users_school_registration_unique');
        });

        Schema::table('standards', function (Blueprint $table) {
            if (! Schema::hasColumn('standards', 'is_entry')) {
                $table->boolean('is_entry')->default(false)->after('status');
            }
        });

        Schema::table('subjects', function (Blueprint $table) {
            if (! Schema::hasColumn('subjects', 'status')) {
                $table->boolean('status')->default(true);
            }
        });

        $this->nullableForeign('standards_link', 'class_teacher_id');
        $this->nullableForeign('class_teacher_links', 'teacher_id');
    }

    private function nullableForeign(string $table, string $column): void
    {
        try {
            Schema::table($table, function (Blueprint $blueprint) use ($column) {
                $blueprint->dropForeign([$column]);
            });
        } catch (\Throwable $e) {
        }
        Schema::table($table, function (Blueprint $blueprint) use ($column) {
            $blueprint->unsignedInteger($column)->nullable()->change();
        });
        try {
            Schema::table($table, function (Blueprint $blueprint) use ($column) {
                $blueprint->foreign($column)->references('id')->on('users')->nullOnDelete();
            });
        } catch (\Throwable $e) {
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_school_registration_unique');
            $table->unique('name');
        });
        Schema::table('standards', function (Blueprint $table) {
            if (Schema::hasColumn('standards', 'is_entry')) {
                $table->dropColumn('is_entry');
            }
        });
    }
};
