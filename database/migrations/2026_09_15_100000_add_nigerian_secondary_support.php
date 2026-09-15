<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_terms', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('school_id');
            $table->unsignedInteger('academic_year_id');
            $table->string('name');
            $table->string('slug');
            $table->unsignedTinyInteger('term_order');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestamps();

            $table->foreign('school_id')->references('id')->on('schools');
            $table->foreign('academic_year_id')->references('id')->on('academic_years');
            $table->unique(['school_id', 'academic_year_id', 'slug']);
        });

        Schema::create('school_academic_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('school_id');
            $table->unsignedInteger('academic_year_id');
            $table->unsignedTinyInteger('pass_mark')->default(40);
            $table->unsignedTinyInteger('ca_weight')->default(30);
            $table->unsignedTinyInteger('exam_weight')->default(70);
            $table->string('grading_scale')->default('waec');
            $table->json('grade_bands')->nullable();
            $table->timestamps();

            $table->foreign('school_id')->references('id')->on('schools');
            $table->foreign('academic_year_id')->references('id')->on('academic_years');
            $table->unique(['school_id', 'academic_year_id']);
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->string('mark', 16)->default('present')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn('mark');
        });
        Schema::dropIfExists('school_academic_settings');
        Schema::dropIfExists('academic_terms');
    }
};
