<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffInvite extends Model
{
    protected $fillable = [
        'school_id',
        'invited_by',
        'name',
        'email',
        'usergroup_id',
        'token',
        'expires_at',
        'accepted_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function isOpen(): bool
    {
        return $this->accepted_at === null && $this->expires_at->isFuture();
    }
}
