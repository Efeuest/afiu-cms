<?php

declare(strict_types=1);

namespace AfiuCMS\Controllers\Admin;

use AfiuCMS\Core\Auth;
use AfiuCMS\Core\Database;
use AfiuCMS\Core\Flash;
use AfiuCMS\Core\Http\Request;
use AfiuCMS\Core\Http\Response;
use AfiuCMS\Core\Settings;
use AfiuCMS\Core\Slugger;
use AfiuCMS\Core\View;

final class ContentController extends AdminController
{
    public function __construct(View $view, Auth $auth, Settings $settings, private readonly Database $db) { parent::__construct($view, $auth, $settings); }

    public function pages(): Response { return $this->listing('page'); }
    public function posts(): Response { return $this->listing('post'); }
    public function createPage(): Response { return $this->form('page'); }
    public function createPost(): Response { return $this->form('post'); }

    public function storePage(Request $request): Response { return $this->store($request, 'page'); }
    public function storePost(Request $request): Response { return $this->store($request, 'post'); }

    public function editPage(string $id): Response { return $this->edit((int) $id, 'page'); }
    public function editPost(string $id): Response { return $this->edit((int) $id, 'post'); }
    public function updatePage(Request $request, string $id): Response { return $this->update($request, (int) $id, 'page'); }
    public function updatePost(Request $request, string $id): Response { return $this->update($request, (int) $id, 'post'); }
    public function deletePage(string $id): Response { return $this->delete((int) $id, 'page'); }
    public function deletePost(string $id): Response { return $this->delete((int) $id, 'post'); }

    private function listing(string $type): Response
    {
        $items = $this->db->all('SELECT c.*, u.name author_name FROM content c JOIN users u ON u.id=c.author_id WHERE c.type=? ORDER BY c.updated_at DESC', [$type]);
        return $this->page('admin.content.index', ['items' => $items, 'type' => $type]);
    }

    private function form(string $type, ?array $item = null, ?string $error = null): Response
    {
        return $this->page('admin.content.form', ['type' => $type, 'item' => $item, 'error' => $error]);
    }

    private function store(Request $request, string $type): Response
    {
        [$data, $error] = $this->validate($request, $type, null);
        if ($error) return $this->form($type, $request->all(), $error);
        $this->db->execute('INSERT INTO content (type,title,slug,excerpt,body,status,author_id,published_at) VALUES (?,?,?,?,?,?,?,?)', [
            $type, $data['title'], $data['slug'], $data['excerpt'], $data['body'], $data['status'], (int) ($this->auth->user()['id'] ?? 0), $data['published_at']
        ]);
        Flash::put('success', ucfirst($type) . ' created.');
        return Response::redirect('/admin/' . ($type === 'page' ? 'pages' : 'posts'));
    }

    private function edit(int $id, string $type): Response
    {
        $item = $this->db->one('SELECT * FROM content WHERE id=? AND type=?', [$id, $type]);
        return $item ? $this->form($type, $item) : $this->page('errors.not-found', [], 404);
    }

    private function update(Request $request, int $id, string $type): Response
    {
        $exists = $this->db->one('SELECT * FROM content WHERE id=? AND type=?', [$id, $type]);
        if (!$exists) return $this->page('errors.not-found', [], 404);
        [$data, $error] = $this->validate($request, $type, $id);
        if ($error) return $this->form($type, array_merge($exists, $request->all()), $error);
        $this->db->execute('UPDATE content SET title=?, slug=?, excerpt=?, body=?, status=?, published_at=?, updated_at=CURRENT_TIMESTAMP WHERE id=? AND type=?', [
            $data['title'], $data['slug'], $data['excerpt'], $data['body'], $data['status'], $data['published_at'], $id, $type
        ]);
        Flash::put('success', ucfirst($type) . ' updated.');
        return Response::redirect('/admin/' . ($type === 'page' ? 'pages' : 'posts'));
    }

    private function delete(int $id, string $type): Response
    {
        $this->db->execute('DELETE FROM content WHERE id=? AND type=?', [$id, $type]);
        Flash::put('success', ucfirst($type) . ' deleted.');
        return Response::redirect('/admin/' . ($type === 'page' ? 'pages' : 'posts'));
    }

    private function validate(Request $request, string $type, ?int $ignoreId): array
    {
        $title = trim((string) $request->input('title'));
        $slug = Slugger::make(trim((string) $request->input('slug', '')) ?: $title);
        $excerpt = trim((string) $request->input('excerpt'));
        $body = (string) $request->input('body');
        $status = (string) $request->input('status', 'draft');
        if ($title === '') return [[], 'Title is required.'];
        if (!in_array($status, ['draft', 'published'], true)) return [[], 'Invalid status.'];
        $duplicate = $ignoreId === null
            ? $this->db->one('SELECT id FROM content WHERE slug=? LIMIT 1', [$slug])
            : $this->db->one('SELECT id FROM content WHERE slug=? AND id<>? LIMIT 1', [$slug, $ignoreId]);
        if ($duplicate) return [[], 'This slug is already used by another item.'];
        return [[
            'title' => mb_substr($title, 0, 255), 'slug' => $slug, 'excerpt' => $excerpt, 'body' => $body,
            'status' => $status, 'published_at' => $status === 'published' ? date('Y-m-d H:i:s') : null,
        ], null];
    }
}
