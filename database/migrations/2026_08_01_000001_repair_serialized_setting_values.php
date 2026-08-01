<?php

use App\Support\SettingDefinition;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('setting_value_repair_backups', function (Blueprint $table) {
            $table->foreignId('setting_id')->primary()->constrained('settings')->cascadeOnDelete();
            $table->longText('old_value')->nullable();
            $table->string('old_type');
        });

        DB::table('settings')->orderBy('id')->each(function (object $setting): void {
            $decoded = $this->decodeLegacyString($setting->value);
            $type = SettingDefinition::typeFor($setting->key, $setting->type);

            if ($decoded === $setting->value && $type === $setting->type) {
                return;
            }

            DB::table('setting_value_repair_backups')->insert([
                'setting_id' => $setting->id,
                'old_value' => $setting->value,
                'old_type' => $setting->type,
            ]);

            DB::table('settings')->where('id', $setting->id)->update([
                'value' => $decoded,
                'type' => $type,
                'updated_at' => now(),
            ]);

            Cache::forget("settings.{$setting->key}");
        });
    }

    public function down(): void
    {
        DB::table('setting_value_repair_backups')->orderBy('setting_id')->each(function (object $backup): void {
            $key = DB::table('settings')->where('id', $backup->setting_id)->value('key');

            DB::table('settings')->where('id', $backup->setting_id)->update([
                'value' => $backup->old_value,
                'type' => $backup->old_type,
                'updated_at' => now(),
            ]);

            if ($key) {
                Cache::forget("settings.{$key}");
            }
        });

        Schema::dropIfExists('setting_value_repair_backups');
    }

    private function decodeLegacyString(mixed $value): mixed
    {
        if (! is_string($value) || strlen($value) < 2 || $value[0] !== '"') {
            return $value;
        }

        $decoded = json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE && is_scalar($decoded)
            ? (string) $decoded
            : $value;
    }
};
