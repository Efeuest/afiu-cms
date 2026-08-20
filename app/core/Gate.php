<?php

declare(strict_types=1);

namespace AfiuCMS\Core;

final class Gate
{
    private const PERMISSIONS = [
        'administrator' => ['*'],
        'editor' => ['content.read','content.write','media.manage','taxonomy.manage'],
        'author' => ['content.read','post.write','media.manage'],
    ];

    public function __construct(private readonly Auth $auth) {}

    public function allows(string $permission): bool
    {
        $role = (string) ($this->auth->user()['role'] ?? '');
        $permissions = self::PERMISSIONS[$role] ?? [];
        return in_array('*', $permissions, true) || in_array($permission, $permissions, true);
    }

    public function role(): string
    {
        return (string) ($this->auth->user()['role'] ?? 'guest');
    }
}
