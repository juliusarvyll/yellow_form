<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DeanRoleMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Super Admin and Admin can also access Dean panel
        if ($request->user() && ($request->user()->hasRole('Dean') || $request->user()->hasRole(['Super Admin', 'Admin']))) {
            return $next($request);
        }

        // Otherwise return 403 forbidden
        abort(403, 'You do not have permission to access the dean panel.');
    }
}
