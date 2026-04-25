<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $name
 */
class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'admin_id'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(Movement::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
