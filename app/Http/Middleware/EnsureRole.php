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

        $allowed = array_map(static fn (string $r) => Role::from($r), $roles);
        if ($roles !== [] && ! in_array($user->role, $allowed, true)) {
            return response()->json(['error' => 'صلاحيات غير كافية'], 403);
        }

        return $next($request);
    }
}
