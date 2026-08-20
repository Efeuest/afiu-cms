<?php

declare(strict_types=1);

namespace AfiuCMS\Core;

final class Auth
{
    private ?array $cachedUser = null;
    private bool $resolved = false;

    public function __construct(private readonly Database $db) {}

    public function attempt(string $email, string $password): bool
    {
        $user = $this->db->one('SELECT * FROM users WHERE email = ? AND status = ? LIMIT 1', [mb_strtolower(trim($email)), 'active']);
        if (!$user || !password_verify($password, $user['password_hash'])) return false;
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            $this->db->execute('UPDATE users SET password_hash = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $user['id']]);
        }
        $this->db->execute('UPDATE users SET last_login_at=CURRENT_TIMESTAMP WHERE id=?', [(int) $user['id']]);
        Session::regenerate();
        Session::put('user_id', (int) $user['id']);
        Session::put('admin_last_activity', time());
        $this->cachedUser = $user;
        $this->resolved = true;
        return true;
    }

    public function user(): ?array
    {
        if ($this->resolved) return $this->cachedUser;
        $this->resolved = true;
        $id = (int) Session::get('user_id', 0);
        if ($id < 1) return null;
        $this->cachedUser = $this->db->one('SELECT id,name,email,role,status,last_login_at,bio,created_at FROM users WHERE id=? AND status=? LIMIT 1', [$id, 'active']);
        return $this->cachedUser;
    }

    public function check(): bool { return $this->user() !== null; }

    public function logout(): void
    {
        Session::forget('user_id');
        Session::forget('admin_last_activity');
        Session::regenerate();
        $this->cachedUser = null;
        $this->resolved = true;
    }
}
