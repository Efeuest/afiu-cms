<?php

declare(strict_types=1);

return [
    'name' => $_ENV['APP_NAME'] ?? 'AfiuCMS',
    'version' => '0.1.3-alpha',
    'environment' => $_ENV['APP_ENV'] ?? 'production',
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOL),
    'url' => rtrim($_ENV['APP_URL'] ?? 'http://localhost', '/'),
    'timezone' => $_ENV['APP_TIMEZONE'] ?? 'UTC',
    'key' => $_ENV['APP_KEY'] ?? '',
    'installed' => filter_var($_ENV['APP_INSTALLED'] ?? false, FILTER_VALIDATE_BOOL),
];
