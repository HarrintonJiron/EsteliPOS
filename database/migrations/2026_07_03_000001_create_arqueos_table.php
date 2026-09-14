<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('arqueos', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->integer('total_sales_count')->default(0);
            $table->decimal('total_sales_amount', 12, 2)->default(0);
            $table->decimal('cash_total', 12, 2)->default(0);
            $table->decimal('credit_payments_total', 12, 2)->default(0);
            $table->decimal('physical_total', 12, 2)->default(0);
            $table->decimal('difference', 12, 2)->default(0);
            $table->json('details')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('arqueos');
    }
};
