<?php

declare(strict_types=1);

namespace AfiuCMS\Core\Middleware;

use AfiuCMS\Core\Contracts\Middleware;
use AfiuCMS\Core\Csrf;
use AfiuCMS\Core\Http\Request;
use AfiuCMS\Core\Http\Response;

final class VerifyCsrfToken implements Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        if (!in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true) && !Csrf::valid(is_string($request->input('_token')) ? $request->input('_token') : null)) {
            return Response::html('<h1>419 Page Expired</h1><p>The security token is invalid or expired. Go back and try again.</p>', 419);
        }
        return $next($request);
    }
}
