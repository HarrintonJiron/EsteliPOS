<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('repair order lock handling', function () {
    it('stores a repair order with a pattern lock', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/reparaciones', [
            'client_name' => 'Ana García',
            'device_brand' => 'Samsung',
            'device_model' => 'Galaxy A54',
            'problem_description' => 'No enciende y muestra pantalla negra',
            'status' => 'received',
            'priority' => 'normal',
            'received_date' => now()->toDateString(),
            'payment_type' => 'cash',
            'lock_type' => 'pattern',
            'device_password' => '1-2-5-8-9',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('repair_orders', [
            'lock_type' => 'pattern',
            'device_password' => '1-2-5-8-9',
        ]);
    });
});
