<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_invites', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->after('email');
            $table->unsignedInteger('class_id')->nullable()->after('usergroup_id');
            $table->json('subjects')->nullable()->after('class_id');
        });

        Schema::table('notice_board', function (Blueprint $table) {
            $table->unsignedInteger('send_mail_id')->nullable()->after('audience');
            $table->text('full_body')->nullable()->after('description');
            $table->unsignedInteger('created_by')->nullable()->after('full_body');
        });
    }

    public function down(): void
    {
        Schema::table('staff_invites', function (Blueprint $table) {
            $table->dropColumn(['phone', 'class_id', 'subjects']);
        });
        Schema::table('notice_board', function (Blueprint $table) {
            $table->dropColumn(['send_mail_id', 'full_body', 'created_by']);
        });
    }
};
