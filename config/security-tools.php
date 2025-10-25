<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Report
    |--------------------------------------------------------------------------
    */
    'report' => [
        'markdown_path' => 'storage/logs/security-report.md',
    ],

    /*
    |--------------------------------------------------------------------------
    | Environment (.env) rules
    |--------------------------------------------------------------------------
    | Правила делятся на:
    | - required_non_empty: ключ должен существовать и быть непустым
    | - dangerous_values: недопустимые значения (всегда)
    | - production_forbidden: значения, запрещённые в production
    | - must_be_one_of: жёсткий whitelist значений
    | - regex: ключ должен удовлетворять паттерну
    | - url_must_be_https: для URL-переменных обязателен https в prod
    */
    'env' => [

        // Базово обязаны быть заданы
        'required_non_empty' => [
            'APP_KEY',
            'APP_URL',
            'LOG_CHANNEL',
            'DB_CONNECTION',
            // для не-sqlite:
            'DB_HOST', 'DB_DATABASE', 'DB_USERNAME',
            // если почта включена:
            'MAIL_MAILER',
        ],

        // Значения, которые опасны в любом окружении
        'dangerous_values' => [
            'APP_DEBUG' => ['true', '1'],
            'APP_ENV'   => ['local', 'development', 'dev'], // если внезапно в проде
        ],

        // В production запрещены такие значения
        'production_forbidden' => [
            'SESSION_DRIVER'   => ['array'],       // сессии не держатся
            'CACHE_DRIVER'     => ['array'],       // нет кэша
            'QUEUE_CONNECTION' => ['sync'],        // задачи в том же процессе
            'MAIL_MAILER'      => ['log', 'array'],// письма «в никуда»
            'LOG_LEVEL'        => ['debug'],       // слишком шумно
            'BROADCAST_DRIVER' => ['log', 'null'], // реального брокера нет
        ],

        // Жёсткий whitelist некоторых ключей
        'must_be_one_of' => [
            'APP_ENV'         => ['local', 'staging', 'production', 'testing'],
            'LOG_CHANNEL'     => ['stack', 'single', 'daily', 'errorlog'],
            'SESSION_SECURE_COOKIE' => ['true','false','1','0'],
        ],

        // Проверки регулярками
        'regex' => [
            // base64: ключ длиной 32 байта после decode
            'APP_KEY' => '/^base64:[A-Za-z0-9+\/=]+$/',
            // простой чек домена / URL (в проде ниже ещё проверим https)
            'APP_URL' => '#^https?://[a-z0-9\.\-_:]+/?#i',
        ],

        // В продакшне обязателен https для этих URL
        'url_must_be_https' => [
            'APP_URL',
            'ASSET_URL',
        ],

        // Нежелательные подстроки в проде (часто забывают)
        'production_disallow_substrings' => [
            'APP_URL' => ['localhost', '127.0.0.1'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Runtime config() checks
    |--------------------------------------------------------------------------
    | Проверки на уровне уже загруженных конфигов.
    */
    'config_checks' => [

        // app.debug в продакшне
        'app.debug_false_in_production' => true,

        // cookie security
        'session.secure_true_in_production' => true,
        'session.http_only_true'           => true,
        'session.same_site_not_none_without_https' => true,

        // CORS
        'cors.disallow_star_in_production' => true,

        // URL
        'app.url_https_in_production' => true,

        // драйверы
        'cache.not_array_in_production'     => true,
        'queue.not_sync_in_production'      => true,
        'session.not_array_in_production'   => true,
        'mail.not_log_in_production'        => true,

        // доверенные прокси (если за Cloudflare/NGINX)
        'trustedproxy.not_wildcard_in_production' => true, // не '*'

        // логирование
        'log.level_not_debug_in_production' => true,
    ],
];