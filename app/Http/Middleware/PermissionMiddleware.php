<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $permission
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $permission)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $user = Auth::user();
        
        // Check if user has the required permission using Laratrust
        if (!$user->hasPermission($permission) && !$user->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized - Insufficient permissions'], 403);
        }

        return $next($request);
    }

    /**
     * Check if user can see sidebar link
     */
    public static function canSeeLink(string $permission): bool
    {
        if (!Auth::check()) {
            return false;
        }

        $user = Auth::user();
        return $user->hasPermission($permission) || $user->hasRole('admin');
    }

    /**
     * Check if user can perform action (for buttons)
     */
    public static function canPerformAction(string $permission): bool
    {
        return self::canSeeLink($permission);
    }

    /**
     * Get user permissions for frontend
     */
    public static function getUserPermissions(): array
    {
        if (!Auth::check()) {
            return [];
        }

        $user = Auth::user();
        return [
            'can_manage_users' => $user->hasPermission('manage-users') || $user->hasRole('admin'),
            'can_manage_settings' => $user->hasPermission('manage-settings') || $user->hasRole('admin'),
            'can_manage_warehouses' => $user->hasPermission('manage-warehouses') || $user->hasRole('admin'),
            'can_manage_products' => $user->hasPermission('manage-products') || $user->hasRole('admin'),
            'can_manage_invoices' => $user->hasPermission('manage-invoices') || $user->hasRole('admin'),
            'can_view_reports' => $user->hasPermission('view-reports') || $user->hasRole('admin'),
            'is_admin' => $user->hasRole('admin'),
            'is_editor' => $user->hasRole('editor'),
            'is_user' => $user->hasRole('user'),
        ];
    }
}
