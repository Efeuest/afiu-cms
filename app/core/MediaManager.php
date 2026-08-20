<?php

declare(strict_types=1);

namespace AfiuCMS\Core;

use RuntimeException;

final class MediaManager
{
    private const ALLOWED = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    public function __construct(private readonly Config $config, private readonly Database $db) {}

    public function store(array $upload, int $userId): int
    {
        if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file((string) ($upload['tmp_name'] ?? ''))) {
            throw new RuntimeException('Media upload failed.');
        }
        $size = (int) ($upload['size'] ?? 0);
        $max = (int) $this->config->get('uploads.max_bytes', 10 * 1024 * 1024);
        if ($size < 1 || $size > $max) {
            throw new RuntimeException('File is empty or exceeds the configured upload limit.');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file((string) $upload['tmp_name']);
        if (!is_string($mime) || !isset(self::ALLOWED[$mime])) {
            throw new RuntimeException('Only JPEG, PNG, WEBP and GIF images are accepted.');
        }
        $filename = bin2hex(random_bytes(20)) . '.' . self::ALLOWED[$mime];
        $directory = (string) $this->config->get('paths.uploads');
        if (!is_dir($directory)) mkdir($directory, 0775, true);
        $target = rtrim($directory, '/') . '/' . $filename;
        if (!move_uploaded_file((string) $upload['tmp_name'], $target)) {
            throw new RuntimeException('Unable to store uploaded media.');
        }
        $this->db->execute('INSERT INTO media (filename, original_name, mime_type, size_bytes, uploaded_by) VALUES (?, ?, ?, ?, ?)', [
            $filename,
            mb_substr((string) ($upload['name'] ?? 'upload'), 0, 255),
            $mime,
            $size,
            $userId,
        ]);
        return $this->db->insertId();
    }

    public function delete(int $id): void
    {
        $media = $this->db->one('SELECT * FROM media WHERE id = ?', [$id]);
        if (!$media) return;
        $path = rtrim((string) $this->config->get('paths.uploads'), '/') . '/' . $media['filename'];
        if (is_file($path)) @unlink($path);
        $this->db->execute('DELETE FROM media WHERE id = ?', [$id]);
    }

    public function path(array $media): string
    {
        return rtrim((string) $this->config->get('paths.uploads'), '/') . '/' . $media['filename'];
    }
}
