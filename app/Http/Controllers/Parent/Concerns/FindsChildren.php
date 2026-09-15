<?php

namespace App\Http\Controllers\Parent\Concerns;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

trait FindsChildren
{
    protected function children()
    {
        return Auth::user()->childUsers()->with(['studentAcademicLatest.standardLink.standard', 'studentAcademicLatest.standardLink.section'])->get();
    }

    protected function childOrFail($id): User
    {
        $child = Auth::user()->childUsers()->where('users.id', $id)->first();
        if (!$child) {
            abort(403);
        }
        return $child;
    }
}
