<?php

declare(strict_types=1);

namespace AfiuCMS\Core;

final class AuditLogger
{
    public function __construct(private readonly Database $db, private readonly Auth $auth) {}

    public function record(string $action, ?string $entityType = null, ?int $entityId = null, array $context = []): void
    {
        $userId = (int) ($this->auth->user()['id'] ?? 0);
        $this->db->execute(
            'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, context_json) VALUES (?, ?, ?, ?, ?)',
            [$userId > 0 ? $userId : null, mb_substr($action, 0, 80), $entityType, $entityId, $context === [] ? null : json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]
        );
    }
}
