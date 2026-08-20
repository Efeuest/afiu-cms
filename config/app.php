<?php

declare(strict_types=1);

return [
    'name' => $_ENV['APP_NAME'] ?? 'AfiuCMS',
    'version' => '0.2.1-alpha',
    'environment' => $_ENV['APP_ENV'] ?? 'production',
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOL),
    'url' => rtrim($_ENV['APP_URL'] ?? 'http://127.0.0.1:8000', '/'),
    'timezone' => $_ENV['APP_TIMEZONE'] ?? 'UTC',
    'key' => $_ENV['APP_KEY'] ?? null,
    'installed' => filter_var($_ENV['APP_INSTALLED'] ?? false, FILTER_VALIDATE_BOOL),
];
