<?php

use App\Models\Module;
use App\Models\User;
use App\Services\ModuleAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('active modules remain available while an existing installation awaits the module role migration', function () {
    Schema::drop('module_role');

    $user = User::factory()->create();
    Module::firstOrCreate(['slug' => 'reparaciones'], [
        'name' => 'Reparaciones',
        'is_active' => true,
    ]);
    Module::flushModuleCache();

    $access = app(ModuleAccessService::class);

    expect($access->canAccessSlug('reparaciones', $user))->toBeTrue()
        ->and($access->accessibleSlugs($user))->toContain('reparaciones');
});
