<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolApplication extends Model
{
    protected $fillable = [
        'kind',
        'school_name',
        'admin_name',
        'email',
        'phone',
        'student_count',
        'notes',
        'status',
    ];

    public function kindLabel(): string
    {
        return match ($this->kind) {
            'ngo' => 'NGO / programme',
            'training' => 'Training firm',
            default => 'School',
        };
    }
}
