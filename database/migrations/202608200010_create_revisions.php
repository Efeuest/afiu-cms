<?php

declare(strict_types=1);

use AfiuCMS\Core\Database;

return static function (Database $db): void {
    $db->pdo()->exec("CREATE TABLE IF NOT EXISTS content_revisions (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        content_id BIGINT UNSIGNED NOT NULL,
        user_id BIGINT UNSIGNED NOT NULL,
        title VARCHAR(255) NOT NULL,
        excerpt TEXT NULL,
        body LONGTEXT NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_revision_content FOREIGN KEY (content_id) REFERENCES content(id) ON DELETE CASCADE,
        CONSTRAINT fk_revision_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
        INDEX idx_revision_content (content_id, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
};
