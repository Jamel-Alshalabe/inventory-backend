<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $action
 * @property string $detail
 * @property string $username
 */
class ActivityLog extends Model
{
    protected $table = 'activity_logs';
    public const UPDATED_AT = null;

    protected $fillable = ['action', 'detail', 'username'];
}
