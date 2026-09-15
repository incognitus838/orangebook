<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->string('join_code', 24)->nullable()->unique()->after('custom_domain');
        });

        Schema::create('staff_invites', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('school_id');
            $table->unsignedInteger('invited_by')->nullable();
            $table->string('name');
            $table->string('email');
            $table->unsignedTinyInteger('usergroup_id')->default(5);
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->foreign('school_id')->references('id')->on('schools');
            $table->foreign('invited_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_invites');
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn('join_code');
        });
    }
};
