<?php

declare(strict_types=1);

use AfiuCMS\Core\Database;

return static function (Database $db): void {
    $db->pdo()->exec("CREATE TABLE IF NOT EXISTS taxonomies (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        type VARCHAR(30) NOT NULL,
        name VARCHAR(120) NOT NULL,
        slug VARCHAR(150) NOT NULL,
        description TEXT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_taxonomy_type_slug (type, slug),
        INDEX idx_taxonomy_type_name (type, name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db->pdo()->exec("CREATE TABLE IF NOT EXISTS content_taxonomy (
        content_id BIGINT UNSIGNED NOT NULL,
        taxonomy_id BIGINT UNSIGNED NOT NULL,
        PRIMARY KEY (content_id, taxonomy_id),
        CONSTRAINT fk_ct_content FOREIGN KEY (content_id) REFERENCES content(id) ON DELETE CASCADE,
        CONSTRAINT fk_ct_taxonomy FOREIGN KEY (taxonomy_id) REFERENCES taxonomies(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
};
