<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->enum('status', ['ordered', 'pending', 'completed', 'canceled'])->change();
        });
    }

    public function down(): void
    {
        DB::table('purchases')->where('status', 'ordered')->update(['status' => 'canceled']);

        Schema::table('purchases', function (Blueprint $table) {
            $table->enum('status', ['pending', 'completed', 'canceled'])->change();
        });
    }
};
