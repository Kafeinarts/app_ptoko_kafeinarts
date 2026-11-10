<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $guarded = [];

    public static function get(string $key, mixed $default = null): mixed
    {
        return cache()->rememberForever('setting.'.$key, function () use ($key, $default) {
            return static::query()->where('key', $key)->value('value') ?? $default;
        });
    }

    public static function put(string $key, mixed $value, string $group = 'general', string $type = 'string'): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value, 'group' => $group, 'type' => $type]);
        cache()->forget('setting.'.$key);
    }
}
