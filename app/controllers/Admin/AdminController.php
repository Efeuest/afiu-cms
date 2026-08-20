<?php

declare(strict_types=1);

namespace AfiuCMS\Controllers\Admin;

use AfiuCMS\Core\Auth;
use AfiuCMS\Core\Flash;
use AfiuCMS\Core\Gate;
use AfiuCMS\Core\Http\Response;
use AfiuCMS\Core\Settings;
use AfiuCMS\Core\View;

abstract class AdminController
{
    public function __construct(protected readonly View $view, protected readonly Auth $auth, protected readonly Settings $settings) {}

    protected function page(string $view, array $data = [], int $status = 200): Response
    {
        $titles = [
            'admin.dashboard' => 'Dashboard', 'admin.content.index' => ($data['type'] ?? '') === 'page' ? 'Pages' : 'Posts',
            'admin.content.form' => (($data['item']['id'] ?? null) ? 'Edit ' : 'Create ') . ucfirst((string) ($data['type'] ?? 'content')),
            'admin.media.index' => 'Media Library', 'admin.themes.index' => 'Themes', 'admin.settings.general' => 'Settings',
            'admin.users.index' => 'Users', 'admin.users.form' => (($data['item']['id'] ?? null) ? 'Edit user' : 'Add user'),
            'admin.taxonomies.index' => ($data['type'] ?? '') === 'category' ? 'Categories' : 'Tags',
            'admin.menus.index' => 'Menus', 'admin.audit.index' => 'Activity log', 'errors.not-found' => 'Not found',
        ];
        $user = $this->auth->user();
        return Response::html($this->view->render($view, $data + [
            'currentUser' => $user, 'flash' => Flash::pull(), 'siteName' => $this->settings->get('site_name', 'AfiuCMS'),
            'pageTitle' => $titles[$view] ?? 'Admin', 'currentPath' => parse_url($_SERVER['REQUEST_URI'] ?? '/admin', PHP_URL_PATH) ?: '/admin',
            'isAdministrator' => ($user['role'] ?? '') === 'administrator',
        ], 'layouts.admin'), $status);
    }

    protected function forbidden(): Response
    {
        return Response::html('<h1>403 Forbidden</h1><p>You do not have permission to perform this action.</p>', 403);
    }
}
