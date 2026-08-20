<?php

declare(strict_types=1);

namespace AfiuCMS\Core;

final class Csrf
{
    public static function token(): string
    {
        $token = Session::get('_csrf');
        if (!is_string($token) || strlen($token) < 32) {
            $token = bin2hex(random_bytes(32));
            Session::put('_csrf', $token);
        }
        return $token;
    }

    public static function valid(?string $token): bool
    {
        $stored = Session::get('_csrf');
        return is_string($stored) && is_string($token) && hash_equals($stored, $token);
    }
}
