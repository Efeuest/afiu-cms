<?php

declare(strict_types=1);

use AfiuCMS\Core\Database;

return static function (Database $db): void {
    $db->pdo()->exec("CREATE TABLE IF NOT EXISTS audit_logs (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NULL,
        action VARCHAR(80) NOT NULL,
        entity_type VARCHAR(80) NULL,
        entity_id BIGINT UNSIGNED NULL,
        context_json LONGTEXT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_audit_created (created_at),
        INDEX idx_audit_entity (entity_type, entity_id),
        CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
};
