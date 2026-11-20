<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $table = 'site_settings';

    protected $fillable = [
        'key',
        'value',
    ];

    public static function getValue(string $key, ?string $default = null): ?string
    {
        return static::where('key', $key)->value('value') ?? $default;
    }

    /**
     * Resolve the valid public URL of a stored branding asset, or null if empty/missing.
     */
    public static function getAssetUrl(string $key): ?string
    {
        $value = static::getValue($key);

        if (blank($value)) {
            return null;
        }

        // Handle full URLs
        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }

        // Clean any leading slashes or accidental 'public/' prefixes
        $cleanPath = ltrim($value, '/\\');
        if (str_starts_with($cleanPath, 'public/')) {
            $cleanPath = substr($cleanPath, 7);
        }

        // Check if the file physically exists in public_path
        $fullPath = public_path($cleanPath);
        if (! file_exists($fullPath) || ! is_file($fullPath)) {
            // Also check storage/ symlink target if path starts with storage/
            if (str_starts_with($cleanPath, 'storage/')) {
                $storageRelative = substr($cleanPath, 8);
                $storageFull = storage_path('app/public/'.$storageRelative);
                if (file_exists($storageFull) && is_file($storageFull)) {
                    return asset($cleanPath);
                }
            }

            return null;
        }

        return asset($cleanPath);
    }
}
