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
            'pages'=>(int)($this->db->one("SELECT COUNT(*) c FROM content WHERE type='page'")['c']??0),
            'posts'=>(int)($this->db->one("SELECT COUNT(*) c FROM content WHERE type='post'")['c']??0),
            'published'=>(int)($this->db->one("SELECT COUNT(*) c FROM content WHERE status='published'")['c']??0),
            'media'=>(int)($this->db->one('SELECT COUNT(*) c FROM media')['c']??0),
            'users'=>(int)($this->db->one('SELECT COUNT(*) c FROM users')['c']??0),
        ];
        $recent = $this->db->all('SELECT c.id,c.type,c.title,c.status,c.updated_at,u.name author_name FROM content c JOIN users u ON u.id=c.author_id ORDER BY c.updated_at DESC LIMIT 8');
        $activity = $this->db->all('SELECT a.*,u.name user_name FROM audit_logs a LEFT JOIN users u ON u.id=a.user_id ORDER BY a.created_at DESC LIMIT 7');
        return $this->page('admin.dashboard', compact('counts','recent','activity'));
    }
}
