<?php

use App\Console\Commands\MigrateProductionCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('runs migrate-production successfully on a migrated database', function () {
    $exitCode = Artisan::call('app:migrate-production', ['--force' => true]);

    expect($exitCode)->toBe(0);
});

it('skips create conflicts when the table already exists', function () {
    $migration = '2099_01_01_000000_create_legacy_conflict_table';
    $path = database_path('migrations/'.$migration.'.php');

    file_put_contents($path, <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_conflict_table', function (Blueprint $table) {
            $table->id();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_conflict_table');
    }
};
PHP);

    Schema::dropIfExists('legacy_conflict_table');
    Schema::create('legacy_conflict_table', function ($table) {
        $table->id();
    });

    expect(Schema::hasTable('legacy_conflict_table'))->toBeTrue();

    try {
        $command = app(MigrateProductionCommand::class);
        $exitCode = Artisan::call($command, ['--force' => true]);

        expect($exitCode)->toBe(0)
            ->and(DB::table('migrations')->where('migration', $migration)->exists())->toBeTrue()
            ->and(Schema::hasTable('legacy_conflict_table'))->toBeTrue();
    } finally {
        Schema::dropIfExists('legacy_conflict_table');
        if (is_file($path)) {
            unlink($path);
        }
        DB::table('migrations')->where('migration', $migration)->delete();
    }
});

it('guards the legacy roles create migration with hasTable', function () {
    $contents = file_get_contents(database_path('migrations/2026_07_31_150000_create_roles_table.php'));

    expect($contents)->toContain("Schema::hasTable('roles')");
});
