<?php

return [
    'barryvdh/laravel-dompdf' => [
        'aliases' => [
            'PDF' => 'Barryvdh\\DomPDF\\Facade\\Pdf',
            'Pdf' => 'Barryvdh\\DomPDF\\Facade\\Pdf',
        ],
        'providers' => [
            0 => 'Barryvdh\\DomPDF\\ServiceProvider',
        ],
    ],
    'laravel/boost' => [
        'providers' => [
            0 => 'Laravel\\Boost\\BoostServiceProvider',
        ],
    ],
    'laravel/mcp' => [
        'aliases' => [
            'Mcp' => 'Laravel\\Mcp\\Server\\Facades\\Mcp',
        ],
        'providers' => [
            0 => 'Laravel\\Mcp\\Server\\McpServiceProvider',
        ],
    ],
    'laravel/pail' => [
        'providers' => [
            0 => 'Laravel\\Pail\\PailServiceProvider',
        ],
    ],
    'laravel/roster' => [
        'providers' => [
            0 => 'Laravel\\Roster\\RosterServiceProvider',
        ],
    ],
    'laravel/sail' => [
        'providers' => [
            0 => 'Laravel\\Sail\\SailServiceProvider',
        ],
    ],
    'laravel/tinker' => [
        'providers' => [
            0 => 'Laravel\\Tinker\\TinkerServiceProvider',
        ],
    ],
    'nesbot/carbon' => [
        'providers' => [
            0 => 'Carbon\\Laravel\\ServiceProvider',
        ],
    ],
    'nunomaduro/collision' => [
        'providers' => [
            0 => 'NunoMaduro\\Collision\\Adapters\\Laravel\\CollisionServiceProvider',
        ],
    ],
    'nunomaduro/termwind' => [
        'providers' => [
            0 => 'Termwind\\Laravel\\TermwindServiceProvider',
        ],
    ],
    'pestphp/pest-plugin-laravel' => [
        'providers' => [
            0 => 'Pest\\Laravel\\PestServiceProvider',
        ],
    ],
];
