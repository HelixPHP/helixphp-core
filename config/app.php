<?php
/**
 * Configuração Principal do HelixPHP
 *
 * Este arquivo centraliza todas as configurações do framework
 */

return [
    // Informações da aplicação
    'app' => [
        'name' => $_ENV['APP_NAME'] ?? 'HelixPHP Application',
        'version' => '2.1.0',
        'environment' => $_ENV['APP_ENV'] ?? 'production',
        'debug' => filter_var($_ENV['APP_DEBUG'] ?? ($_ENV['APP_ENV'] === 'development' ? true : false), FILTER_VALIDATE_BOOLEAN),
        'timezone' => $_ENV['APP_TIMEZONE'] ?? 'UTC',
        'locale' => $_ENV['APP_LOCALE'] ?? 'en'
    ],

    // Configurações do servidor
    'server' => [
        'host' => $_ENV['SERVER_HOST'] ?? 'localhost',
        'port' => $_ENV['SERVER_PORT'] ?? 8080,
        'ssl' => filter_var($_ENV['SSL_ENABLED'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'max_execution_time' => $_ENV['MAX_EXECUTION_TIME'] ?? 30,
        'memory_limit' => $_ENV['MEMORY_LIMIT'] ?? '256M'
    ],

    // Banco de dados
    'database' => [
        'default' => $_ENV['DB_CONNECTION'] ?? 'mysql',
        'connections' => [
            'mysql' => [
                'driver' => 'mysql',
                'host' => $_ENV['DB_HOST'] ?? 'localhost',
                'port' => $_ENV['DB_PORT'] ?? 3306,
                'database' => $_ENV['DB_NAME'] ?? '',
                'username' => $_ENV['DB_USER'] ?? '',
                'password' => $_ENV['DB_PASS'] ?? '',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'options' => [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            ],
            'sqlite' => [
                'driver' => 'sqlite',
                'database' => $_ENV['DB_DATABASE'] ?? 'database.sqlite',
                'options' => [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            ]
        ]
    ],

    // Cache
    'cache' => [
        'default' => $_ENV['CACHE_DRIVER'] ?? 'file',
        'stores' => [
            'file' => [
                'driver' => 'file',
                'path' => 'storage/cache'
            ],
            'redis' => [
                'driver' => 'redis',
                'host' => $_ENV['REDIS_HOST'] ?? 'localhost',
                'port' => $_ENV['REDIS_PORT'] ?? 6379,
                'password' => $_ENV['REDIS_PASSWORD'] ?? null,
                'database' => $_ENV['REDIS_DB'] ?? 0
            ],
            'memory' => [
                'driver' => 'memory'
            ]
        ],
        'prefix' => $_ENV['CACHE_PREFIX'] ?? 'express_php:',
        'ttl' => $_ENV['CACHE_TTL'] ?? 3600
    ],

    // Sessões
    'session' => [
        'driver' => $_ENV['SESSION_DRIVER'] ?? 'file',
        'lifetime' => $_ENV['SESSION_LIFETIME'] ?? 120, // minutos
        'path' => 'storage/sessions',
        'cookie' => [
            'name' => $_ENV['SESSION_COOKIE'] ?? 'express_php_session',
            'secure' => filter_var($_ENV['SESSION_SECURE_COOKIE'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'http_only' => true,
            'same_site' => $_ENV['SESSION_SAME_SITE'] ?? 'lax'
        ]
    ],

    // Autenticação
    'auth' => [
        'default' => 'jwt',
        'guards' => [
            'jwt' => [
                'driver' => 'jwt',
                'secret' => $_ENV['JWT_SECRET'] ?? '',
                'ttl' => $_ENV['JWT_TTL'] ?? 3600, // segundos
                'algorithm' => $_ENV['JWT_ALGORITHM'] ?? 'HS256',
                'refresh_ttl' => $_ENV['JWT_REFRESH_TTL'] ?? 86400 // 24 horas
            ],
            'basic' => [
                'driver' => 'basic',
                'realm' => $_ENV['BASIC_AUTH_REALM'] ?? 'HelixPHP API'
            ],
            'bearer' => [
                'driver' => 'bearer',
                'header' => 'Authorization',
                'prefix' => 'Bearer'
            ],
            'api_key' => [
                'driver' => 'api_key',
                'header' => $_ENV['API_KEY_HEADER'] ?? 'X-API-Key',
                'table' => 'api_keys',
                'key_column' => 'key',
                'user_column' => 'user_id'
            ]
        ],
        'middleware' => [
            'auto_detect' => true,
            'multiple_methods' => true,
            'fallback_to_guest' => false
        ]
    ],

    // Segurança
    'security' => [
        'cors' => [
            'enabled' => filter_var($_ENV['CORS_ENABLED'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'origins' => explode(',', $_ENV['CORS_ORIGINS'] ?? '*'),
            'methods' => explode(',', $_ENV['CORS_METHODS'] ?? 'GET,POST,PUT,DELETE,OPTIONS'),
            'headers' => explode(',', $_ENV['CORS_HEADERS'] ?? 'Content-Type,Authorization,X-API-Key'),
            'credentials' => filter_var($_ENV['CORS_CREDENTIALS'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'max_age' => $_ENV['CORS_MAX_AGE'] ?? 86400,
            'expose' => !empty($_ENV['CORS_EXPOSE']) ? explode(',', $_ENV['CORS_EXPOSE']) : []
        ],
        'csrf' => [
            'enabled' => filter_var($_ENV['CSRF_ENABLED'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'token_name' => '_token',
            'header_name' => 'X-CSRF-TOKEN',
            'lifetime' => 3600,
            'exclude_routes' => ['/api/*', '/webhooks/*']
        ],
        'rate_limiting' => [
            'enabled' => filter_var($_ENV['RATE_LIMIT_ENABLED'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'default_limit' => $_ENV['RATE_LIMIT_DEFAULT'] ?? 60, // requests per minute
            'burst_limit' => $_ENV['RATE_LIMIT_BURST'] ?? 100,
            'storage' => $_ENV['RATE_LIMIT_STORAGE'] ?? 'redis',
            'key_generator' => 'ip', // ip, user, api_key
            'routes' => [
                '/api/auth/*' => 10, // Limite menor para rotas de autenticação
                '/api/upload/*' => 5  // Limite menor para uploads
            ]
        ],
        'xss' => [
            'enabled' => filter_var($_ENV['XSS_PROTECTION'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'auto_escape' => true,
            'allowed_tags' => '<p><br><strong><em><ul><ol><li>',
            'encoding' => 'UTF-8'
        ],
        'headers' => [
            'X-Frame-Options' => 'DENY',
            'X-Content-Type-Options' => 'nosniff',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Content-Security-Policy' => "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'"
        ]
    ],

    // Logging
    'logging' => [
        'default' => $_ENV['LOG_CHANNEL'] ?? 'file',
        'channels' => [
            'file' => [
                'driver' => 'file',
                'path' => 'logs/app.log',
                'level' => $_ENV['LOG_LEVEL'] ?? 'debug',
                'max_files' => 30,
                'permission' => 0644
            ],
            'syslog' => [
                'driver' => 'syslog',
                'level' => $_ENV['LOG_LEVEL'] ?? 'debug'
            ],
            'error_log' => [
                'driver' => 'error_log',
                'level' => $_ENV['LOG_LEVEL'] ?? 'debug'
            ]
        ],
        'deprecations' => filter_var($_ENV['LOG_DEPRECATIONS'] ?? true, FILTER_VALIDATE_BOOLEAN)
    ],

    // Upload de arquivos
    'uploads' => [
        'max_file_size' => $_ENV['UPLOAD_MAX_SIZE'] ?? '10M',
        'allowed_types' => explode(',', $_ENV['UPLOAD_ALLOWED_TYPES'] ?? 'jpg,jpeg,png,gif,pdf,doc,docx'),
        'storage_path' => $_ENV['UPLOAD_PATH'] ?? 'storage/uploads',
        'url_prefix' => $_ENV['UPLOAD_URL_PREFIX'] ?? '/uploads',
        'virus_scan' => filter_var($_ENV['UPLOAD_VIRUS_SCAN'] ?? false, FILTER_VALIDATE_BOOLEAN)
    ],

    // Email
    'mail' => [
        'default' => $_ENV['MAIL_MAILER'] ?? 'smtp',
        'mailers' => [
            'smtp' => [
                'transport' => 'smtp',
                'host' => $_ENV['MAIL_HOST'] ?? '',
                'port' => $_ENV['MAIL_PORT'] ?? 587,
                'encryption' => $_ENV['MAIL_ENCRYPTION'] ?? 'tls',
                'username' => $_ENV['MAIL_USERNAME'] ?? '',
                'password' => $_ENV['MAIL_PASSWORD'] ?? ''
            ],
            'sendmail' => [
                'transport' => 'sendmail',
                'path' => $_ENV['MAIL_SENDMAIL_PATH'] ?? '/usr/sbin/sendmail -bs'
            ]
        ],
        'from' => [
            'address' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'hello@example.com',
            'name' => $_ENV['MAIL_FROM_NAME'] ?? 'HelixPHP'
        ]
    ],

    // Monitoramento
    'monitoring' => [
        'enabled' => filter_var($_ENV['MONITORING_ENABLED'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'metrics' => [
            'endpoint' => '/metrics',
            'include_system' => true,
            'include_application' => true,
            'include_performance' => true
        ],
        'health_check' => [
            'endpoint' => '/health',
            'checks' => ['database', 'redis', 'filesystem', 'memory']
        ],
        'alerts' => [
            'webhook_url' => $_ENV['ALERT_WEBHOOK'] ?? '',
            'email' => $_ENV['ALERT_EMAIL'] ?? '',
            'slack_webhook' => $_ENV['SLACK_WEBHOOK'] ?? ''
        ]
    ],

    // Middlewares
    'middlewares' => [
        'global' => [
            'security_headers',
            'cors',
            'rate_limiting'
        ],
        'web' => [
            'csrf',
            'xss_protection',
            'session'
        ],
        'api' => [
            'auth:jwt',
            'throttle:api'
        ]
    ],

    // Providers de serviços (desabilitado temporariamente)
    'providers' => [
        // 'Helix\\Providers\\AuthServiceProvider',
        // 'Helix\\Providers\\CacheServiceProvider',
        // 'Helix\\Providers\\DatabaseServiceProvider',
        // 'Helix\\Providers\\LoggingServiceProvider',
        // 'Helix\\Providers\\MailServiceProvider',
        // 'Helix\\Providers\\SecurityServiceProvider',
        // 'Helix\\Providers\\ValidationServiceProvider'
    ],

    // Extensions and Plugins Configuration
    'extensions' => [
        // Auto-discovery of service providers from composer packages
        'auto_discover_providers' => filter_var($_ENV['AUTO_DISCOVER_PROVIDERS'] ?? true, FILTER_VALIDATE_BOOLEAN),

        // Manual extension registration
        // 'my_extension' => [
        //     'provider' => 'Vendor\\Package\\ExpressServiceProvider',
        //     'config' => [
        //         'option1' => 'value1',
        //         'option2' => true
        //     ]
        // ]
    ],

    // Hook System Configuration
    'hooks' => [
        'enabled' => filter_var($_ENV['HOOKS_ENABLED'] ?? true, FILTER_VALIDATE_BOOLEAN),

        // Core hooks that are always available
        'core_hooks' => [
            'app.booting',
            'app.booted',
            'request.received',
            'response.sending',
            'middleware.before',
            'middleware.after',
            'route.matched',
            'route.executed'
        ]
    ],

    // Aliases
    'aliases' => [
        'Auth' => 'Helix\\Facades\\Auth',
        'Cache' => 'Helix\\Facades\\Cache',
        'Config' => 'Helix\\Facades\\Config',
        'Log' => 'Helix\\Facades\\Log',
        'Request' => 'Helix\\Facades\\Request',
        'Response' => 'Helix\\Facades\\Response',
        'Security' => 'Helix\\Facades\\Security',
        'Validator' => 'Helix\\Facades\\Validator'
    ]
];
