<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property float $buy_price
 * @property float $sell_price
 * @property int $quantity
 * @property int $warehouse_id
 */
class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'buy_price',
        'sell_price',
        'quantity',
        'warehouse_id',
    ];

    protected function casts(): array
    {
        return [
            'buy_price' => 'float',
            'sell_price' => 'float',
            'quantity' => 'integer',
            'warehouse_id' => 'integer',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function scopeForWarehouse(Builder $q, ?int $warehouseId): Builder
    {
        return $warehouseId ? $q->where('warehouse_id', $warehouseId) : $q;
    }

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (! $term) {
            return $q;
        }
        $like = '%'.trim($term).'%';
        return $q->where(function (Builder $w) use ($like): void {
            $w->where('name', 'like', $like)->orWhere('code', 'like', $like);
        });
    }
}
