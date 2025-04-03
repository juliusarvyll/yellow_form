<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminPanelAccessMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // If user is not logged in, let the authentication middleware handle it
        if (!$user) {
            return $next($request);
        }

        // Check if user has Super Admin or Admin role
        if ($user->hasRole(['Super Admin', 'Admin'])) {
            return $next($request);
        }

        // For deans, redirect to the dean panel
        if ($user->hasRole('Dean') && $user->department_id) {
            return redirect('/dean');
        }

        // If user has none of the required roles, show an access denied message
        return response()->view('errors.403', [
            'message' => 'You do not have permission to access the admin panel. Please contact a system administrator.'
        ], 403);
    }
}
