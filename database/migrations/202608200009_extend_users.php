<?php

declare(strict_types=1);

use AfiuCMS\Core\Database;

return static function (Database $db): void {
    foreach ([
        'last_login_at' => 'DATETIME NULL AFTER status',
        'bio' => 'TEXT NULL AFTER last_login_at',
    ] as $name => $definition) {
        $exists = $db->one("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME=?", [$name]);
        if (!$exists) {
            $db->pdo()->exec("ALTER TABLE users ADD COLUMN `{$name}` {$definition}");
        }
    }
};
