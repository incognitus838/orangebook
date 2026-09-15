<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TermResult extends Model
{
    protected $fillable = [
        'school_id',
        'academic_term_id',
        'standardLink_id',
        'student_id',
        'average',
        'position',
        'class_size',
        'grade',
        'teacher_comment',
        'published_at',
    ];

    protected $casts = [
        'average' => 'float',
        'published_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function term()
    {
        return $this->belongsTo(AcademicTerm::class, 'academic_term_id');
    }

    public function standardLink()
    {
        return $this->belongsTo(StandardLink::class, 'standardLink_id');
    }
}
