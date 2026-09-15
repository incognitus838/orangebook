<?php

namespace App\Http\Middleware;

use Closure;

class MustBeSchoolAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
       
        if(method_exists(\Auth::user(), 'isSiteAdmin') && \Auth::user()->isSiteAdmin())
        {
            return redirect('/platform');
        }

        if(\Auth::user()->isAdmin())
        {
            return $next($request);
        }

        if(\Auth::user()->isTeacher())
        {
            return redirect('/teacher/dashboard');
        }

        if(\Auth::user()->isStudent())
        {
            return redirect('/student/dashboard');
        }

        if(\Auth::user()->isParent())
        {
            return redirect('/parent/dashboard');
        }
        
        if(\Auth::user()->isLibrarian())
        {
            return redirect('/library/dashboard');          
        }
        
        if(\Auth::user()->isAlumni())
        {
            return redirect('/alumni/dashboard');          
        }
        
        if(\Auth::user()->isReceptionist())
        {
            return redirect('/receptionist/dashboard');          
        }
        
        if(\Auth::user()->isAccountant())
        {
            return redirect('/accountant/dashboard');          
        }
        if(\Auth::user()->isStockKeeper())
        {
            return redirect('/stock/dashboard');          
        }
            
        abort(404);
    }
}