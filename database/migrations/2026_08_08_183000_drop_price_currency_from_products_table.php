<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('products', 'price_currency')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('price_currency');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('products', 'price_currency')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('price_currency', 3)->default('NIO')->after('sale_price');
            });
        }
    }
};
