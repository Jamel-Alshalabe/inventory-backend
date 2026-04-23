<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'start_date',
        'end_date',
        'subscription_cost',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'subscription_cost' => 'decimal:2',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if subscription is currently active
     */
    public function isCurrentlyActive(): bool
    {
        return $this->is_active && 
               $this->start_date->lessThanOrEqualTo(now()) && 
               $this->end_date->greaterThanOrEqualTo(now());
    }

    /**
     * Check if subscription is expired
     */
    public function isExpired(): bool
    {
        return $this->end_date->lessThan(now());
    }

    /**
     * Get days remaining until expiration
     */
    public function getDaysRemaining(): int
    {
        if ($this->isExpired()) {
            return 0;
        }
        
        return now()->diffInDays($this->end_date);
    }

    /**
     * Scope to get only active subscriptions
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where('start_date', '<=', now())
                    ->where('end_date', '>=', now());
    }

    /**
     * Scope to get expired subscriptions
     */
    public function scopeExpired($query)
    {
        return $query->where('end_date', '<', now());
    }
}
