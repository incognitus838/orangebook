<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_academic_settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('ca_max')->default(30)->after('ca_weight');
            $table->unsignedTinyInteger('midterm_max')->default(20)->after('ca_max');
            $table->unsignedTinyInteger('exam_max')->default(50)->after('midterm_max');
        });

        Schema::create('assessment_scores', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('school_id');
            $table->unsignedInteger('academic_year_id');
            $table->unsignedInteger('academic_term_id');
            $table->unsignedInteger('standardLink_id');
            $table->unsignedInteger('subject_id');
            $table->unsignedInteger('student_id');
            $table->unsignedInteger('teacher_id')->nullable();
            $table->decimal('ca', 5, 2)->nullable();
            $table->decimal('midterm', 5, 2)->nullable();
            $table->decimal('exam', 5, 2)->nullable();
            $table->decimal('total', 5, 2)->nullable();
            $table->string('grade', 8)->nullable();
            $table->string('remark', 64)->nullable();
            $table->boolean('published')->default(false);
            $table->timestamps();

            $table->foreign('school_id')->references('id')->on('schools');
            $table->foreign('academic_year_id')->references('id')->on('academic_years');
            $table->foreign('academic_term_id')->references('id')->on('academic_terms');
            $table->foreign('standardLink_id')->references('id')->on('standards_link');
            $table->foreign('subject_id')->references('id')->on('subjects');
            $table->foreign('student_id')->references('id')->on('users');
            $table->unique(['academic_term_id', 'standardLink_id', 'subject_id', 'student_id'], 'score_unique');
        });

        Schema::create('assessment_score_audits', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('assessment_score_id');
            $table->unsignedInteger('changed_by');
            $table->string('field', 32);
            $table->string('old_value', 32)->nullable();
            $table->string('new_value', 32)->nullable();
            $table->timestamps();

            $table->foreign('assessment_score_id')->references('id')->on('assessment_scores')->onDelete('cascade');
            $table->foreign('changed_by')->references('id')->on('users');
        });

        Schema::create('term_results', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('school_id');
            $table->unsignedInteger('academic_term_id');
            $table->unsignedInteger('standardLink_id');
            $table->unsignedInteger('student_id');
            $table->decimal('average', 5, 2)->nullable();
            $table->unsignedInteger('position')->nullable();
            $table->unsignedInteger('class_size')->nullable();
            $table->string('grade', 8)->nullable();
            $table->text('teacher_comment')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->foreign('school_id')->references('id')->on('schools');
            $table->foreign('academic_term_id')->references('id')->on('academic_terms');
            $table->foreign('standardLink_id')->references('id')->on('standards_link');
            $table->foreign('student_id')->references('id')->on('users');
            $table->unique(['academic_term_id', 'standardLink_id', 'student_id'], 'term_result_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('term_results');
        Schema::dropIfExists('assessment_score_audits');
        Schema::dropIfExists('assessment_scores');
        Schema::table('school_academic_settings', function (Blueprint $table) {
            $table->dropColumn(['ca_max', 'midterm_max', 'exam_max']);
        });
    }
};
