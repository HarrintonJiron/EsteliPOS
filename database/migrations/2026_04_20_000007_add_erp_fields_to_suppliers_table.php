<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('code', 50)->nullable()->after('id');
            $table->string('business_name')->nullable()->after('name');
            $table->string('ruc', 30)->nullable()->after('business_name');
            $table->string('contact_name')->nullable()->after('ruc');
            $table->string('city')->nullable()->after('contact_name');
            $table->string('type')->nullable()->after('city');
            $table->string('payment_condition')->nullable()->after('type'); // contado / credito_15 / credito_30 / credito_60
            $table->decimal('credit_limit', 12, 2)->nullable()->after('payment_condition');
            $table->enum('status', ['active', 'inactive'])->default('active')->after('credit_limit');

            $table->string('phone', 50)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn([
                'code',
                'business_name',
                'ruc',
                'contact_name',
                'city',
                'type',
                'payment_condition',
                'credit_limit',
                'status',
            ]);

            $table->string('phone')->nullable(false)->change();
        });
    }
};

