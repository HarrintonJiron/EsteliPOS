<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('purchases', 'payment_type')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->string('payment_type', 20)->default('cash')->after('status');
            });
        }

        DB::table('purchases')->where('status', 'pending')->update(['payment_type' => 'credit']);
    }

    public function down(): void
    {
        if (! Schema::hasColumn('purchases', 'payment_type')) {
            return;
        }

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn('payment_type');
        });
    }
};
