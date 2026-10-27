<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DenySltEmployeeLists
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->hasRole('slt_employee')) {
            abort(403);
        }

        return $next($request);
    }
}
