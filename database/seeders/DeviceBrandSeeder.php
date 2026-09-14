<?php

namespace Database\Seeders;

use App\Models\DeviceBrand;
use Illuminate\Database\Seeder;

class DeviceBrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $brands = [
            'Samsung',
            'Apple',
            'Xiaomi',
            'Huawei',
            'Motorola',
            'LG',
            'Sony',
            'Nokia',
            'OPPO',
            'Realme',
            'OnePlus',
            'Tecno',
            'ZTE',
        ];

        foreach ($brands as $brand) {
            DeviceBrand::firstOrCreate(
                ['name' => $brand],
                ['is_active' => true]
            );
        }
    }
}
