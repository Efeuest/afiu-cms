<?php

declare(strict_types=1);

namespace AfiuCMS\Core\Middleware;

use AfiuCMS\Core\Auth;
use AfiuCMS\Core\Contracts\Middleware;
use AfiuCMS\Core\Http\Request;
use AfiuCMS\Core\Http\Response;

final class GuestOnly implements Middleware
{
    public function __construct(private readonly Auth $auth) {}

    public function handle(Request $request, callable $next): Response
    {
        return $this->auth->check() ? Response::redirect('/admin') : $next($request);
    }
}
