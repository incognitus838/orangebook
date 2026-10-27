<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PeopleGroup extends Model
{
    protected $fillable = ['school_id', 'name'];

    public function members()
    {
        return $this->belongsToMany(User::class, 'people_group_members', 'people_group_id', 'user_id');
    }
}
