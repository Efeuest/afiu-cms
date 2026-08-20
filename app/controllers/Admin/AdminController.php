<?php

declare(strict_types=1);

namespace AfiuCMS\Controllers\Admin;

use AfiuCMS\Core\Auth;
use AfiuCMS\Core\Flash;
use AfiuCMS\Core\Http\Response;
use AfiuCMS\Core\Settings;
use AfiuCMS\Core\View;

abstract class AdminController
{
    public function __construct(protected readonly View $view, protected readonly Auth $auth, protected readonly Settings $settings) {}

    protected function page(string $view, array $data = [], int $status = 200): Response
    {
        $titles = [
            'admin.dashboard' => 'Dashboard',
            'admin.content.index' => ($data['type'] ?? '') === 'page' ? 'Pages' : 'Posts',
            'admin.content.form' => (($data['item']['id'] ?? null) ? 'Edit ' : 'Create ') . ucfirst((string) ($data['type'] ?? 'content')),
            'admin.media.index' => 'Media',
            'admin.themes.index' => 'Themes',
            'admin.settings.general' => 'Settings',
            'errors.not-found' => 'Not found',
        ];

        return Response::html($this->view->render($view, $data + [
            'currentUser' => $this->auth->user(),
            'flash' => Flash::pull(),
            'siteName' => $this->settings->get('site_name', 'AfiuCMS'),
            'pageTitle' => $titles[$view] ?? 'Admin',
        ], 'layouts.admin'), $status);
    }
}
