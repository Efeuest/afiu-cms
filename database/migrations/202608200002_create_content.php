<?php

declare(strict_types=1);

use AfiuCMS\Core\Database;

return static function (Database $db): void {
    $db->pdo()->exec("CREATE TABLE IF NOT EXISTS content (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        type VARCHAR(20) NOT NULL,
        title VARCHAR(255) NOT NULL,
        slug VARCHAR(190) NOT NULL UNIQUE,
        excerpt TEXT NULL,
        body LONGTEXT NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'draft',
        author_id BIGINT UNSIGNED NOT NULL,
        published_at DATETIME NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_content_type_status (type, status),
        INDEX idx_content_published (published_at),
        CONSTRAINT fk_content_author FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
};
