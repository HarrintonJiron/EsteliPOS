<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Galería de fotos de un producto (hasta MAX_IMAGES). La primera foto es la
 * portada y se copia a products.image_url, que usan el catálogo y el POS.
 */
class ProductGalleryService
{
    public const MAX_IMAGES = 5;

    /**
     * Valida el cupo: fotos actuales - las que se quitan + las nuevas <= MAX_IMAGES.
     *
     * @param  array<int, int|string>  $removeIds
     */
    public function ensureCapacity(?Product $product, int $incoming, array $removeIds = []): void
    {
        $current = 0;

        if ($product?->exists) {
            $this->adoptLegacyCover($product);
            $ids = $product->images()->pluck('id');
            $current = $ids->count() - $ids->intersect($removeIds)->count();
        }

        if ($current + $incoming > self::MAX_IMAGES) {
            $free = max(0, self::MAX_IMAGES - $current);

            throw ValidationException::withMessages([
                'images' => 'Cada producto admite hasta '.self::MAX_IMAGES.' fotos. '
                    .($free === 0 ? 'Elimina alguna para agregar otra.' : "Solo puedes agregar {$free} más."),
            ]);
        }
    }

    /**
     * Optimiza y guarda los archivos subidos; devuelve sus rutas en el disco público.
     *
     * @param  array<int, UploadedFile>  $files
     * @return array<int, string>
     */
    public function storeUploads(array $files): array
    {
        $paths = [];

        try {
            foreach ($files as $file) {
                $paths[] = app(ImageProcessingService::class)->storePublicImage($file, 'products', 1600, 1600);
            }
        } catch (Throwable $exception) {
            report($exception);
            $this->discard($paths);

            throw ValidationException::withMessages([
                'images' => 'No se pudo procesar una de las imágenes. Use JPG, PNG o WebP de hasta 8 MB y 20 megapíxeles.',
            ]);
        }

        return $paths;
    }

    /**
     * @param  array<int, string>  $paths
     */
    public function attach(Product $product, array $paths): void
    {
        $this->adoptLegacyCover($product);
        $next = (int) ($product->images()->max('sort_order') ?? -1) + 1;

        foreach ($paths as $path) {
            $product->images()->create(['path' => $path, 'sort_order' => $next++]);
        }

        $this->syncCover($product);
    }

    /**
     * @param  array<int, int|string>  $imageIds
     */
    public function detach(Product $product, array $imageIds): void
    {
        $this->adoptLegacyCover($product);
        $paths = $product->images()->whereIn('id', $imageIds)->pluck('path')->all();

        $product->images()->whereIn('id', $imageIds)->delete();
        $this->syncCover($product);
        $this->discard($paths);
    }

    /**
     * Reemplaza la portada (primera foto); si no había fotos, la crea.
     */
    public function replaceCover(Product $product, string $path): void
    {
        $this->adoptLegacyCover($product);
        $cover = $product->images()->first();
        $oldPath = $cover?->path;

        if ($cover) {
            $cover->update(['path' => $path]);
        } else {
            $product->images()->create(['path' => $path, 'sort_order' => 0]);
        }

        $this->syncCover($product);

        if ($oldPath && $oldPath !== $path) {
            $this->discard([$oldPath]);
        }
    }

    /**
     * Un producto con image_url pero sin filas en la galería (creado por otra vía)
     * conserva esa imagen como su primera foto.
     */
    private function adoptLegacyCover(Product $product): void
    {
        $legacy = $product->getRawOriginal('image_url');

        if ($legacy && ! $product->images()->exists()) {
            $product->images()->create(['path' => $legacy, 'sort_order' => 0]);
        }
    }

    public function syncCover(Product $product): void
    {
        $cover = $product->images()->first();

        $product->forceFill(['image_url' => $cover?->path])->saveQuietly();
    }

    /**
     * Borra del disco las rutas que ya no usa ningún producto.
     *
     * @param  array<int, string|null>  $paths
     */
    public function discard(array $paths): void
    {
        foreach (array_filter($paths) as $path) {
            if (! str_starts_with($path, 'products/')) {
                continue;
            }

            $stillUsed = ProductImage::where('path', $path)->exists()
                || Product::withTrashed()->where('image_url', $path)->exists();

            if (! $stillUsed) {
                Storage::disk('public')->delete($path);
            }
        }
    }
}
