<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // This migration is now handled by 2026_08_01_200000_add_lock_type_to_repair_orders_table.php
        // Left empty to avoid conflicts
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op - handled by the other migration
    }
};
