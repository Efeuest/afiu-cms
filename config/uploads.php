<?php

declare(strict_types=1);

return [
    'max_bytes' => max(1, (int) ($_ENV['UPLOAD_MAX_MB'] ?? 10)) * 1024 * 1024,
];
