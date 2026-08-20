<?php

declare(strict_types=1);

namespace AfiuCMS\Controllers;

use AfiuCMS\Core\Database;
use AfiuCMS\Core\Http\Response;
use AfiuCMS\Core\Settings;
use AfiuCMS\Core\ThemeManager;

final class SiteController
{
    public function __construct(private readonly Database $db, private readonly Settings $settings, private readonly ThemeManager $themes) {}

    public function home(): Response
    {
        $posts = $this->db->all("SELECT id,title,slug,excerpt,published_at FROM content WHERE type='post' AND status='published' ORDER BY published_at DESC LIMIT 6");
        return Response::html($this->themes->render('home', ['settings' => $this->settings->all(), 'posts' => $posts]));
    }

    public function blog(): Response
    {
        $posts = $this->db->all("SELECT id,title,slug,excerpt,published_at FROM content WHERE type='post' AND status='published' ORDER BY published_at DESC LIMIT 100");
        return Response::html($this->themes->render('blog', ['settings' => $this->settings->all(), 'posts' => $posts]));
    }

    public function post(string $slug): Response
    {
        $post = $this->db->one("SELECT c.*, u.name author_name FROM content c JOIN users u ON u.id=c.author_id WHERE c.type='post' AND c.slug=? AND c.status='published' LIMIT 1", [$slug]);
        return $post
            ? Response::html($this->themes->render('post', ['settings' => $this->settings->all(), 'post' => $post]))
            : Response::html($this->themes->render('404', ['settings' => $this->settings->all()]), 404);
    }

    public function page(string $slug): Response
    {
        $page = $this->db->one("SELECT c.*, u.name author_name FROM content c JOIN users u ON u.id=c.author_id WHERE c.type='page' AND c.slug=? AND c.status='published' LIMIT 1", [$slug]);
        return $page
            ? Response::html($this->themes->render('page', ['settings' => $this->settings->all(), 'page' => $page]))
            : Response::html($this->themes->render('404', ['settings' => $this->settings->all()]), 404);
    }
}
