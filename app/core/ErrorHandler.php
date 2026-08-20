<?php

declare(strict_types=1);

namespace AfiuCMS\Core;

use ErrorException;
use Throwable;

final class ErrorHandler
{
    public static function register(bool $debug, string $logFile): void
    {
        ini_set('display_errors', $debug ? '1' : '0');
        error_reporting(E_ALL);
        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity)) return false;
            throw new ErrorException($message, 0, $severity, $file, $line);
        });
        set_exception_handler(static function (Throwable $e) use ($debug, $logFile): void {
            $directory = dirname($logFile);
            if (!is_dir($directory)) @mkdir($directory, 0775, true);
            @file_put_contents($logFile, sprintf("[%s] %s in %s:%d\n%s\n\n", date('c'), $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString()), FILE_APPEND | LOCK_EX);
            http_response_code(500);
            header('Content-Type: text/html; charset=UTF-8');
            if (!$debug) {
                echo '<!doctype html><meta charset="utf-8"><title>AfiuCMS error</title><h1>500 Internal Server Error</h1><p>An unexpected error occurred.</p>';
                return;
            }
            echo '<!doctype html><meta charset="utf-8"><title>AfiuCMS development error</title><style>body{font-family:ui-monospace,monospace;background:#111;color:#eee;padding:32px}pre{white-space:pre-wrap;background:#1d1d1d;padding:20px;border-radius:12px}code{color:#ffcf70}</style>';
            echo '<h1>AfiuCMS Development Error</h1><p><code>' . e($e->getMessage()) . '</code></p><p>' . e($e->getFile()) . ':' . $e->getLine() . '</p><pre>' . e($e->getTraceAsString()) . '</pre>';
        });
    }
}
