<?php

declare(strict_types=1);

namespace AfiuCMS\Core;

final class Slugger
{
    public static function make(string $value): string
    {
        $map = ['ı'=>'i','İ'=>'i','ğ'=>'g','Ğ'=>'g','ü'=>'u','Ü'=>'u','ş'=>'s','Ş'=>'s','ö'=>'o','Ö'=>'o','ç'=>'c','Ç'=>'c'];
        $value = strtr(trim($value), $map);
        $value = mb_strtolower($value, 'UTF-8');
        if (function_exists('iconv')) {
            $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            if (is_string($ascii)) {
                $value = $ascii;
            }
        }
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        $value = trim($value, '-');
        return $value !== '' ? $value : 'content-' . bin2hex(random_bytes(3));
    }
}
