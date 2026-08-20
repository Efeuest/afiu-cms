<?php

declare(strict_types=1);

namespace AfiuCMS\Core;

final class MenuManager
{
    public function __construct(private readonly Database $db) {}

    public function location(string $location = 'primary'): array
    {
        $menu = $this->db->one('SELECT * FROM menus WHERE location=? LIMIT 1', [$location]);
        if (!$menu) return [];
        return $this->db->all('SELECT * FROM menu_items WHERE menu_id=? ORDER BY sort_order ASC, id ASC', [(int) $menu['id']]);
    }
}
