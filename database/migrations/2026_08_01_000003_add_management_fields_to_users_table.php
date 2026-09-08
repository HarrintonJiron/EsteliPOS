<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 60)->nullable()->unique()->after('name');
            $table->string('phone', 30)->nullable()->after('email');
            $table->string('profile_photo')->nullable()->after('phone');
            $table->boolean('force_password_change')->default(false)->after('password');
            $table->timestamp('password_changed_at')->nullable()->after('force_password_change');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn(['username', 'phone', 'profile_photo', 'force_password_change', 'password_changed_at']);
        });
    }
};
