<?php

declare(strict_types=1);

namespace AfiuCMS\Controllers\Admin;

use AfiuCMS\Core\Auth;
use AfiuCMS\Core\Flash;
use AfiuCMS\Core\Http\Request;
use AfiuCMS\Core\Http\Response;
use AfiuCMS\Core\Settings;
use AfiuCMS\Core\ThemeManager;
use AfiuCMS\Core\View;
use Throwable;

final class ThemeController extends AdminController
{
    public function __construct(View $view, Auth $auth, Settings $settings, private readonly ThemeManager $themes) { parent::__construct($view, $auth, $settings); }

    public function index(): Response
    {
        return $this->page('admin.themes.index', ['themes' => $this->themes->themes()]);
    }

    public function activate(string $slug): Response
    {
        try {
            $this->themes->activate($slug);
            Flash::put('success', 'Theme activated.');
        } catch (Throwable $e) {
            Flash::put('error', $e->getMessage());
        }
        return Response::redirect('/admin/themes');
    }

    public function upload(Request $request): Response
    {
        try {
            $slug = $this->themes->installZip($request->file('theme') ?? []);
            Flash::put('success', "Theme {$slug} installed. Review it before activation.");
        } catch (Throwable $e) {
            Flash::put('error', $e->getMessage());
        }
        return Response::redirect('/admin/themes');
    }
}
