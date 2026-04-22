<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'description'];

    /**
     * Get the value as the correct type.
     */
    public function getCastValueAttribute()
    {
        return match ($this->type) {
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $this->value,
            default => $this->value,
        };
    }

    /**
     * Static helper to get a setting value.
     */
    public static function getValue(string $key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        if ($setting) {
            return $setting->cast_value;
        }

        // Hardcoded fallbacks for safety
        $fallbacks = [
            'max_spouses' => 0,
            'allow_same_sex' => false,
            'allow_dead_login' => false,
            'allow_custom_metadata' => true,
            'priority_limit' => 10,
        ];

        return $fallbacks[$key] ?? $default;
    }
}
