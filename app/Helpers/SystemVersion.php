<?php

namespace App\Helpers;

use Illuminate\Support\Facades\File;

class SystemVersion
{
    protected static ?object $versionData = null;

    /**
     * Get the current version data from version.json.
     */
    public static function getData(): ?object
    {
        if (self::$versionData) {
            return self::$versionData;
        }

        $path = base_path('version.json');

        if (File::exists($path)) {
            self::$versionData = json_decode(File::get($path));
        }

        return self::$versionData;
    }

    /**
     * Get the current version name string.
     */
    public static function getName(): string
    {
        return self::getData()?->version ?? 'v1.0.0-dev';
    }

    /**
     * Get the current version release date.
     */
    public static function getReleaseDate(): ?string
    {
        return self::getData()?->release_date ?? date('Y-m-d');
    }
}
