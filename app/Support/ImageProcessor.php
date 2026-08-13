<?php

namespace App\Support;

use GdImage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Beeldverwerking (scope §2.11): uploads verkleinen en comprimeren, plus een
 * thumbnail-variant voor kaartjes en lijsten. Gebruikt PHP's ingebouwde GD,
 * geen externe dienst nodig. WebP waar beschikbaar, anders JPEG.
 */
class ImageProcessor
{
    private const MAX_MAIN = 1600;

    private const MAX_THUMB = 640;

    private const QUALITY = 82;

    /**
     * @return array{path: string, thumb_path: string}|null null als GD het beeld niet kan lezen
     */
    public static function store(string $bytes, string $directory): ?array
    {
        try {
            $image = @imagecreatefromstring($bytes);
        } catch (Throwable) {
            return null;
        }

        if ($image === false) {
            return null;
        }

        $useWebp = function_exists('imagewebp');
        $extension = $useWebp ? 'webp' : 'jpg';
        $name = Str::random(20);

        $path = "{$directory}/{$name}.{$extension}";
        $thumbPath = "{$directory}/{$name}-thumb.{$extension}";

        Storage::disk('public')->put($path, self::encodeResized($image, self::MAX_MAIN, $useWebp));
        Storage::disk('public')->put($thumbPath, self::encodeResized($image, self::MAX_THUMB, $useWebp));

        imagedestroy($image);

        return ['path' => $path, 'thumb_path' => $thumbPath];
    }

    private static function encodeResized(GdImage $image, int $maxSize, bool $useWebp): string
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $scale = min(1, $maxSize / max($width, $height));

        $resized = $image;
        if ($scale < 1) {
            $newWidth = max(1, (int) round($width * $scale));
            $newHeight = max(1, (int) round($height * $scale));
            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        }

        ob_start();
        $useWebp ? imagewebp($resized, null, self::QUALITY) : imagejpeg($resized, null, self::QUALITY);
        $encoded = ob_get_clean();

        if ($resized !== $image) {
            imagedestroy($resized);
        }

        return $encoded;
    }
}
