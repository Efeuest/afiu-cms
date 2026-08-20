<?php

declare(strict_types=1);

namespace AfiuCMS\Controllers\Admin;

use AfiuCMS\Core\Auth;
use AfiuCMS\Core\Database;
use AfiuCMS\Core\Flash;
use AfiuCMS\Core\Http\Request;
use AfiuCMS\Core\Http\Response;
use AfiuCMS\Core\MediaManager;
use AfiuCMS\Core\Settings;
use AfiuCMS\Core\View;
use Throwable;

final class MediaController extends AdminController
{
    public function __construct(View $view, Auth $auth, Settings $settings, private readonly Database $db, private readonly MediaManager $media) { parent::__construct($view, $auth, $settings); }

    public function index(): Response
    {
        $items = $this->db->all('SELECT m.*, u.name uploader FROM media m JOIN users u ON u.id=m.uploaded_by ORDER BY m.created_at DESC');
        return $this->page('admin.media.index', compact('items'));
    }

    public function upload(Request $request): Response
    {
        try {
            $this->media->store($request->file('media') ?? [], (int) ($this->auth->user()['id'] ?? 0));
            Flash::put('success', 'Media uploaded.');
        } catch (Throwable $e) {
            Flash::put('error', $e->getMessage());
        }
        return Response::redirect('/admin/media');
    }

    public function delete(string $id): Response
    {
        $this->media->delete((int) $id);
        Flash::put('success', 'Media deleted.');
        return Response::redirect('/admin/media');
    }
}
