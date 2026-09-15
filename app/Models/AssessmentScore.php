<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentScore extends Model
{
    protected $fillable = [
        'school_id',
        'academic_year_id',
        'academic_term_id',
        'standardLink_id',
        'subject_id',
        'student_id',
        'teacher_id',
        'ca',
        'midterm',
        'exam',
        'total',
        'grade',
        'remark',
        'published',
    ];

    protected $casts = [
        'published' => 'boolean',
        'ca' => 'float',
        'midterm' => 'float',
        'exam' => 'float',
        'total' => 'float',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function audits()
    {
        return $this->hasMany(AssessmentScoreAudit::class);
    }
}
