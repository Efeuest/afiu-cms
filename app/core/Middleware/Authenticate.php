<?php

declare(strict_types=1);

namespace AfiuCMS\Core\Middleware;

use AfiuCMS\Core\Auth;
use AfiuCMS\Core\Contracts\Middleware;
use AfiuCMS\Core\Flash;
use AfiuCMS\Core\Http\Request;
use AfiuCMS\Core\Http\Response;

final class Authenticate implements Middleware
{
    public function __construct(private readonly Auth $auth) {}

    public function handle(Request $request, callable $next): Response
    {
        if (!$this->auth->check()) {
            Flash::put('warning', 'Please sign in to continue.');
            return Response::redirect('/admin/login');
        }
        return $next($request);
    }
}
