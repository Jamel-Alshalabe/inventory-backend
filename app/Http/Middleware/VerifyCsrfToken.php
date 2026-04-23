<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        '*',
        'api/*',
        'api/*/*',
        'api/*/*/*',
        'sanctum/csrf-cookie',
        'auth/*',
        'login',
        'logout',
    ];

    /**
     * Determine if the request has a URI that should pass through CSRF verification.
     */
    protected function inExceptArray($request): bool
    {
        // تخطي CSRF لجميع طلبات API
        if ($request->is('api/*') || $request->expectsJson()) {
            return true;
        }

        return parent::inExceptArray($request);
    }
}
