<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
        'label',
        'description',
        'sort_order',
    ];

    public static function get(string $key, $default = null)
    {
        return Cache::remember("setting.{$key}", 3600, function () use ($key, $default) {
            $setting = self::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    public static function set(string $key, $value): void
    {
        $setting = self::firstOrNew(['key' => $key]);
        $setting->value = $value;
        if (!$setting->exists) {
            $setting->label = ucwords(str_replace('_', ' ', $key));
        }
        $setting->save();

        Cache::forget("setting.{$key}");
    }

    public static function getGroup(string $group): array
    {
        return self::where('group', $group)
            ->orderBy('sort_order')
            ->get()
            ->pluck('value', 'key')
            ->toArray();
    }

    public static function getAllGrouped(): array
    {
        return self::orderBy('group')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('group')
            ->toArray();
    }
}