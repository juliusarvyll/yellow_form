<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RedirectBasedOnRole
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip this middleware for asset requests or AJAX calls
        if ($request->ajax() || $this->isAssetRequest($request)) {
            return $next($request);
        }

        // Proceed with the request if user isn't authenticated
        if (!auth()->check()) {
            return $next($request);
        }

        $user = auth()->user();

        // Log the current user and request for debugging
        Log::info('RedirectBasedOnRole middleware:', [
            'user' => $user->name,
            'roles' => $user->roles->pluck('name'),
            'url' => $request->url(),
            'path' => $request->path(),
        ]);

        // Handle panel access
        if ($request->is('admin') || $request->is('admin/*')) {
            // If trying to access admin panel but not an admin
            if (!$user->hasRole(['Super Admin', 'Admin'])) {
                Log::warning('Unauthorized admin access attempt:', [
                    'user' => $user->name,
                    'roles' => $user->roles->pluck('name'),
                ]);

                // Redirect deans to dean panel
                if ($user->hasRole('Dean') && $user->department_id) {
                    return redirect('/dean');
                }

                // Otherwise show error
                abort(403, 'You do not have permission to access the admin panel.');
            }
        }

        if ($request->is('dean') || $request->is('dean/*')) {
            // If trying to access dean panel but not authorized
            if (!$user->hasRole(['Super Admin', 'Admin']) &&
                !($user->hasRole('Dean') && $user->department_id)) {
                Log::warning('Unauthorized dean panel access attempt:', [
                    'user' => $user->name,
                    'roles' => $user->roles->pluck('name'),
                ]);

                abort(403, 'You do not have permission to access the dean panel.');
            }
        }

        // Handle root path redirects based on role
        if ($request->is('/')) {
            if ($user->hasRole(['Super Admin', 'Admin'])) {
                return redirect('/admin');
            }

            if ($user->hasRole('Dean') && $user->department_id) {
                return redirect('/dean');
            }
        }

        return $next($request);
    }

    /**
     * Check if this is an asset request (CSS, JS, images)
     */
    protected function isAssetRequest(Request $request): bool
    {
        $path = $request->path();
        return preg_match('/\.(css|js|jpg|jpeg|png|gif|ico|svg|woff|woff2|ttf|eot)$/', $path);
    }
}
