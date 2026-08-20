<?php

declare(strict_types=1);

use AfiuCMS\Core\Database;

return static function (Database $db): void {
    $db->pdo()->exec("CREATE TABLE IF NOT EXISTS menus (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(120) NOT NULL,
        location VARCHAR(60) NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_menu_location (location)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db->pdo()->exec("CREATE TABLE IF NOT EXISTS menu_items (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        menu_id BIGINT UNSIGNED NOT NULL,
        label VARCHAR(150) NOT NULL,
        url VARCHAR(500) NOT NULL,
        target VARCHAR(20) NOT NULL DEFAULT '_self',
        sort_order INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_menu_item_menu FOREIGN KEY (menu_id) REFERENCES menus(id) ON DELETE CASCADE,
        INDEX idx_menu_items_order (menu_id, sort_order, id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
};
