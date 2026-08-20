<?php

declare(strict_types=1);

namespace AfiuCMS\Core\Middleware;

use AfiuCMS\Core\Contracts\Middleware;
use AfiuCMS\Core\Http\Request;
use AfiuCMS\Core\Http\Response;

final class SecurityHeaders implements Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        return $next($request)
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('X-Frame-Options', 'SAMEORIGIN')
            ->withHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->withHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }
}
