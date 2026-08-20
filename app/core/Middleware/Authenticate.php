<?php

declare(strict_types=1);

namespace AfiuCMS\Core\Middleware;

use AfiuCMS\Core\Auth;
use AfiuCMS\Core\Config;
use AfiuCMS\Core\Contracts\Middleware;
use AfiuCMS\Core\Flash;
use AfiuCMS\Core\Http\Request;
use AfiuCMS\Core\Http\Response;
use AfiuCMS\Core\Session;

final class Authenticate implements Middleware
{
    public function __construct(private readonly Auth $auth, private readonly Config $config) {}

    public function handle(Request $request, callable $next): Response
    {
        if (!$this->auth->check()) {
            Flash::put('warning', 'Please sign in to continue.');
            return Response::redirect('/admin/login');
        }
        $last = (int) Session::get('admin_last_activity', time());
        $timeout = (int) $this->config->get('session.idle_timeout', 3600);
        if ($last > 0 && time() - $last > $timeout) {
            $this->auth->logout();
            Flash::put('warning', 'Your admin session expired due to inactivity.');
            return Response::redirect('/admin/login');
        }
        Session::put('admin_last_activity', time());
        return $next($request);
    }
}
