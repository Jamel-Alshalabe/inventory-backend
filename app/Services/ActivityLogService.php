<?php

namespace App\Services;

use App\Models\Activity;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Contracts\Activity as ActivityContract;

class ActivityLogService
{
    /**
     * Log an activity with admin_id.
     */
    public static function log(
        string $description,
        $subject = null,
        string $logName = 'default',
        array $properties = []
    ): ActivityContract {
        $activity = activity()
            ->inLog($logName)
            ->causedBy(Auth::user())
            ->performedOn($subject)
            ->withProperties($properties);

        // Set admin_id based on the current user's admin
        if (Auth::check()) {
            $adminId = self::getAdminId();
            $activity->tap(function (ActivityContract $activity) use ($adminId) {
                $activity->admin_id = $adminId;
            });
        }

        return $activity->log($description);
    }

    /**
     * Log user creation.
     */
    public static function logUserCreated($user): ActivityContract
    {
        return self::log(
            'created',
            $user,
            'users',
            [
                'username' => $user->username,
                'role' => $user->role,
                'created_by' => Auth::user()->username,
            ]
        );
    }

    /**
     * Log user update.
     */
    public static function logUserUpdated($user, $changes): ActivityContract
    {
        return self::log(
            'updated',
            $user,
            'users',
            [
                'username' => $user->username,
                'changes' => $changes,
                'updated_by' => Auth::user()->username,
            ]
        );
    }

    /**
     * Log user deletion.
     */
    public static function logUserDeleted($user): ActivityContract
    {
        return self::log(
            'deleted',
            $user,
            'users',
            [
                'username' => $user->username,
                'deleted_by' => Auth::user()->username,
            ]
        );
    }

    /**
     * Log product creation.
     */
    public static function logProductCreated($product): ActivityContract
    {
        return self::log(
            'created',
            $product,
            'products',
            [
                'name' => $product->name,
                'code' => $product->code,
                'quantity' => $product->quantity,
                'warehouse' => $product->warehouse->name,
                'created_by' => Auth::user()->username,
            ]
        );
    }

    /**
     * Log product update.
     */
    public static function logProductUpdated($product, $changes): ActivityContract
    {
        return self::log(
            'updated',
            $product,
            'products',
            [
                'name' => $product->name,
                'code' => $product->code,
                'changes' => $changes,
                'updated_by' => Auth::user()->username,
            ]
        );
    }

    /**
     * Log product deletion.
     */
    public static function logProductDeleted($product): ActivityContract
    {
        return self::log(
            'deleted',
            $product,
            'products',
            [
                'name' => $product->name,
                'code' => $product->code,
                'deleted_by' => Auth::user()->username,
            ]
        );
    }

    /**
     * Log warehouse creation.
     */
    public static function logWarehouseCreated($warehouse): ActivityContract
    {
        return self::log(
            'created',
            $warehouse,
            'warehouses',
            [
                'name' => $warehouse->name,
                'created_by' => Auth::user()->username,
            ]
        );
    }

    /**
     * Log invoice creation.
     */
    public static function logInvoiceCreated($invoice): ActivityContract
    {
        return self::log(
            'created',
            $invoice,
            'invoices',
            [
                'invoice_number' => $invoice->invoice_number,
                'customer_name' => $invoice->customer_name,
                'total' => $invoice->total,
                'created_by' => Auth::user()->username,
            ]
        );
    }

    /**
     * Log movement creation.
     */
    public static function logMovementCreated($movement): ActivityContract
    {
        return self::log(
            'created',
            $movement,
            'movements',
            [
                'type' => $movement->type,
                'product' => $movement->product->name,
                'quantity' => $movement->quantity,
                'warehouse' => $movement->warehouse->name,
                'created_by' => Auth::user()->username,
            ]
        );
    }

    /**
     * Log login activity.
     */
    public static function logLogin(): ActivityContract
    {
        return self::log(
            'login',
            null,
            'auth',
            [
                'username' => Auth::user()->username,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]
        );
    }

    /**
     * Log logout activity.
     */
    public static function logLogout(): ActivityContract
    {
        return self::log(
            'logout',
            null,
            'auth',
            [
                'username' => Auth::user()->username,
                'ip' => request()->ip(),
            ]
        );
    }

    /**
     * Get admin_id for the current user.
     */
    private static function getAdminId(): ?int
    {
        $user = Auth::user();

        // If user is admin themselves, return their own ID
        if ($user->hasRole('admin')) {
            return $user->id;
        }

        // If user has admin_id (employee), return their admin's ID
        if ($user->admin_id) {
            return $user->admin_id;
        }

        // If user is super_admin, return null (no admin filtering)
        if ($user->hasRole('super_admin')) {
            return null;
        }

        return null;
    }

    /**
     * Get activities for the current admin.
     */
    public static function getAdminActivities($limit = 50, $logName = null)
    {
        $query = Activity::query();

        // Filter by admin_id
        $adminId = self::getAdminId();
        if ($adminId) {
            $query->forAdmin($adminId);
        }

        // Filter by log name if specified
        if ($logName) {
            $query->forLog($logName);
        }

        return $query->with(['causer', 'subject'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Get activities paginated for the current admin.
     */
    public static function getAdminActivitiesPaginated($perPage = 20, $logName = null)
    {
        $query = Activity::query();

        // Filter by admin_id
        $adminId = self::getAdminId();
        if ($adminId) {
            $query->forAdmin($adminId);
        }

        // Filter by log name if specified
        if ($logName) {
            $query->forLog($logName);
        }

        return $query->with(['causer', 'subject'])
            ->latest()
            ->paginate($perPage);
    }
}
