<?php

namespace Database\Seeders;

use App\Models\NumberSequence;
use Illuminate\Database\Seeder;

class AccountingSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([ChartOfAccountsSeeder::class, TaxSeeder::class]);

        NumberSequence::firstOrCreate(
            ['type' => 'asiento'],
            ['prefix' => 'POL-', 'current_number' => 1, 'padding' => 6, 'is_active' => true],
        );
    }
}
