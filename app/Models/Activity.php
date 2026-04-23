<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

class Activity extends SpatieActivity
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'admin_id',
        'log_name',
        'description',
        'subject_type',
        'subject_id',
        'causer_type',
        'causer_id',
        'properties',
        'batch_uuid',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'properties' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the admin (company owner) that owns this activity.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Get the user who performed this activity.
     */
    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'causer_id');
    }

    /**
     * Scope to get activities for a specific admin (company).
     */
    public function scopeForAdmin($query, $adminId)
    {
        return $query->where('admin_id', $adminId);
    }

    /**
     * Scope to get activities for a specific log name.
     */
    public function scopeForLog($query, $logName)
    {
        return $query->where('log_name', $logName);
    }

    /**
     * Scope to get activities within date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Get formatted activity description in Arabic.
     */
    public function getArabicDescription(): string
    {
        $descriptions = [
            'created' => 'قام بإنشاء',
            'updated' => 'قام بتحديث',
            'deleted' => 'قام بحذف',
            'viewed' => 'قام بعرض',
            'login' => 'قام بتسجيل الدخول',
            'logout' => 'قام بتسجيل الخروج',
        ];

        $subjectType = class_basename($this->subject_type);
        $action = $this->description;

        $arabicAction = $descriptions[$action] ?? $action;
        
        return "{$arabicAction} {$subjectType}";
    }

    /**
     * Get activity icon based on log name.
     */
    public function getIcon(): string
    {
        $icons = [
            'users' => 'users',
            'products' => 'package',
            'warehouses' => 'building',
            'invoices' => 'file-text',
            'movements' => 'arrow-up-down',
            'settings' => 'settings',
            'auth' => 'log-in',
            'default' => 'activity',
        ];

        return $icons[$this->log_name] ?? $icons['default'];
    }

    /**
     * Get activity color based on action.
     */
    public function getColor(): string
    {
        $colors = [
            'created' => 'green',
            'updated' => 'blue',
            'deleted' => 'red',
            'viewed' => 'gray',
            'login' => 'green',
            'logout' => 'orange',
        ];

        return $colors[$this->description] ?? 'gray';
    }
}
