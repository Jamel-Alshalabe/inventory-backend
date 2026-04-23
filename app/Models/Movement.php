<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MovementType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property MovementType $type
 * @property string $product_code
 * @property string $product_name
 * @property int $quantity
 * @property float $price
 * @property float $total
 * @property int $warehouse_id
 */
class Movement extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'product_code',
        'product_name',
        'quantity',
        'price',
        'total',
        'warehouse_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => MovementType::class,
            'quantity' => 'integer',
            'price' => 'float',
            'total' => 'float',
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
}
