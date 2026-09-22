<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
use App\Services\InventoryService;
use App\Services\PricingService;
use Illuminate\Database\Seeder;

/**
 * Celulares de demostración con marca, modelo, color, IMEI, batería y precios
 * de mercado aproximados en USD. Se puede volver a ejecutar sin duplicar:
 * php artisan db:seed --class=CellphoneDemoSeeder
 */
class CellphoneDemoSeeder extends Seeder
{
    public function run(InventoryService $inventory, PricingService $pricing): void
    {
        $category = Category::firstOrCreate(['name' => 'Celulares']);
        $unit = Unit::query()->where('abbreviation', 'und')->first();
        $supplier = Supplier::query()->orderBy('id')->first();

        foreach ($this->phones() as $phone) {
            $imei = $this->imei($phone['imei_base']);

            if (Product::withTrashed()->where('imei', $imei)->exists()) {
                continue;
            }

            $product = Product::create([
                'category_id' => $category->id,
                'name' => $phone['name'],
                'code' => $inventory->nextProductCode('CEL'),
                'brand' => $phone['brand'],
                'model' => $phone['model'],
                'color' => $phone['color'],
                'imei' => $imei,
                'battery_percentage' => $phone['battery'],
                'condition' => $phone['condition'],
                'description' => $phone['description'],
                'purchase_price' => $phone['cost'],
                'sale_price' => $phone['price'],
                'source_sale_price' => $phone['price'],
                'price_currency' => 'USD',
                'stock' => 0,
                'unit' => $unit?->abbreviation ?? 'und',
                'base_unit_id' => $unit?->id,
                'low_stock_threshold' => 1,
                'status' => 'active',
            ]);

            $inventory->stockIn($product, 1, 'initial_stock', 'Stock inicial — celular demo');
            $pricing->syncProductToDefaultList($product->fresh());

            $supplier?->products()->syncWithoutDetaching([
                $product->id => ['purchase_price' => $phone['cost'], 'preferred' => true],
            ]);
        }
    }

    /**
     * IMEI de 15 dígitos con dígito verificador Luhn válido.
     */
    private function imei(string $base14): string
    {
        $sum = 0;
        foreach (array_reverse(str_split($base14)) as $i => $digit) {
            $n = (int) $digit;
            if ($i % 2 === 0) {
                $n *= 2;
                if ($n > 9) {
                    $n -= 9;
                }
            }
            $sum += $n;
        }

        return $base14.((10 - ($sum % 10)) % 10);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function phones(): array
    {
        $box = 'Incluye caja y cable USB-C. Garantía de 30 días.';
        $usedKit = 'Incluye cable y cargador. Garantía de 30 días.';

        return [
            ['name' => 'iPhone 15 Pro Max 256GB', 'brand' => 'Apple', 'model' => 'A3108', 'color' => 'Titanio natural', 'imei_base' => '35291012000011', 'battery' => 100, 'condition' => 'new', 'description' => 'Nuevo sellado, 256GB, libre de fábrica. '.$box, 'cost' => 1080, 'price' => 1249],
            ['name' => 'iPhone 15 128GB', 'brand' => 'Apple', 'model' => 'A3090', 'color' => 'Negro', 'imei_base' => '35291012000022', 'battery' => 100, 'condition' => 'new', 'description' => 'Nuevo sellado, 128GB, libre de fábrica. '.$box, 'cost' => 690, 'price' => 799],
            ['name' => 'iPhone 14 128GB', 'brand' => 'Apple', 'model' => 'A2882', 'color' => 'Medianoche', 'imei_base' => '35291012000033', 'battery' => 91, 'condition' => 'used', 'description' => 'Seminuevo, sin detalles en pantalla, Face ID funcional. '.$usedKit, 'cost' => 430, 'price' => 529],
            ['name' => 'iPhone 13 128GB', 'brand' => 'Apple', 'model' => 'A2633', 'color' => 'Azul', 'imei_base' => '35291012000044', 'battery' => 86, 'condition' => 'used', 'description' => 'Seminuevo, detalle leve en el marco. '.$usedKit, 'cost' => 320, 'price' => 399],
            ['name' => 'iPhone 12 64GB', 'brand' => 'Apple', 'model' => 'A2403', 'color' => 'Blanco', 'imei_base' => '35291012000055', 'battery' => 82, 'condition' => 'used', 'description' => 'Seminuevo, 64GB, pantalla original. '.$usedKit, 'cost' => 205, 'price' => 269],
            ['name' => 'iPhone 11 64GB', 'brand' => 'Apple', 'model' => 'A2111', 'color' => 'Verde', 'imei_base' => '35291012000066', 'battery' => 78, 'condition' => 'used', 'description' => 'Seminuevo, batería con desgaste normal. '.$usedKit, 'cost' => 165, 'price' => 219],
            ['name' => 'Samsung Galaxy S24 Ultra 256GB', 'brand' => 'Samsung', 'model' => 'SM-S928B', 'color' => 'Gris titanio', 'imei_base' => '35401112000011', 'battery' => 100, 'condition' => 'new', 'description' => 'Nuevo sellado, 12GB RAM / 256GB, incluye S Pen. Cable USB-C incluido. Garantía de 30 días.', 'cost' => 1020, 'price' => 1199],
            ['name' => 'Samsung Galaxy S23 256GB', 'brand' => 'Samsung', 'model' => 'SM-S911B', 'color' => 'Verde', 'imei_base' => '35401112000022', 'battery' => 98, 'condition' => 'open_box', 'description' => 'Caja abierta, sin uso, 8GB RAM / 256GB. '.$box, 'cost' => 520, 'price' => 629],
            ['name' => 'Samsung Galaxy A55 5G 128GB', 'brand' => 'Samsung', 'model' => 'SM-A556E', 'color' => 'Azul claro', 'imei_base' => '35401112000033', 'battery' => 100, 'condition' => 'new', 'description' => 'Nuevo sellado, 8GB RAM / 128GB. Cable USB-C incluido. Garantía de 30 días.', 'cost' => 340, 'price' => 399],
            ['name' => 'Samsung Galaxy A15 128GB', 'brand' => 'Samsung', 'model' => 'SM-A155M', 'color' => 'Negro', 'imei_base' => '35401112000044', 'battery' => 100, 'condition' => 'new', 'description' => 'Nuevo sellado, 4GB RAM / 128GB. Cable USB-C incluido. Garantía de 30 días.', 'cost' => 145, 'price' => 179],
            ['name' => 'Xiaomi Redmi Note 13 Pro 256GB', 'brand' => 'Xiaomi', 'model' => '2312DRA50G', 'color' => 'Azul medianoche', 'imei_base' => '86412705000011', 'battery' => 100, 'condition' => 'new', 'description' => 'Nuevo sellado, 8GB RAM / 256GB, cámara 200MP. Incluye cargador de 67W. Garantía de 30 días.', 'cost' => 270, 'price' => 329],
            ['name' => 'Motorola Moto G54 5G 128GB', 'brand' => 'Motorola', 'model' => 'XT2343-1', 'color' => 'Azul índigo', 'imei_base' => '35934412000011', 'battery' => 100, 'condition' => 'new', 'description' => 'Nuevo sellado, 8GB RAM / 128GB. Incluye cargador. Garantía de 30 días.', 'cost' => 160, 'price' => 199],
        ];
    }
}
