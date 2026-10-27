<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notice_board', function (Blueprint $table) {
            if (!Schema::hasColumn('notice_board', 'audience')) {
                $table->text('audience')->nullable();
            }
        });
        Schema::create('people_groups', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('school_id');
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('people_group_members', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('people_group_id');
            $table->unsignedInteger('user_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('people_group_members');
        Schema::dropIfExists('people_groups');
        Schema::table('notice_board', function (Blueprint $table) {
            if (Schema::hasColumn('notice_board', 'audience')) {
                $table->dropColumn('audience');
            }
        });
    }
};
