<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminRoleMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! $request->user()->hasRole(['Super Admin', 'Admin'])) {
            // If user is a dean, redirect to dean panel
            if ($request->user() && $request->user()->hasRole('Dean')) {
                return redirect('/dean');
            }

            // Otherwise return 403 forbidden
            abort(403, 'You do not have permission to access the admin panel.');
        }

        return $next($request);
    }
}
