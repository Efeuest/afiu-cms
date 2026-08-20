<?php

declare(strict_types=1);

namespace AfiuCMS\Controllers;

use AfiuCMS\Core\Auth;
use AfiuCMS\Core\Flash;
use AfiuCMS\Core\Http\Request;
use AfiuCMS\Core\Http\Response;
use AfiuCMS\Core\Session;
use AfiuCMS\Core\View;

final class AuthController
{
    public function __construct(private readonly Auth $auth, private readonly View $view) {}

    public function showLogin(): Response
    {
        return Response::html($this->view->render('auth.login', ['flash' => Flash::pull()]));
    }

    public function login(Request $request): Response
    {
        $lockedUntil = (int) Session::get('login_locked_until', 0);
        if ($lockedUntil > time()) {
            Flash::put('error', 'Too many failed attempts. Try again shortly.');
            return Response::redirect('/admin/login');
        }
        if ($this->auth->attempt((string) $request->input('email'), (string) $request->input('password'))) {
            Session::forget('login_failures');
            Session::forget('login_locked_until');
            return Response::redirect('/admin');
        }
        $failures = (int) Session::get('login_failures', 0) + 1;
        Session::put('login_failures', $failures);
        if ($failures >= 5) {
            Session::put('login_locked_until', time() + 60);
            Session::put('login_failures', 0);
        }
        Flash::put('error', 'Email or password is incorrect.');
        return Response::redirect('/admin/login');
    }

    public function logout(): Response
    {
        $this->auth->logout();
        Flash::put('success', 'You have been signed out.');
        return Response::redirect('/admin/login');
    }
}
