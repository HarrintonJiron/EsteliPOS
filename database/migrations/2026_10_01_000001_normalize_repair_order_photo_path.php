<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('repair_order_photos', 'path') && ! Schema::hasColumn('repair_order_photos', 'photo_path')) {
            Schema::table('repair_order_photos', function (Blueprint $table): void {
                $table->renameColumn('path', 'photo_path');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('repair_order_photos', 'photo_path') && ! Schema::hasColumn('repair_order_photos', 'path')) {
            Schema::table('repair_order_photos', function (Blueprint $table): void {
                $table->renameColumn('photo_path', 'path');
            });
        }
    }
};
