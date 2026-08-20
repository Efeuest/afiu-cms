<?php

declare(strict_types=1);

namespace AfiuCMS\Core;

use RuntimeException;

final class Config
{
    private array $items = [];

    public function loadDirectory(string $directory): void
    {
        foreach (glob(rtrim($directory, '/') . '/*.php') ?: [] as $file) {
            $this->load(pathinfo($file, PATHINFO_FILENAME), $file);
        }
    }

    public function load(string $name, string $file): void
    {
        $value = require $file;
        if (!is_array($value)) {
            throw new RuntimeException("Config file {$file} must return an array.");
        }
        $this->items[$name] = $value;
    }

    public function set(string $key, mixed $value): void
    {
        $segments = explode('.', $key);
        $target =& $this->items;

        foreach ($segments as $index => $segment) {
            if ($index === array_key_last($segments)) {
                $target[$segment] = $value;
                return;
            }

            if (!isset($target[$segment]) || !is_array($target[$segment])) {
                $target[$segment] = [];
            }

            $target =& $target[$segment];
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->items;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }

    public function all(): array
    {
        return $this->items;
    }
}
