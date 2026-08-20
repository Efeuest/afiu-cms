<?php

declare(strict_types=1);

namespace AfiuCMS\Controllers\Admin;

use AfiuCMS\Core\Auth;
use AfiuCMS\Core\Flash;
use AfiuCMS\Core\Http\Request;
use AfiuCMS\Core\Http\Response;
use AfiuCMS\Core\Settings;
use AfiuCMS\Core\View;

final class SettingsController extends AdminController
{
    public function __construct(View $view, Auth $auth, Settings $settings) { parent::__construct($view, $auth, $settings); }

    public function index(): Response
    {
        return $this->page('admin.settings.general', ['values' => $this->settings->all()]);
    }

    public function update(Request $request): Response
    {
        $name = trim((string) $request->input('site_name'));
        if ($name === '') {
            Flash::put('error', 'Site name cannot be empty.');
            return Response::redirect('/admin/settings');
        }
        $this->settings->set('site_name', mb_substr($name, 0, 190));
        $this->settings->set('site_tagline', mb_substr(trim((string) $request->input('site_tagline')), 0, 255));
        Flash::put('success', 'Settings saved.');
        return Response::redirect('/admin/settings');
    }
}
