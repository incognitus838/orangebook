<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // School-wide catalogue for the year. Class rows stay in `subjects`
        // (one per standard/section) plus class_teacher_links. The catalogue
        // is the distinct list admins edit; applying copies into subjects.
        Schema::create('subject_catalog', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('school_id');
            $table->unsignedInteger('academic_year_id');
            $table->string('name');
            $table->string('code', 16)->nullable();
            $table->string('type', 20)->default('core');
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->unique(['school_id', 'academic_year_id', 'name'], 'subject_catalog_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_catalog');
    }
};
