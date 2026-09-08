<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ImageProcessingService
{
    /**
     * Resize and optimize an uploaded image, storing it on the public disk.
     */
    public function storePublicImage(
        UploadedFile $file,
        string $directory,
        int $maxWidth = 1200,
        int $maxHeight = 1200,
        int $quality = 82,
    ): string {
        $source = $this->createImageFromUpload($file);

        if ($source === null) {
            throw new RuntimeException('No se pudo leer la imagen. Use JPG, PNG, WebP o GIF.');
        }

        $source = $this->applyExifOrientation($source, $file);

        $width = imagesx($source);
        $height = imagesy($source);
        [$targetWidth, $targetHeight] = $this->fitWithin($width, $height, $maxWidth, $maxHeight);

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);

        if ($canvas === false) {
            imagedestroy($source);

            throw new RuntimeException('No se pudo procesar la imagen.');
        }

        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefill($canvas, 0, 0, $transparent);
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);
        imagedestroy($source);

        $directory = trim($directory, '/');
        Storage::disk('public')->makeDirectory($directory);

        [$filename, $absolutePath] = $this->resolveOutputPath($directory);
        $saved = $this->saveOptimizedImage($canvas, $absolutePath, $quality);
        imagedestroy($canvas);

        if (! $saved) {
            throw new RuntimeException('No se pudo guardar la imagen optimizada.');
        }

        return $directory.'/'.$filename;
    }

    private function createImageFromUpload(UploadedFile $file): ?\GdImage
    {
        $path = $file->getRealPath();

        if (! $path || ! is_readable($path)) {
            return null;
        }

        $imageInfo = @getimagesize($path);

        if ($imageInfo === false) {
            return null;
        }

        return match ($imageInfo[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path) ?: null,
            IMAGETYPE_PNG => @imagecreatefrompng($path) ?: null,
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? (@imagecreatefromwebp($path) ?: null) : null,
            IMAGETYPE_GIF => @imagecreatefromgif($path) ?: null,
            IMAGETYPE_BMP => function_exists('imagecreatefrombmp') ? (@imagecreatefrombmp($path) ?: null) : null,
            default => null,
        };
    }

    private function applyExifOrientation(\GdImage $source, UploadedFile $file): \GdImage
    {
        if (! function_exists('exif_read_data') || ! in_array($file->getMimeType(), ['image/jpeg', 'image/jpg'], true)) {
            return $source;
        }

        $path = $file->getRealPath();
        $exif = @exif_read_data($path ?: '');

        if (! is_array($exif) || ! isset($exif['Orientation'])) {
            return $source;
        }

        $rotated = match ((int) $exif['Orientation']) {
            3 => imagerotate($source, 180, 0),
            6 => imagerotate($source, -90, 0),
            8 => imagerotate($source, 90, 0),
            default => false,
        };

        if ($rotated instanceof \GdImage) {
            imagedestroy($source);

            return $rotated;
        }

        return $source;
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function fitWithin(int $width, int $height, int $maxWidth, int $maxHeight): array
    {
        if ($width <= $maxWidth && $height <= $maxHeight) {
            return [$width, $height];
        }

        $ratio = min($maxWidth / $width, $maxHeight / $height);

        return [
            max(1, (int) round($width * $ratio)),
            max(1, (int) round($height * $ratio)),
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolveOutputPath(string $directory): array
    {
        $extension = function_exists('imagewebp') ? 'webp' : 'jpg';
        $filename = Str::uuid()->toString().'.'.$extension;

        return [$filename, Storage::disk('public')->path($directory.'/'.$filename)];
    }

    private function saveOptimizedImage(\GdImage $canvas, string $absolutePath, int $quality): bool
    {
        if (str_ends_with(strtolower($absolutePath), '.webp') && function_exists('imagewebp')) {
            return imagewebp($canvas, $absolutePath, $quality);
        }

        imagealphablending($canvas, true);
        imagesavealpha($canvas, false);

        return imagejpeg($canvas, $absolutePath, $quality);
    }
}
