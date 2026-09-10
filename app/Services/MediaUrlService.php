<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class MediaUrlService
{
    public static function publicUrl(mixed $path): ?string
    {
        $value = self::normalizePath($path);

        if ($value === null) {
            return null;
        }

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }

        $disk = self::cloudDisk();

        if ($disk !== null) {
            try {
                return Storage::disk($disk)->url($value);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $cloudBase = rtrim((string) config('filesystems.disks.s3.url', ''), '/');
        if ($cloudBase !== '') {
            return $cloudBase.'/'.ltrim($value, '/');
        }

        return Storage::disk('public')->url($value);
    }

    private static function normalizePath(mixed $path): ?string
    {
        $value = trim(str_replace('\\', '/', (string) $path));
        if ($value === '') {
            return null;
        }

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            foreach (['/api/v1/storage/', '/storage/'] as $marker) {
                $position = strpos($value, $marker);
                if ($position !== false) {
                    return ltrim(substr($value, $position + strlen($marker)), '/');
                }
            }

            return $value;
        }

        return ltrim($value, '/');
    }

    private static function cloudDisk(): ?string
    {
        $candidates = array_unique(array_filter([
            config('filesystems.media_disk'),
            config('filesystems.default'),
            's3',
        ]));

        foreach ($candidates as $disk) {
            $config = (array) config("filesystems.disks.{$disk}", []);
            if (($config['driver'] ?? null) === 's3'
                && ! empty($config['bucket'])
                && ! empty($config['key'])
                && ! empty($config['secret'])) {
                return (string) $disk;
            }
        }

        return null;
    }
}
