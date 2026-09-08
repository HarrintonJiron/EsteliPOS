<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ImageProcessingService
{
    private const MAX_SOURCE_PIXELS = 20_000_000;

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
        $imageInfo = $this->inspectUpload($file);

        if (! $this->canProcess($imageInfo[2])) {
            return $this->storeValidatedOriginal($file, $directory, $imageInfo[2]);
        }

        $source = $this->createImageFromUpload($file, $imageInfo[2]);

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
        if (! Storage::disk('public')->exists($directory) && ! Storage::disk('public')->makeDirectory($directory)) {
            imagedestroy($canvas);

            throw new RuntimeException('No se pudo preparar la carpeta donde se guardan los logos.');
        }

        [$filename, $absolutePath] = $this->resolveOutputPath($directory);
        $saved = $this->saveOptimizedImage($canvas, $absolutePath, $quality);
        imagedestroy($canvas);

        if (! $saved) {
            throw new RuntimeException('No se pudo guardar la imagen optimizada.');
        }

        return $directory.'/'.$filename;
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private function inspectUpload(UploadedFile $file): array
    {
        $path = $file->getRealPath();

        if (! $path || ! is_readable($path)) {
            throw new RuntimeException('No se pudo leer el archivo temporal de la imagen.');
        }

        $imageInfo = @getimagesize($path);

        if ($imageInfo === false) {
            throw new RuntimeException('El archivo no contiene una imagen válida.');
        }

        $width = (int) ($imageInfo[0] ?? 0);
        $height = (int) ($imageInfo[1] ?? 0);
        $type = (int) ($imageInfo[2] ?? 0);
        $supportedTypes = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_GIF];

        if ($width < 1 || $height < 1 || ! in_array($type, $supportedTypes, true)) {
            throw new RuntimeException('Use una imagen JPG, PNG, WebP o GIF válida.');
        }

        if ($width * $height > self::MAX_SOURCE_PIXELS) {
            throw new RuntimeException('La resolución del logo es demasiado alta. Use una imagen de hasta 20 megapíxeles.');
        }

        return [$width, $height, $type];
    }

    private function createImageFromUpload(UploadedFile $file, int $imageType): ?\GdImage
    {
        $path = $file->getRealPath();

        if (! $path || ! is_readable($path)) {
            return null;
        }

        return match ($imageType) {
            IMAGETYPE_JPEG => function_exists('imagecreatefromjpeg') ? (@imagecreatefromjpeg($path) ?: null) : null,
            IMAGETYPE_PNG => function_exists('imagecreatefrompng') ? (@imagecreatefrompng($path) ?: null) : null,
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? (@imagecreatefromwebp($path) ?: null) : null,
            IMAGETYPE_GIF => function_exists('imagecreatefromgif') ? (@imagecreatefromgif($path) ?: null) : null,
            default => null,
        };
    }

    private function canProcess(int $imageType): bool
    {
        if (! function_exists('imagecreatetruecolor') || ! function_exists('imagecopyresampled')) {
            return false;
        }

        return match ($imageType) {
            IMAGETYPE_JPEG => function_exists('imagecreatefromjpeg'),
            IMAGETYPE_PNG => function_exists('imagecreatefrompng'),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp'),
            IMAGETYPE_GIF => function_exists('imagecreatefromgif'),
            default => false,
        };
    }

    private function storeValidatedOriginal(UploadedFile $file, string $directory, int $imageType): string
    {
        $extension = match ($imageType) {
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_WEBP => 'webp',
            IMAGETYPE_GIF => 'gif',
            default => throw new RuntimeException('El formato de imagen no es compatible.'),
        };
        $directory = trim($directory, '/');
        $filename = Str::uuid()->toString().'.'.$extension;
        $path = $file->getRealPath();

        if (! $path || ! is_readable($path)) {
            throw new RuntimeException('No se pudo leer el archivo temporal de la imagen.');
        }

        $stream = @fopen($path, 'rb');
        if ($stream === false) {
            throw new RuntimeException('No se pudo abrir la imagen para guardarla.');
        }

        try {
            $saved = Storage::disk('public')->put($directory.'/'.$filename, $stream);
        } finally {
            fclose($stream);
        }

        $storedPath = $directory.'/'.$filename;
        if (! $saved || ! Storage::disk('public')->exists($storedPath) || Storage::disk('public')->size($storedPath) < 1) {
            Storage::disk('public')->delete($storedPath);

            throw new RuntimeException('Windows no permitió guardar la imagen. Revise los permisos de storage/app/public.');
        }

        return $storedPath;
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
