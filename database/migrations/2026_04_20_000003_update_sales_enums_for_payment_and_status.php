<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Soporta lo que ya existe en el formulario: cash/transfer/credit y completed/pending/canceled
        DB::statement("ALTER TABLE `sales` MODIFY `payment_type` ENUM('cash','transfer','credit') NOT NULL");
        DB::statement("ALTER TABLE `sales` MODIFY `status` ENUM('completed','pending','canceled') NOT NULL");
    }

    public function down(): void
    {
        // Regresa al estado anterior del proyecto (si había datos con transfer/pending/canceled, esta reversión puede fallar)
        DB::statement("ALTER TABLE `sales` MODIFY `payment_type` ENUM('cash','credit') NOT NULL");
        DB::statement("ALTER TABLE `sales` MODIFY `status` ENUM('completed','canceled') NOT NULL");
    }
};

