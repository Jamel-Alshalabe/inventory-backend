<?php

namespace App\Http\Middleware;

use App\Services\ActivityLogService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class LogActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Only log successful requests (2xx status codes)
        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            $this->logActivity($request);
        }

        return $response;
    }

    /**
     * Log the activity based on the request.
     */
    private function logActivity(Request $request): void
    {
        $method = $request->method();
        $route = $request->route();
        
        if (!$route) {
            return;
        }

        $routeName = $route->getName();
        $uri = $request->getRequestUri();

        // Skip logging for certain routes
        if ($this->shouldSkipLogging($uri, $routeName)) {
            return;
        }

        // Determine activity type and log name
        [$logName, $action] = $this->getActivityInfo($method, $uri, $routeName);

        if (!$logName || !$action) {
            return;
        }

        // Log the activity
        ActivityLogService::log(
            $action,
            null,
            $logName,
            [
                'method' => $method,
                'uri' => $uri,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]
        );
    }

    /**
     * Determine if the request should be skipped from logging.
     */
    private function shouldSkipLogging(string $uri, ?string $routeName): bool
    {
        $skipPatterns = [
            'activity-logs',
            'health-check',
            'sanctum',
            'csrf-cookie',
            'options',
        ];

        foreach ($skipPatterns as $pattern) {
            if (str_contains($uri, $pattern)) {
                return true;
            }
        }

        // Skip GET requests for viewing data (to avoid too many logs)
        $skipGetPatterns = [
            'dashboard',
            'products.index',
            'warehouses.index',
            'invoices.index',
            'movements.index',
            'users.index',
        ];

        if (request()->isMethod('GET') && $routeName) {
            foreach ($skipGetPatterns as $pattern) {
                if (str_contains($routeName, $pattern)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get activity information based on request.
     */
    private function getActivityInfo(string $method, string $uri, ?string $routeName): array
    {
        // API resource patterns
        if (preg_match('/\/api\/(products|warehouses|invoices|movements|users)(\/|$)/', $uri, $matches)) {
            $resource = $matches[1];
            $logName = $resource;
            
            return match ($method) {
                'POST' => [$logName, 'created'],
                'PUT', 'PATCH' => [$logName, 'updated'],
                'DELETE' => [$logName, 'deleted'],
                default => [null, null],
            };
        }

        // Auth activities
        if (str_contains($uri, 'login')) {
            return ['auth', 'login'];
        }

        if (str_contains($uri, 'logout')) {
            return ['auth', 'logout'];
        }

        // Settings
        if (str_contains($uri, 'settings')) {
            return ['settings', 'updated'];
        }

        // Reports
        if (str_contains($uri, 'reports')) {
            return ['reports', 'viewed'];
        }

        return [null, null];
    }
}
