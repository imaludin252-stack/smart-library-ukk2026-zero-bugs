<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function get(string $key, string $default = ''): string
    {
        $settings = self::semua();

        return $settings[$key] ?? $default;
    }

    public static function set(string $key, ?string $value): void
    {
        self::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget('settings.all');
    }

    /** @return array<string, string> */
    public static function semua(): array
    {
        return Cache::rememberForever('settings.all', function () {
            return self::query()->pluck('value', 'key')->all();
        });
    }
}
