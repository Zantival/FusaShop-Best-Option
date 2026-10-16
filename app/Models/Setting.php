<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'group', 'description'];

    public static function getValue(string $key, mixed $default = null): mixed
    {
        if (!Schema::hasTable('settings')) {
            return $default;
        }

        return Cache::remember("setting.{$key}", 300, function () use ($key, $default) {
            $row = static::where('key', $key)->first();
            return $row ? $row->value : $default;
        });
    }

    public static function setValue(string $key, mixed $value, string $group = 'general', ?string $description = null): self
    {
        $setting = static::updateOrCreate(
            ['key' => $key],
            array_filter([
                'value' => (string) $value,
                'group' => $group,
                'description' => $description,
            ], fn ($v) => $v !== null)
        );

        Cache::forget("setting.{$key}");
        return $setting;
    }

    public static function commissionRate(): float
    {
        return (float) static::getValue('commission_rate', 8.0);
    }

    public static function bannerFee(): int
    {
        return (int) static::getValue('banner_fee', 150000);
    }

    public static function pqrsSlaBusinessDays(): int
    {
        return (int) static::getValue('pqrs_sla_business_days', 15);
    }
}
