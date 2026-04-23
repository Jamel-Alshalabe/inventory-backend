<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $key
 * @property string $value
 * @property string $type
 */
class Setting extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $primaryKey = 'key';
    protected $keyType = 'string';
    
    protected $fillable = [
        'key',
        'value',
        'type',
    ];

    protected $casts = [
        'value' => 'string',
    ];

    public $timestamps = true;

    /**
     * Get a setting value by key
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();
        
        if (!$setting) {
            return $default;
        }

        return match ($setting->type) {
            'json' => json_decode($setting->value, true),
            'number' => is_numeric($setting->value) ? (float) $setting->value : $setting->value,
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            default => $setting->value,
        };
    }

    /**
     * Set a setting value
     */
    public static function set(string $key, mixed $value, string $type = 'string'): void
    {
        $processedValue = match ($type) {
            'json' => json_encode($value),
            'boolean' => $value ? '1' : '0',
            'number' => (string) $value,
            default => (string) $value,
        };

        static::updateOrCreate(
            ['key' => $key],
            ['value' => $processedValue, 'type' => $type]
        );
    }

    /**
     * Get all theme settings
     */
    public static function getThemeSettings(): array
    {
        return [
            'background_color' => static::get('background_color', '#08081a'),
            'surface_color' => static::get('surface_color', '#1a1a2e'),
            'text_color' => static::get('text_color', '#e0e0e0'),
            'primary_action_color' => static::get('primary_action_color', '#1a56db'),
            'secondary_action_color' => static::get('secondary_action_color', '#0ea5e9'),
            'font_family' => static::get('font_family', 'Noto Kufi Arabic'),
        ];
    }
}
