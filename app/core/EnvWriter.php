<?php

declare(strict_types=1);

namespace AfiuCMS\Core;

use RuntimeException;

final class EnvWriter
{
    public function __construct(private readonly Config $config) {}

    public function write(array $values): void
    {
        $path = (string) $this->config->get('paths.env');
        $lines = [];
        foreach ($values as $key => $value) {
            if (is_bool($value)) {
                $rendered = $value ? 'true' : 'false';
            } elseif (is_int($value) || is_float($value)) {
                $rendered = (string) $value;
            } else {
                $rendered = '"' . str_replace(['\\', '"', "\n", "\r"], ['\\\\', '\\"', '\\n', ''], (string) $value) . '"';
            }
            $lines[] = $key . '=' . $rendered;
        }
        $content = implode(PHP_EOL, $lines) . PHP_EOL;
        $tmp = $path . '.tmp';
        if (file_put_contents($tmp, $content, LOCK_EX) === false || !rename($tmp, $path)) {
            @unlink($tmp);
            throw new RuntimeException('Unable to write .env. Check project directory permissions.');
        }
        @chmod($path, 0600);
    }
}
