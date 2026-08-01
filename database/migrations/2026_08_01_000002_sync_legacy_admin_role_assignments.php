<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_role_assignment_backups', function (Blueprint $table) {
            $table->foreignId('user_id');
            $table->foreignId('role_id');
            $table->primary(['user_id', 'role_id']);
        });

        DB::table('users')->whereNotNull('role')->orderBy('id')->each(function (object $user): void {
            $roleId = DB::table('roles')
                ->where('slug', $user->role)
                ->orderByDesc('is_system')
                ->orderBy('id')
                ->value('id');

            if (! $roleId) {
                return;
            }

            $exists = DB::table('role_user')
                ->where('user_id', $user->id)
                ->where('role_id', $roleId)
                ->exists();

            if ($exists) {
                return;
            }

            DB::table('role_user')->insert(['user_id' => $user->id, 'role_id' => $roleId]);
            DB::table('legacy_role_assignment_backups')->insert(['user_id' => $user->id, 'role_id' => $roleId]);
        });
    }

    public function down(): void
    {
        DB::table('legacy_role_assignment_backups')->orderBy('user_id')->each(function (object $assignment): void {
            DB::table('role_user')
                ->where('user_id', $assignment->user_id)
                ->where('role_id', $assignment->role_id)
                ->delete();
        });

        Schema::dropIfExists('legacy_role_assignment_backups');
    }
};
