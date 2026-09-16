<?php

use App\Models\RepairOrder;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\ConfigurationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('jewelry repair order privacy', function () {
    it('does not store legacy device credentials', function () {
        $this->seed(ConfigurationSeeder::class);
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'admin')->value('id'));

        $response = $this->actingAs($user)->post('/reparaciones', [
            'client_name' => 'Ana García',
            'device_brand' => 'Anillo',
            'device_model' => 'Oro 14K',
            'device_color' => 'Oro amarillo',
            'device_imei' => '4.25 g / grabado AJ',
            'accessories' => 'Piedra roja central y seis circones',
            'problem_description' => 'Ajustar talla y reforzar el engaste de la piedra central',
            'status' => 'received',
            'priority' => 'normal',
            'received_date' => now()->toDateString(),
            'payment_type' => 'cash',
            'lock_type' => 'pattern',
            'device_password' => '1-2-5-8-9',
        ]);

        $response->assertRedirect();
        $order = RepairOrder::query()->firstOrFail();
        expect($order->lock_type)->toBe('none')
            ->and($order->device_password)->toBeNull()
            ->and($order->device_brand)->toBe('Anillo')
            ->and($order->device_model)->toBe('Oro 14K')
            ->and($order->device_imei)->toBe('4.25 g / grabado AJ');
    });
});
