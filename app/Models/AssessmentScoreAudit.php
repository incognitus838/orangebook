<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentScoreAudit extends Model
{
    protected $fillable = [
        'assessment_score_id',
        'changed_by',
        'field',
        'old_value',
        'new_value',
    ];

    public function changer()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
