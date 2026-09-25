<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;

uses(RefreshDatabase::class);

test('thermal tickets use 50 millimeter paper on mobile browsers', function () {
    request()->headers->set('User-Agent', 'Mozilla/5.0 (Linux; Android 14; Mobile) AppleWebKit/537.36');

    $html = Blade::render('<x-mobile-ticket-paper selector=".receipt" />');

    expect($html)
        ->toContain('content="50mm"')
        ->toContain('size: 50mm auto')
        ->toContain('width: 46mm !important');
});

test('thermal tickets keep 80 millimeter paper on desktop browsers', function () {
    request()->headers->set('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');

    $html = Blade::render('<x-mobile-ticket-paper selector=".receipt" />');

    expect($html)
        ->toContain('content="80mm"')
        ->toContain('size: 80mm auto')
        ->toContain('width: 72mm !important');
});

test('paper query parameter can force 50 millimeter output', function () {
    request()->headers->set('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
    request()->query->set('paper', '50');

    $html = Blade::render('<x-mobile-ticket-paper selector=".ticket" />');

    expect($html)->toContain('content="50mm"');
});
