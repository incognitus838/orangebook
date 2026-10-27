<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubjectCatalog extends Model
{
    protected $table = 'subject_catalog';

    protected $fillable = [
        'school_id', 'academic_year_id', 'name', 'code', 'type', 'status',
    ];
}
