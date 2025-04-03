<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureDeanHasDepartment
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && !$user->department_id) {
            // Instead of logging out and redirecting to a specific route,
            // just return a response with an explanation
            return response()->view('dean.access-denied', [
                'message' => 'Your account does not have a department assigned. Please contact the administrator.'
            ], 403);
        }

        return $next($request);
    }
}
