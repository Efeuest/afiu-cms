<?php

declare(strict_types=1);

return [
    'secure' => filter_var($_ENV['SESSION_SECURE'] ?? false, FILTER_VALIDATE_BOOL),
    'same_site' => $_ENV['SESSION_SAME_SITE'] ?? 'Lax',
];
