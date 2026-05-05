<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $admin_id
 * @property string $first_name
 * @property string|null $last_name
 * @property string|null $email
 * @property string|null $phone_number
 * @property string|null $whatsapp_number
 * @property string|null $address
 * @property string|null $company_name
 * @property string|null $company_address
 */
class Client extends Model
{
    protected $fillable = [
        'admin_id',
        'first_name',
        'last_name',
        'email',
        'phone_number',
        'whatsapp_number',
        'address',
        'company_name',
        'company_address',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
