<?php

declare(strict_types=1);

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$publicRoot = realpath(__DIR__);
$requested = realpath(__DIR__ . $uri);
if ($publicRoot !== false && $requested !== false && str_starts_with($requested, $publicRoot . DIRECTORY_SEPARATOR) && is_file($requested)) {
    return false;
}
require __DIR__ . '/index.php';
