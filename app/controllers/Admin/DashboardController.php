<?php

declare(strict_types=1);

namespace AfiuCMS\Controllers\Admin;

use AfiuCMS\Core\Auth;
use AfiuCMS\Core\Database;
use AfiuCMS\Core\Http\Response;
use AfiuCMS\Core\Settings;
use AfiuCMS\Core\View;

final class DashboardController extends AdminController
{
    public function __construct(View $view, Auth $auth, Settings $settings, private readonly Database $db) { parent::__construct($view, $auth, $settings); }

    public function index(): Response
    {
        $counts = [
            'pages' => (int) ($this->db->one("SELECT COUNT(*) AS c FROM content WHERE type='page'")['c'] ?? 0),
            'posts' => (int) ($this->db->one("SELECT COUNT(*) AS c FROM content WHERE type='post'")['c'] ?? 0),
            'media' => (int) ($this->db->one('SELECT COUNT(*) AS c FROM media')['c'] ?? 0),
        ];
        $recent = $this->db->all('SELECT id, type, title, status, updated_at FROM content ORDER BY updated_at DESC LIMIT 8');
        return $this->page('admin.dashboard', compact('counts', 'recent'));
    }
}
