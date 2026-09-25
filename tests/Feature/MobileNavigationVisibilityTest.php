<?php

test('bottom navigation is visible only on phone-sized screens', function () {
    $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));
    $styles = file_get_contents(resource_path('css/app-ui.css'));

    expect($layout)
        ->toContain('class="mobile-dock md:hidden"')
        ->not->toContain('class="mobile-dock lg:hidden"')
        ->and($styles)
        ->toContain('@media (max-width: 767px)')
        ->toContain('.mobile-dock { display: grid; }')
        ->toContain('body.has-mobile-dock .app-main:not(.overflow-hidden) > div { padding-bottom: 5.5rem; }');
});
