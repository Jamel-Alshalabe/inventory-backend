<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $invoice_number
 * @property string $customer_name
 * @property array $items
 * @property float $total
 * @property string $status
 * @property int $warehouse_id
 */
class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'customer_name',
        'items',
        'total',
        'status',
        'warehouse_id',
    ];

    protected function casts(): array
    {
        return [
            'items' => 'array',
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
