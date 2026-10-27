<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_applications', function (Blueprint $table) {
            $table->increments('id');
            $table->string('kind', 20)->default('school');
            $table->string('school_name');
            $table->string('admin_name');
            $table->string('email');
            $table->string('phone', 20)->nullable();
            $table->unsignedInteger('student_count')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_applications');
    }
};
