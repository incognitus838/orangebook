<?php

namespace App\Http\Middleware;

use App\Support\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class ResolveTenant
{
    public function handle(Request $request, Closure $next)
    {
        $school = Tenant::resolve($request->getHost(), $request->query('tenant'));
        Tenant::set($school);

        View::share('tenantSchool', $school);
        View::share('tenantColor', $school->primary_color ?? '#1B2A4A');

        return $next($request);
    }
}
