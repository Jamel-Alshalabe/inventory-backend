<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int|null $admin_id
 * @property string $action
 * @property string $detail
 * @property string $username
 */
class ActivityLog extends Model
{
    protected $table = 'activity_log';

    protected $casts = [
        'properties' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $fillable = ['admin_id', 'log_name', 'description', 'subject_type', 'subject_id', 'causer_type', 'causer_id', 'properties', 'batch_uuid'];

    public function causer()
    {
        return $this->morphTo();
    }

    public function subject()
    {
        return $this->morphTo();
    }
}
