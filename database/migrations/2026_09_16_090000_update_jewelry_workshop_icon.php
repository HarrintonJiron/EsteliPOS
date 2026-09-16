<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('modules')->where('slug', 'reparaciones')->update(['icon' => '💍']);
    }

    public function down(): void
    {
        DB::table('modules')->where('slug', 'reparaciones')->update(['icon' => '🛠️']);
    }
};
