<?php

declare(strict_types=1);

namespace AfiuCMS\Controllers;

use AfiuCMS\Core\Database;
use AfiuCMS\Core\Http\Response;
use AfiuCMS\Core\MediaManager;
use AfiuCMS\Core\ThemeManager;
use RuntimeException;

final class AssetController
{
    public function __construct(private readonly ThemeManager $themes, private readonly Database $db, private readonly MediaManager $media) {}

    public function theme(string $theme, string $path): Response
    {
        try {
            [$file, $mime] = $this->themes->asset($theme, $path);
            return Response::file($file, $mime)->withHeader('Cache-Control', 'public, max-age=86400');
        } catch (RuntimeException) {
            return Response::html('Not found', 404);
        }
    }

    public function media(string $id): Response
    {
        $item = $this->db->one('SELECT * FROM media WHERE id=?', [(int) $id]);
        if (!$item) return Response::html('Not found', 404);
        $path = $this->media->path($item);
        return is_file($path) ? Response::file($path, (string) $item['mime_type'])->withHeader('Cache-Control', 'public, max-age=86400') : Response::html('Not found', 404);
    }
}
