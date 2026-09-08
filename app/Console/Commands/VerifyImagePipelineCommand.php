<?php

namespace App\Console\Commands;

use App\Services\ImageProcessingService;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Throwable;

class VerifyImagePipelineCommand extends Command
{
    protected $signature = 'app:verify-image-pipeline';

    protected $description = 'Verifica lectura, procesamiento, almacenamiento y entrega local de imágenes';

    public function handle(ImageProcessingService $processor): int
    {
        $temporaryPath = tempnam(sys_get_temp_dir(), 'estelipos-image-');
        $storedPath = null;

        if ($temporaryPath === false) {
            $this->error('No se pudo crear un archivo temporal para probar imágenes.');

            return self::FAILURE;
        }

        try {
            $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true);
            if ($png === false || file_put_contents($temporaryPath, $png) === false) {
                throw new \RuntimeException('No se pudo escribir la imagen temporal de verificación.');
            }

            $upload = new UploadedFile($temporaryPath, 'verificacion.png', 'image/png', null, true);
            $storedPath = $processor->storePublicImage($upload, 'company/.health-check', 32, 32);
            $absolutePath = Storage::disk('public')->path($storedPath);
            $imageInfo = @getimagesize($absolutePath);

            if (! Storage::disk('public')->exists($storedPath) || Storage::disk('public')->size($storedPath) < 1) {
                throw new \RuntimeException('La imagen de prueba no se pudo leer después de guardarla.');
            }
            if ($imageInfo === false || ($imageInfo[0] ?? 0) < 1 || ($imageInfo[1] ?? 0) < 1) {
                throw new \RuntimeException('La imagen guardada quedó dañada o no es reconocible.');
            }

            $this->table(['Componente', 'Estado'], [
                ['Carpeta storage/app/public', 'lectura y escritura OK'],
                ['GD', extension_loaded('gd') ? 'disponible' : 'no disponible; se conservará el original'],
                ['JPEG / PNG / WebP / GIF', $this->formatCapabilities()],
                ['Prueba completa', "OK ({$imageInfo[0]}x{$imageInfo[1]})"],
            ]);
            $this->info('[OK] El manejo de logos e imágenes está operativo.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('[ERROR] La prueba preventiva de imágenes falló: '.$exception->getMessage());
            $this->line('Revise permisos de storage/app/public y las extensiones gd y fileinfo de PHP.');

            return self::FAILURE;
        } finally {
            if ($storedPath !== null) {
                Storage::disk('public')->delete($storedPath);
                Storage::disk('public')->deleteDirectory('company/.health-check');
            }
            @unlink($temporaryPath);
        }
    }

    private function formatCapabilities(): string
    {
        return implode(' | ', [
            'JPG '.(function_exists('imagecreatefromjpeg') ? 'OK' : 'original'),
            'PNG '.(function_exists('imagecreatefrompng') ? 'OK' : 'original'),
            'WebP '.(function_exists('imagecreatefromwebp') ? 'OK' : 'original'),
            'GIF '.(function_exists('imagecreatefromgif') ? 'OK' : 'original'),
        ]);
    }
}
