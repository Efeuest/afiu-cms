<?php

declare(strict_types=1);

namespace AfiuCMS\Core;

final class Settings
{
    private ?array $cache = null;

    public function __construct(private readonly Database $db) {}

    public function all(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }
        $rows = $this->db->all('SELECT setting_key, setting_value FROM settings');
        $this->cache = [];
        foreach ($rows as $row) {
            $this->cache[$row['setting_key']] = $row['setting_value'];
        }
        return $this->cache;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public function set(string $key, string $value): void
    {
        $this->db->execute(
            'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = CURRENT_TIMESTAMP',
            [$key, $value]
        );
        $this->cache = null;
    }
}
