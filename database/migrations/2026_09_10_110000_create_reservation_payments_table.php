<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained('reservations')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 20)->default('payment');
            $table->decimal('amount', 12, 2);
            $table->string('payment_method', 20)->default('cash');
            $table->string('reference_number', 100)->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('paid_at');
            $table->timestamps();
            $table->index(['reservation_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_payments');
    }
};
