<?php

declare(strict_types=1);

namespace AfiuCMS\Core;

final class Flash
{
    public static function put(string $type, string $message): void
    {
        Session::put('_flash', ['type' => $type, 'message' => $message]);
    }

    public static function pull(): ?array
    {
        $flash = Session::get('_flash');
        Session::forget('_flash');
        return is_array($flash) ? $flash : null;
    }
}
