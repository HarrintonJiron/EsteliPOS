<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('clients')->where('ruc', '')->update(['ruc' => null]);
        DB::table('clients')->where('business_name', '')->update(['business_name' => null]);

        Schema::table('clients', function (Blueprint $table) {
            $table->string('client_type', 20)->default('natural')->after('name');
            $table->string('cedula', 30)->nullable()->after('ruc');
            $table->string('taxpayer_type', 50)->nullable()->after('address');
            $table->string('department', 100)->nullable()->after('taxpayer_type');
            $table->string('municipality', 100)->nullable()->after('department');
            $table->string('status', 20)->default('active')->after('municipality');
            $table->index('client_type', 'clients_client_type_index');
            $table->unique('cedula', 'clients_cedula_unique');
            $table->unique('ruc', 'clients_ruc_unique');
        });

        DB::table('clients')->orderBy('id')->get()->each(function (object $client): void {
            DB::table('clients')->where('id', $client->id)->update([
                'client_type' => ! empty($client->ruc) || ! empty($client->business_name) ? 'company' : 'natural',
                'status' => 'active',
                'updated_at' => now(),
            ]);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->string('billing_document_type', 20)->nullable()->after('billing_business_name');
        });

        DB::table('sales')->orderBy('id')->get()->each(function (object $sale): void {
            $client = $sale->client_id ? DB::table('clients')->where('id', $sale->client_id)->first() : null;
            $documentType = ! empty($client?->ruc) || ! empty($sale->billing_ruc) ? 'ruc' : (! empty($client?->cedula) ? 'cedula' : null);
            $documentNumber = $sale->billing_ruc ?: ($documentType === 'cedula' ? ($client?->cedula ?? null) : ($client?->ruc ?? null));

            DB::table('sales')->where('id', $sale->id)->update([
                'billing_document_type' => $documentType,
                'billing_ruc' => $documentNumber,
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('billing_document_type');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropUnique('clients_cedula_unique');
            $table->dropUnique('clients_ruc_unique');
            $table->dropIndex('clients_client_type_index');
            $table->dropColumn(['client_type', 'cedula', 'taxpayer_type', 'department', 'municipality', 'status']);
        });
    }
};