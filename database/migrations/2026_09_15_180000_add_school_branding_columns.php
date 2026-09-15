<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->string('primary_color', 16)->default('#1B2A4A')->after('slug');
            $table->string('custom_domain')->nullable()->after('primary_color');
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn(['primary_color', 'custom_domain']);
        });
    }
};
