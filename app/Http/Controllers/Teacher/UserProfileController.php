<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Teacher;

use App\Http\Requests\ChangeAvatarRequest;
use App\Http\Requests\ChangePasswordRequest;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Userprofile;
use App\Traits\LogActivity;
use App\Traits\Common;
use App\Models\User;
use Exception;
use Hash;

class UserProfileController extends Controller
{
    use Common;
    use LogActivity;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function ChangePassword()
    {
        return view('/teacher/changepassword');
    }
 
    /**
     * Updates the password of the specified user.
     * 
     * @param \Illuminate\Http\Request $request 
     * 
     * @return \Illuminate\Http\Response
     */
    public function updateChangePassword(ChangePasswordRequest $request)
    {
        $user = User::find(Auth::id());
        $hashedPassword = $user->password;

        if($hashedPassword!='')
        { 
            $user->password = Hash::make($request->newpassword);
            $user->save();

            $ip= $this->getRequestIP();
            $this->doActivityLog(
                $user,
                Auth::user(),
                ['ip' => $ip,'details' => $_SERVER['HTTP_USER_AGENT']],
                LOGNAME_CHANGE_PASSWORD,
                'Changed Profile Password.'                        
            );        
        } 
           
        return redirect()->back()->with('successmessage', 'Password saved.');
    }

    public function edit()
    {
        return view('/teacher/editprofile');
    }

    public function updateProfile(Request $request)
    {
        $user = User::findOrFail(Auth::id());
        $data = $request->validate([
            'name' => 'required|string|max:80|unique:users,name,'.$user->id,
            'email' => 'required|email|max:120|unique:users,email,'.$user->id,
            'phone' => 'nullable|string|max:20',
        ]);
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->mobile_no = $data['phone'] ?: $user->mobile_no;
        $user->save();
        $parts = preg_split('/\s+/', trim($data['name']), 2);
        $profile = Userprofile::where('user_id', $user->id)->first();
        if ($profile) {
            $profile->firstname = $parts[0];
            $profile->lastname = $parts[1] ?? $profile->lastname;
            $profile->save();
        }
        return redirect('/teacher/editprofile')->with('successmessage', 'Profile saved.');
    }  

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getavatar()
    {
        $userprofile = Userprofile::where('user_id', Auth::id())->first();
        $array=[];

        if(Auth::user())
        {
            $array['avatar'] = $this->getFilePath($userprofile->avatar);
            $array['id']=$userprofile->id;
        }

        return $array;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function changeavatar(Request $request)
    {   
        return view('/teacher/changeavatar');
    }
 
    /**
     * Updates the avatar image for specified user.
     * 
     * @param \Illuminate\Http\Request $request 
     * 
     * @return \Illuminate\Http\Response
     */
    public function updatechangeavatar(ChangeAvatarRequest $request)
    {
        try
        {
            $userprofile = Userprofile::where('user_id', Auth::id())->first();
            if (!$userprofile) {
                return redirect()->back()->with('failmessage', 'No profile to attach a photo to.');
            }
            $path = \Storage::putFile(Auth::user()->school->slug.'/uploads/avatars', $request->avatar, 'public');
            if ($path != '') {
                $userprofile->avatar = $path;
                $userprofile->save();
            }

            $ip= $this->getRequestIP();
            $this->doActivityLog(
                $userprofile,
                Auth::user(),
                ['ip' => $ip, 'details' => $_SERVER['HTTP_USER_AGENT'] ],
                LOGNAME_CHANGE_AVATAR,
                'Changed Avatar'
            );
            return redirect()->back()->with('successmessage', 'Photo saved.');
        }
        catch(Exception $e)
        {
            return redirect()->back()->with('failmessage', 'Could not save that photo.');
        }
    }
}