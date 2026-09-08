<?php

return [
    'hashing' => [
        'driver' => 'bcrypt',
        'bcrypt' => [
            'rounds' => '12',
            'verify' => true,
            'limit' => null,
        ],
        'argon' => [
            'memory' => 65536,
            'threads' => 1,
            'time' => 4,
            'verify' => true,
        ],
        'rehash_on_login' => true,
    ],
    'concurrency' => [
        'default' => 'process',
    ],
    'broadcasting' => [
        'default' => 'log',
        'connections' => [
            'reverb' => [
                'driver' => 'reverb',
                'key' => null,
                'secret' => null,
                'app_id' => null,
                'options' => [
                    'host' => null,
                    'port' => 443,
                    'scheme' => 'https',
                    'useTLS' => true,
                ],
                'client_options' => [
                ],
            ],
            'pusher' => [
                'driver' => 'pusher',
                'key' => null,
                'secret' => null,
                'app_id' => null,
                'options' => [
                    'cluster' => null,
                    'host' => 'api-mt1.pusher.com',
                    'port' => 443,
                    'scheme' => 'https',
                    'encrypted' => true,
                    'useTLS' => true,
                ],
                'client_options' => [
                ],
            ],
            'ably' => [
                'driver' => 'ably',
                'key' => null,
            ],
            'log' => [
                'driver' => 'log',
            ],
            'null' => [
                'driver' => 'null',
            ],
        ],
    ],
    'view' => [
        'paths' => [
            0 => '/Users/harrintonjiron/agroservicio/resources/views',
        ],
        'compiled' => '/Users/harrintonjiron/agroservicio/storage/framework/views',
    ],
    'cors' => [
        'paths' => [
            0 => 'api/*',
            1 => 'sanctum/csrf-cookie',
        ],
        'allowed_methods' => [
            0 => '*',
        ],
        'allowed_origins' => [
            0 => '*',
        ],
        'allowed_origins_patterns' => [
        ],
        'allowed_headers' => [
            0 => '*',
        ],
        'exposed_headers' => [
        ],
        'max_age' => 0,
        'supports_credentials' => false,
    ],
    'app' => [
        'name' => 'Laravel',
        'env' => 'production',
        'debug' => false,
        'url' => 'http://localhost:8080',
        'frontend_url' => 'http://localhost:3000',
        'asset_url' => null,
        'timezone' => 'America/Managua',
        'locale' => 'es',
        'fallback_locale' => 'en',
        'faker_locale' => 'en_US',
        'cipher' => 'AES-256-CBC',
        'key' => 'base64:MogFQO1pypQBAL+v9LxR+Ha/h6noxfTeBIjF5DZD00I=',
        'previous_keys' => [
        ],
        'maintenance' => [
            'driver' => 'file',
            'store' => 'database',
        ],
        'providers' => [
            0 => 'Illuminate\\Auth\\AuthServiceProvider',
            1 => 'Illuminate\\Broadcasting\\BroadcastServiceProvider',
            2 => 'Illuminate\\Bus\\BusServiceProvider',
            3 => 'Illuminate\\Cache\\CacheServiceProvider',
            4 => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
            5 => 'Illuminate\\Concurrency\\ConcurrencyServiceProvider',
            6 => 'Illuminate\\Cookie\\CookieServiceProvider',
            7 => 'Illuminate\\Database\\DatabaseServiceProvider',
            8 => 'Illuminate\\Encryption\\EncryptionServiceProvider',
            9 => 'Illuminate\\Filesystem\\FilesystemServiceProvider',
            10 => 'Illuminate\\Foundation\\Providers\\FoundationServiceProvider',
            11 => 'Illuminate\\Hashing\\HashServiceProvider',
            12 => 'Illuminate\\Mail\\MailServiceProvider',
            13 => 'Illuminate\\Notifications\\NotificationServiceProvider',
            14 => 'Illuminate\\Pagination\\PaginationServiceProvider',
            15 => 'Illuminate\\Auth\\Passwords\\PasswordResetServiceProvider',
            16 => 'Illuminate\\Pipeline\\PipelineServiceProvider',
            17 => 'Illuminate\\Queue\\QueueServiceProvider',
            18 => 'Illuminate\\Redis\\RedisServiceProvider',
            19 => 'Illuminate\\Session\\SessionServiceProvider',
            20 => 'Illuminate\\Translation\\TranslationServiceProvider',
            21 => 'Illuminate\\Validation\\ValidationServiceProvider',
            22 => 'Illuminate\\View\\ViewServiceProvider',
            23 => 'App\\Providers\\AppServiceProvider',
        ],
        'aliases' => [
            'App' => 'Illuminate\\Support\\Facades\\App',
            'Arr' => 'Illuminate\\Support\\Arr',
            'Artisan' => 'Illuminate\\Support\\Facades\\Artisan',
            'Auth' => 'Illuminate\\Support\\Facades\\Auth',
            'Benchmark' => 'Illuminate\\Support\\Benchmark',
            'Blade' => 'Illuminate\\Support\\Facades\\Blade',
            'Broadcast' => 'Illuminate\\Support\\Facades\\Broadcast',
            'Bus' => 'Illuminate\\Support\\Facades\\Bus',
            'Cache' => 'Illuminate\\Support\\Facades\\Cache',
            'Concurrency' => 'Illuminate\\Support\\Facades\\Concurrency',
            'Config' => 'Illuminate\\Support\\Facades\\Config',
            'Context' => 'Illuminate\\Support\\Facades\\Context',
            'Cookie' => 'Illuminate\\Support\\Facades\\Cookie',
            'Crypt' => 'Illuminate\\Support\\Facades\\Crypt',
            'Date' => 'Illuminate\\Support\\Facades\\Date',
            'DB' => 'Illuminate\\Support\\Facades\\DB',
            'Eloquent' => 'Illuminate\\Database\\Eloquent\\Model',
            'Event' => 'Illuminate\\Support\\Facades\\Event',
            'File' => 'Illuminate\\Support\\Facades\\File',
            'Gate' => 'Illuminate\\Support\\Facades\\Gate',
            'Hash' => 'Illuminate\\Support\\Facades\\Hash',
            'Http' => 'Illuminate\\Support\\Facades\\Http',
            'Js' => 'Illuminate\\Support\\Js',
            'Lang' => 'Illuminate\\Support\\Facades\\Lang',
            'Log' => 'Illuminate\\Support\\Facades\\Log',
            'Mail' => 'Illuminate\\Support\\Facades\\Mail',
            'Notification' => 'Illuminate\\Support\\Facades\\Notification',
            'Number' => 'Illuminate\\Support\\Number',
            'Password' => 'Illuminate\\Support\\Facades\\Password',
            'Process' => 'Illuminate\\Support\\Facades\\Process',
            'Queue' => 'Illuminate\\Support\\Facades\\Queue',
            'RateLimiter' => 'Illuminate\\Support\\Facades\\RateLimiter',
            'Redirect' => 'Illuminate\\Support\\Facades\\Redirect',
            'Request' => 'Illuminate\\Support\\Facades\\Request',
            'Response' => 'Illuminate\\Support\\Facades\\Response',
            'Route' => 'Illuminate\\Support\\Facades\\Route',
            'Schedule' => 'Illuminate\\Support\\Facades\\Schedule',
            'Schema' => 'Illuminate\\Support\\Facades\\Schema',
            'Session' => 'Illuminate\\Support\\Facades\\Session',
            'Storage' => 'Illuminate\\Support\\Facades\\Storage',
            'Str' => 'Illuminate\\Support\\Str',
            'Uri' => 'Illuminate\\Support\\Uri',
            'URL' => 'Illuminate\\Support\\Facades\\URL',
            'Validator' => 'Illuminate\\Support\\Facades\\Validator',
            'View' => 'Illuminate\\Support\\Facades\\View',
            'Vite' => 'Illuminate\\Support\\Facades\\Vite',
        ],
        'seed_demo_data' => false,
    ],
    'auth' => [
        'defaults' => [
            'guard' => 'web',
            'passwords' => 'users',
        ],
        'guards' => [
            'web' => [
                'driver' => 'session',
                'provider' => 'users',
            ],
        ],
        'providers' => [
            'users' => [
                'driver' => 'eloquent',
                'model' => 'App\\Models\\User',
            ],
        ],
        'passwords' => [
            'users' => [
                'provider' => 'users',
                'table' => 'password_reset_tokens',
                'expire' => 60,
                'throttle' => 60,
            ],
        ],
        'password_timeout' => 10800,
    ],
    'cache' => [
        'default' => 'database',
        'stores' => [
            'array' => [
                'driver' => 'array',
                'serialize' => false,
            ],
            'session' => [
                'driver' => 'session',
                'key' => '_cache',
            ],
            'database' => [
                'driver' => 'database',
                'connection' => null,
                'table' => 'cache',
                'lock_connection' => null,
                'lock_table' => null,
            ],
            'file' => [
                'driver' => 'file',
                'path' => '/Users/harrintonjiron/agroservicio/storage/framework/cache/data',
                'lock_path' => '/Users/harrintonjiron/agroservicio/storage/framework/cache/data',
            ],
            'memcached' => [
                'driver' => 'memcached',
                'persistent_id' => null,
                'sasl' => [
                    0 => null,
                    1 => null,
                ],
                'options' => [
                ],
                'servers' => [
                    0 => [
                        'host' => '127.0.0.1',
                        'port' => 11211,
                        'weight' => 100,
                    ],
                ],
            ],
            'redis' => [
                'driver' => 'redis',
                'connection' => 'cache',
                'lock_connection' => 'default',
            ],
            'dynamodb' => [
                'driver' => 'dynamodb',
                'key' => '',
                'secret' => '',
                'region' => 'us-east-1',
                'table' => 'cache',
                'endpoint' => null,
            ],
            'octane' => [
                'driver' => 'octane',
            ],
            'failover' => [
                'driver' => 'failover',
                'stores' => [
                    0 => 'database',
                    1 => 'array',
                ],
            ],
        ],
        'prefix' => 'laravel-cache-',
    ],
    'database' => [
        'default' => 'sqlite',
        'connections' => [
            'sqlite' => [
                'driver' => 'sqlite',
                'url' => null,
                'database' => '/Users/harrintonjiron/agroservicio/tmp/inventory-import/client-test.sqlite',
                'prefix' => '',
                'foreign_key_constraints' => true,
                'busy_timeout' => 5000,
                'journal_mode' => 'WAL',
                'synchronous' => 'NORMAL',
                'transaction_mode' => 'IMMEDIATE',
            ],
            'mysql' => [
                'driver' => 'mysql',
                'url' => null,
                'host' => 'mysql',
                'port' => '3306',
                'database' => '/Users/harrintonjiron/agroservicio/tmp/inventory-import/client-test.sqlite',
                'username' => 'root',
                'password' => 'password',
                'unix_socket' => '',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'prefix_indexes' => true,
                'strict' => true,
                'engine' => null,
                'options' => [
                ],
            ],
            'mariadb' => [
                'driver' => 'mariadb',
                'url' => null,
                'host' => 'mysql',
                'port' => '3306',
                'database' => '/Users/harrintonjiron/agroservicio/tmp/inventory-import/client-test.sqlite',
                'username' => 'root',
                'password' => 'password',
                'unix_socket' => '',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'prefix_indexes' => true,
                'strict' => true,
                'engine' => null,
                'options' => [
                ],
            ],
            'pgsql' => [
                'driver' => 'pgsql',
                'url' => null,
                'host' => 'mysql',
                'port' => '3306',
                'database' => '/Users/harrintonjiron/agroservicio/tmp/inventory-import/client-test.sqlite',
                'username' => 'root',
                'password' => 'password',
                'charset' => 'utf8',
                'prefix' => '',
                'prefix_indexes' => true,
                'search_path' => 'public',
                'sslmode' => 'prefer',
            ],
            'sqlsrv' => [
                'driver' => 'sqlsrv',
                'url' => null,
                'host' => 'mysql',
                'port' => '3306',
                'database' => '/Users/harrintonjiron/agroservicio/tmp/inventory-import/client-test.sqlite',
                'username' => 'root',
                'password' => 'password',
                'charset' => 'utf8',
                'prefix' => '',
                'prefix_indexes' => true,
            ],
        ],
        'migrations' => [
            'table' => 'migrations',
            'update_date_on_publish' => true,
        ],
        'redis' => [
            'client' => 'phpredis',
            'options' => [
                'cluster' => 'redis',
                'prefix' => 'laravel-database-',
                'persistent' => false,
            ],
            'default' => [
                'url' => null,
                'host' => '127.0.0.1',
                'username' => null,
                'password' => null,
                'port' => '6379',
                'database' => '0',
                'max_retries' => 3,
                'backoff_algorithm' => 'decorrelated_jitter',
                'backoff_base' => 100,
                'backoff_cap' => 1000,
            ],
            'cache' => [
                'url' => null,
                'host' => '127.0.0.1',
                'username' => null,
                'password' => null,
                'port' => '6379',
                'database' => '1',
                'max_retries' => 3,
                'backoff_algorithm' => 'decorrelated_jitter',
                'backoff_base' => 100,
                'backoff_cap' => 1000,
            ],
        ],
    ],
    'filesystems' => [
        'default' => 'local',
        'disks' => [
            'local' => [
                'driver' => 'local',
                'root' => '/Users/harrintonjiron/agroservicio/storage/app/private',
                'serve' => true,
                'throw' => false,
                'report' => false,
            ],
            'public' => [
                'driver' => 'local',
                'root' => '/Users/harrintonjiron/agroservicio/storage/app/public',
                'url' => 'http://localhost:8080/storage',
                'visibility' => 'public',
                'throw' => false,
                'report' => false,
            ],
            's3' => [
                'driver' => 's3',
                'key' => '',
                'secret' => '',
                'region' => 'us-east-1',
                'bucket' => '',
                'url' => null,
                'endpoint' => null,
                'use_path_style_endpoint' => false,
                'throw' => false,
                'report' => false,
            ],
        ],
        'links' => [
            '/Users/harrintonjiron/agroservicio/public/storage' => '/Users/harrintonjiron/agroservicio/storage/app/public',
        ],
    ],
    'logging' => [
        'default' => 'stack',
        'deprecations' => [
            'channel' => null,
            'trace' => false,
        ],
        'channels' => [
            'stack' => [
                'driver' => 'stack',
                'channels' => [
                    0 => 'single',
                ],
                'ignore_exceptions' => false,
            ],
            'single' => [
                'driver' => 'single',
                'path' => '/Users/harrintonjiron/agroservicio/storage/logs/laravel.log',
                'level' => 'warning',
                'replace_placeholders' => true,
            ],
            'daily' => [
                'driver' => 'daily',
                'path' => '/Users/harrintonjiron/agroservicio/storage/logs/laravel.log',
                'level' => 'warning',
                'days' => 14,
                'replace_placeholders' => true,
            ],
            'slack' => [
                'driver' => 'slack',
                'url' => null,
                'username' => 'Laravel Log',
                'emoji' => ':boom:',
                'level' => 'warning',
                'replace_placeholders' => true,
            ],
            'papertrail' => [
                'driver' => 'monolog',
                'level' => 'warning',
                'handler' => 'Monolog\\Handler\\SyslogUdpHandler',
                'handler_with' => [
                    'host' => null,
                    'port' => null,
                    'connectionString' => 'tls://:',
                ],
                'processors' => [
                    0 => 'Monolog\\Processor\\PsrLogMessageProcessor',
                ],
            ],
            'stderr' => [
                'driver' => 'monolog',
                'level' => 'warning',
                'handler' => 'Monolog\\Handler\\StreamHandler',
                'handler_with' => [
                    'stream' => 'php://stderr',
                ],
                'formatter' => null,
                'processors' => [
                    0 => 'Monolog\\Processor\\PsrLogMessageProcessor',
                ],
            ],
            'syslog' => [
                'driver' => 'syslog',
                'level' => 'warning',
                'facility' => 8,
                'replace_placeholders' => true,
            ],
            'errorlog' => [
                'driver' => 'errorlog',
                'level' => 'warning',
                'replace_placeholders' => true,
            ],
            'null' => [
                'driver' => 'monolog',
                'handler' => 'Monolog\\Handler\\NullHandler',
            ],
            'emergency' => [
                'path' => '/Users/harrintonjiron/agroservicio/storage/logs/laravel.log',
            ],
        ],
    ],
    'mail' => [
        'default' => 'log',
        'mailers' => [
            'smtp' => [
                'transport' => 'smtp',
                'scheme' => null,
                'url' => null,
                'host' => '127.0.0.1',
                'port' => '2525',
                'username' => null,
                'password' => null,
                'timeout' => null,
                'local_domain' => 'localhost',
            ],
            'ses' => [
                'transport' => 'ses',
            ],
            'postmark' => [
                'transport' => 'postmark',
            ],
            'resend' => [
                'transport' => 'resend',
            ],
            'sendmail' => [
                'transport' => 'sendmail',
                'path' => '/usr/sbin/sendmail -bs -i',
            ],
            'log' => [
                'transport' => 'log',
                'channel' => null,
            ],
            'array' => [
                'transport' => 'array',
            ],
            'failover' => [
                'transport' => 'failover',
                'mailers' => [
                    0 => 'smtp',
                    1 => 'log',
                ],
                'retry_after' => 60,
            ],
            'roundrobin' => [
                'transport' => 'roundrobin',
                'mailers' => [
                    0 => 'ses',
                    1 => 'postmark',
                ],
                'retry_after' => 60,
            ],
        ],
        'from' => [
            'address' => 'hello@example.com',
            'name' => 'Laravel',
        ],
        'markdown' => [
            'theme' => 'default',
            'paths' => [
                0 => '/Users/harrintonjiron/agroservicio/resources/views/vendor/mail',
            ],
            'extensions' => [
            ],
        ],
    ],
    'northlink' => [
        'name' => 'Northlink Microsystem',
        'product' => 'EsteliPOS',
        'location' => 'Estelí, Nicaragua',
        'website' => 'https://northlinkni.com',
        'support_email' => null,
        'whatsapp' => null,
    ],
    'queue' => [
        'default' => 'database',
        'connections' => [
            'sync' => [
                'driver' => 'sync',
            ],
            'database' => [
                'driver' => 'database',
                'connection' => null,
                'table' => 'jobs',
                'queue' => 'default',
                'retry_after' => 90,
                'after_commit' => false,
            ],
            'beanstalkd' => [
                'driver' => 'beanstalkd',
                'host' => 'localhost',
                'queue' => 'default',
                'retry_after' => 90,
                'block_for' => 0,
                'after_commit' => false,
            ],
            'sqs' => [
                'driver' => 'sqs',
                'key' => '',
                'secret' => '',
                'prefix' => 'https://sqs.us-east-1.amazonaws.com/your-account-id',
                'queue' => 'default',
                'suffix' => null,
                'region' => 'us-east-1',
                'after_commit' => false,
            ],
            'redis' => [
                'driver' => 'redis',
                'connection' => 'default',
                'queue' => 'default',
                'retry_after' => 90,
                'block_for' => null,
                'after_commit' => false,
            ],
            'deferred' => [
                'driver' => 'deferred',
            ],
            'failover' => [
                'driver' => 'failover',
                'connections' => [
                    0 => 'database',
                    1 => 'deferred',
                ],
            ],
            'background' => [
                'driver' => 'background',
            ],
        ],
        'batching' => [
            'database' => 'sqlite',
            'table' => 'job_batches',
        ],
        'failed' => [
            'driver' => 'database-uuids',
            'database' => 'sqlite',
            'table' => 'failed_jobs',
        ],
    ],
    'services' => [
        'postmark' => [
            'key' => null,
        ],
        'resend' => [
            'key' => null,
        ],
        'ses' => [
            'key' => '',
            'secret' => '',
            'region' => 'us-east-1',
        ],
        'slack' => [
            'notifications' => [
                'bot_user_oauth_token' => null,
                'channel' => null,
            ],
        ],
    ],
    'session' => [
        'driver' => 'database',
        'lifetime' => 120,
        'expire_on_close' => false,
        'encrypt' => false,
        'files' => '/Users/harrintonjiron/agroservicio/storage/framework/sessions',
        'connection' => null,
        'table' => 'sessions',
        'store' => null,
        'lottery' => [
            0 => 2,
            1 => 100,
        ],
        'cookie' => 'laravel-session',
        'path' => '/',
        'domain' => null,
        'secure' => null,
        'http_only' => true,
        'same_site' => 'lax',
        'partitioned' => false,
    ],
    'dompdf' => [
        'show_warnings' => false,
        'public_path' => null,
        'convert_entities' => true,
        'options' => [
            'font_dir' => '/Users/harrintonjiron/agroservicio/storage/fonts',
            'font_cache' => '/Users/harrintonjiron/agroservicio/storage/fonts',
            'temp_dir' => '/var/folders/qy/lstvtmjn3m9fyx3m70tl27n40000gn/T',
            'chroot' => '/Users/harrintonjiron/agroservicio',
            'allowed_protocols' => [
                'data://' => [
                    'rules' => [
                    ],
                ],
                'file://' => [
                    'rules' => [
                    ],
                ],
                'http://' => [
                    'rules' => [
                    ],
                ],
                'https://' => [
                    'rules' => [
                    ],
                ],
            ],
            'artifactPathValidation' => null,
            'log_output_file' => null,
            'enable_font_subsetting' => false,
            'pdf_backend' => 'CPDF',
            'default_media_type' => 'screen',
            'default_paper_size' => 'a4',
            'default_paper_orientation' => 'portrait',
            'default_font' => 'serif',
            'dpi' => 96,
            'enable_php' => false,
            'enable_javascript' => true,
            'enable_remote' => false,
            'allowed_remote_hosts' => null,
            'font_height_ratio' => 1.1,
            'enable_html5_parser' => true,
        ],
    ],
    'boost' => [
        'enabled' => true,
        'browser_logs_watcher' => true,
        'executable_paths' => [
            'php' => null,
            'composer' => null,
            'npm' => null,
            'vendor_bin' => null,
        ],
    ],
    'mcp' => [
        'redirect_domains' => [
            0 => '*',
        ],
    ],
    'tinker' => [
        'commands' => [
        ],
        'alias' => [
        ],
        'dont_alias' => [
            0 => 'App\\Nova',
        ],
        'trust_project' => 'always',
    ],
];
