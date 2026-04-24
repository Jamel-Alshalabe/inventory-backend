<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Usage: ->middleware('role:admin') or ->middleware('role:admin,user')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['error' => 'غير مصرح'], 401);
        }

        // Debug logging
        \Log::info('EnsureRole middleware check', [
            'user_id' => $user->id,
            'user_roles' => $user->roles->pluck('name')->toArray(),
            'required_roles' => $roles,
            'request_path' => $request->path(),
            'request_method' => $request->method()
        ]);

        // Check if user has any of the required roles using Laratrust
        if ($roles !== []) {
            $hasRequiredRole = false;
            foreach ($roles as $role) {
                \Log::info('Checking role', [
                    'role' => $role,
                    'has_role' => $user->hasRole($role)
                ]);
                if ($user->hasRole($role)) {
                    $hasRequiredRole = true;
                    break;
                }
            }
            
            \Log::info('Role check result', [
                'has_required_role' => $hasRequiredRole
            ]);
            
            if (! $hasRequiredRole) {
                \Log::error('Access denied - insufficient permissions', [
                    'user_id' => $user->id,
                    'user_roles' => $user->roles->pluck('name')->toArray(),
                    'required_roles' => $roles
                ]);
                return response()->json(['error' => 'صلاحيات غير كافية'], 403);
            }
        }

        return $next($request);
    }
}
