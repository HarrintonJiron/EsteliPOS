<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('repair_orders')
            ->whereNotNull('device_password')
            ->orderBy('id')
            ->chunkById(100, function ($orders): void {
                foreach ($orders as $order) {
                    try {
                        Crypt::decryptString($order->device_password);
                    } catch (Throwable) {
                        DB::table('repair_orders')->where('id', $order->id)->update([
                            'device_password' => Crypt::encryptString($order->device_password),
                        ]);
                    }
                }
            });
    }

    public function down(): void
    {
        // Se mantiene cifrado para no reintroducir credenciales en texto plano.
    }
};
