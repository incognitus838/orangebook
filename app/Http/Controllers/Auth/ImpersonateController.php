<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ImpersonateController extends Controller
{
    public function start($id)
    {
        $admin = Auth::user();
        if (! $admin || ! $admin->isAdmin()) {
            abort(403);
        }

        $target = User::where('id', $id)
            ->where('school_id', $admin->school_id)
            ->where('status', 'active')
            ->whereIn('usergroup_id', [
                User::TEACHER_USERGROUP_ID,
                User::STUDENT_USERGROUP_ID,
                User::PARENT_USERGROUP_ID,
                User::LIBRARIAN_USERGROUP_ID,
            ])
            ->firstOrFail();

        $admin->setImpersonating($target->id);
        session(['impersonator_id' => $admin->id]);

        return redirect($target->homePath())->with('successmessage', 'You are viewing '.$target->name.'’s desk.');
    }

    public function impersonate($id)
    {
        return $this->start($id);
    }

    public function studentimpersonate($id)
    {
        return $this->start($id);
    }

    public function librarianimpersonate($id)
    {
        return $this->start($id);
    }

    public function schoolAdminimpersonate($id)
    {
        $actor = Auth::user();
        if (! $actor || ! $actor->isSiteAdmin()) {
            abort(403);
        }
        $target = User::where('id', $id)->where('usergroup_id', User::SCHOOLADMIN_USERGROUP_ID)->firstOrFail();
        $actor->setImpersonating($target->id);
        session(['impersonator_id' => $actor->id]);

        return redirect('/admin/dashboard');
    }

    public function stop()
    {
        $home = '/admin/dashboard';
        $adminId = session('impersonator_id');
        if (Auth::user()) {
            Auth::user()->stopImpersonating();
        }
        session()->forget('impersonator_id');
        if ($adminId) {
            $admin = User::find($adminId);
            if ($admin) {
                $home = $admin->homePath();
            }
        }

        return redirect($home)->with('successmessage', 'Back at the admin desk.');
    }

    public function stopImpersonate()
    {
        return $this->stop();
    }
}
