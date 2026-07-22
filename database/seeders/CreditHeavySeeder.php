<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Client;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\CreditPayment;
use App\Models\User;
use Carbon\Carbon;

class CreditHeavySeeder extends Seeder
{
    public function run(): void
    {
        // Ensure a user exists for sales/payments
        if (User::count() === 0) {
            User::create([
                'name' => 'Demo User',
                'email' => 'demo@local',
                'password' => bcrypt('secret'),
            ]);
        }

        $user = User::first();

        // Create products if none exist
        if (Product::count() === 0) {
            Product::create(['category_id' => 1, 'name' => 'Semilla Maíz', 'code' => 'P-SEM-001', 'purchase_price' => 100, 'sale_price' => 125, 'stock' => 1000, 'unit' => 'kg']);
            Product::create(['category_id' => 1, 'name' => 'Urea 46%', 'code' => 'P-FER-001', 'purchase_price' => 380, 'sale_price' => 480, 'stock' => 500, 'unit' => 'qq']);
            Product::create(['category_id' => 1, 'name' => 'Insecticida Genérico', 'code' => 'P-PLA-001', 'purchase_price' => 150, 'sale_price' => 200, 'stock' => 300, 'unit' => 'lt']);
            Product::create(['category_id' => 1, 'name' => 'Aspersora 20L', 'code' => 'P-EQU-001', 'purchase_price' => 800, 'sale_price' => 1100, 'stock' => 50, 'unit' => 'un']);
        }

        $products = Product::all();

        // Create many clients with credit enabled and some with no credit
        for ($i = 1; $i <= 30; $i++) {
            $client = Client::updateOrCreate([
                'email' => "cliente{$i}@example.com",
            ], [
                'name' => "Cliente Demo {$i}",
                'phone' => sprintf('8%04d', rand(1000, 9999)),
                'address' => 'Dirección de prueba ' . $i,
                'credit_enabled' => rand(0, 100) > 20,
                'credit_limit' => rand(0, 1) ? rand(5000, 50000) : 0,
                'credit_days' => [15, 30, 45][array_rand([0,1,2])],
            ]);

            // For clients with credit, create 1-3 credit sales
            if ($client->credit_enabled) {
                $numSales = rand(1, 3);
                for ($s = 0; $s < $numSales; $s++) {
                    $date = Carbon::now()->subDays(rand(1, 90));
                    $sale = Sale::create([
                        'client_id' => $client->id,
                        'user_id' => $user->id,
                        'date' => $date->format('Y-m-d'),
                        'due_date' => $date->copy()->addDays($client->credit_days ?? 30)->format('Y-m-d'),
                        'total' => 0,
                        'payment_type' => 'credit',
                        'status' => 'pending',
                        'created_at' => $date,
                        'updated_at' => $date,
                    ]);

                    $items = $products->random(rand(1, 4));
                    $total = 0;
                    foreach ($items as $p) {
                        $qty = rand(1, 20);
                        $price = (float) $p->sale_price;
                        $subtotal = $qty * $price;
                        SaleDetail::create([
                            'sale_id' => $sale->id,
                            'product_id' => $p->id,
                            'quantity' => $qty,
                            'price' => $price,
                            'subtotal' => $subtotal,
                        ]);

                        $total += $subtotal;
                        // Decrement stock if column exists
                        if (isset($p->stock)) {
                            $p->decrement('stock', $qty);
                        }
                    }

                    $sale->update(['total' => $total]);

                    // Optionally create a partial payment
                    if (rand(0, 100) > 40) {
                        $paid = round($total * (rand(10, 80) / 100), 2);
                        CreditPayment::create([
                            'client_id' => $client->id,
                            'sale_id' => $sale->id,
                            'amount' => $paid,
                            'payment_date' => Carbon::now()->subDays(rand(0, 30)),
                            'payment_type' => ['cash','transfer','check','other'][array_rand(['cash','transfer','check','other'])],
                            'reference_number' => 'REF' . strtoupper(substr(uniqid(), -6)),
                            'notes' => 'Abono de prueba',
                            'user_id' => $user->id,
                        ]);
                    }
                }
            }
        }
    }
}
