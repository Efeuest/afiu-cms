<?php

declare(strict_types=1);

use AfiuCMS\Core\Database;

return static function (Database $db): void {
    $columns = [
        'seo_title' => "VARCHAR(255) NULL AFTER status",
        'seo_description' => "VARCHAR(320) NULL AFTER seo_title",
        'canonical_url' => "VARCHAR(500) NULL AFTER seo_description",
        'featured_media_id' => "BIGINT UNSIGNED NULL AFTER canonical_url",
    ];
    foreach ($columns as $name => $definition) {
        $exists = $db->one("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='content' AND COLUMN_NAME=?", [$name]);
        if (!$exists) {
            $db->pdo()->exec("ALTER TABLE content ADD COLUMN `{$name}` {$definition}");
        }
    }
};
