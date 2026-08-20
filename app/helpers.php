<?php

declare(strict_types=1);

use AfiuCMS\Core\Csrf;

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(Csrf::token()) . '">';
    }
}

if (!function_exists('selected')) {
    function selected(mixed $value, mixed $expected): string
    {
        return $value === $expected ? ' selected' : '';
    }
}

if (!function_exists('checked')) {
    function checked(bool $value): string
    {
        return $value ? ' checked' : '';
    }
}
