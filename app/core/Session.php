<?php

declare(strict_types=1);

namespace AfiuCMS\Core;

final class Session
{
    public static function start(Config $config, string $savePath): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        if (!is_dir($savePath)) {
            mkdir($savePath, 0775, true);
        }
        session_save_path($savePath);
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.use_trans_sid', '0');
        session_name('AFIUCMSID');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => (bool) $config->get('session.secure', false),
            'httponly' => true,
            'samesite' => (string) $config->get('session.same_site', 'Lax'),
        ]);
        session_start();
    }

    public static function get(string $key, mixed $default = null): mixed { return $_SESSION[$key] ?? $default; }
    public static function put(string $key, mixed $value): void { $_SESSION[$key] = $value; }
    public static function forget(string $key): void { unset($_SESSION[$key]); }
    public static function regenerate(): void { session_regenerate_id(true); }
    public static function invalidate(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool) $params['secure'], (bool) $params['httponly']);
        }
        session_destroy();
    }
}
