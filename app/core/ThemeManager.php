<?php

declare(strict_types=1);

namespace AfiuCMS\Core;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use ZipArchive;

final class ThemeManager
{
    private const PUBLIC_ASSET_EXTENSIONS = [
        'css', 'js', 'mjs',
        'png', 'jpg', 'jpeg', 'gif', 'webp', 'avif', 'svg', 'ico',
        'woff', 'woff2', 'ttf', 'otf',
        'mp4', 'webm',
    ];

    public function __construct(
        private readonly Config $config,
        private readonly Settings $settings
    ) {}

    public function activeSlug(): string
    {
        return (string) $this->settings->get('active_theme', 'afiu-default');
    }

    public function themes(): array
    {
        $items = [];
        foreach (glob(rtrim((string) $this->config->get('paths.themes'), '/') . '/*/theme.json') ?: [] as $manifest) {
            $data = json_decode((string) file_get_contents($manifest), true);
            if (!is_array($data) || !isset($data['slug'], $data['name'])) {
                continue;
            }
            $data['path'] = dirname($manifest);
            $data['active'] = $data['slug'] === $this->activeSlug();
            $items[] = $data;
        }

        usort($items, fn (array $a, array $b): int => strcasecmp((string) $a['name'], (string) $b['name']));
        return $items;
    }

    public function activate(string $slug): void
    {
        if ($this->manifest($slug) === null) {
            throw new RuntimeException('Theme not found or invalid.');
        }

        $this->publishAssets($slug);
        $this->settings->set('active_theme', $slug);
    }

    public function render(string $view, array $data = []): string
    {
        $slug = $this->activeSlug();
        $root = $this->themePath($slug);
        $viewFile = $root . '/views/' . $view . '.php';

        if (!is_file($viewFile)) {
            $viewFile = $root . '/views/404.php';
        }
        if (!is_file($viewFile)) {
            throw new RuntimeException("Theme view not found: {$view}");
        }

        extract($data + ['themeSlug' => $slug], EXTR_SKIP);
        ob_start();
        require $viewFile;
        $content = (string) ob_get_clean();

        $layout = $root . '/views/layout.php';
        if (!is_file($layout)) {
            return $content;
        }

        ob_start();
        require $layout;
        return (string) ob_get_clean();
    }

    /**
     * Dynamic fallback for installations that have not yet published theme assets.
     */
    public function asset(string $slug, string $path): array
    {
        if ($this->manifest($slug) === null) {
            throw new RuntimeException('Theme not found.');
        }

        $assetsRoot = realpath($this->themePath($slug) . '/assets');
        $file = realpath($this->themePath($slug) . '/assets/' . ltrim($path, '/'));

        if (
            $assetsRoot === false
            || $file === false
            || !str_starts_with($file, $assetsRoot . DIRECTORY_SEPARATOR)
            || !is_file($file)
        ) {
            throw new RuntimeException('Theme asset not found.');
        }

        $extension = strtolower((string) pathinfo($file, PATHINFO_EXTENSION));
        if (!in_array($extension, self::PUBLIC_ASSET_EXTENSIONS, true)) {
            throw new RuntimeException('Theme asset type is not allowed.');
        }

        $mime = class_exists(\finfo::class)
            ? ((new \finfo(FILEINFO_MIME_TYPE))->file($file) ?: $this->fallbackMime($extension))
            : $this->fallbackMime($extension);

        return [$file, $mime];
    }

    /**
     * Copies a theme's static assets into public/theme-assets/<slug>.
     * PHP and other executable/server configuration files are intentionally excluded.
     */
    public function publishAssets(string $slug): void
    {
        if ($this->manifest($slug) === null) {
            throw new RuntimeException('Theme not found or invalid.');
        }

        $source = $this->themePath($slug) . '/assets';
        if (!is_dir($source)) {
            throw new RuntimeException('Theme assets directory is missing.');
        }

        $destinationRoot = rtrim((string) $this->config->get('paths.public_theme_assets'), '/');
        if ($destinationRoot === '') {
            throw new RuntimeException('Public theme asset path is not configured.');
        }

        $destination = $destinationRoot . '/' . $slug;
        $this->deleteTree($destination);

        if (!mkdir($destination, 0775, true) && !is_dir($destination)) {
            throw new RuntimeException('Unable to create public theme asset directory.');
        }

        $sourceReal = realpath($source);
        if ($sourceReal === false) {
            throw new RuntimeException('Unable to resolve theme assets directory.');
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceReal, \FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $relative = substr($item->getPathname(), strlen($sourceReal) + 1);
            if ($relative === false || $relative === '') {
                continue;
            }

            $target = $destination . '/' . $relative;

            if ($item->isDir()) {
                if (!is_dir($target) && !mkdir($target, 0775, true) && !is_dir($target)) {
                    throw new RuntimeException('Unable to create public theme asset directory.');
                }
                continue;
            }

            if (!$item->isFile() || $item->isLink()) {
                continue;
            }

            $extension = strtolower((string) pathinfo($item->getFilename(), PATHINFO_EXTENSION));
            if (!in_array($extension, self::PUBLIC_ASSET_EXTENSIONS, true)) {
                continue;
            }

            $targetDirectory = dirname($target);
            if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0775, true) && !is_dir($targetDirectory)) {
                throw new RuntimeException('Unable to create public theme asset directory.');
            }

            if (!copy($item->getPathname(), $target)) {
                throw new RuntimeException('Unable to publish theme asset: ' . $relative);
            }
        }
    }

    public function ensurePublished(string $slug): void
    {
        $public = rtrim((string) $this->config->get('paths.public_theme_assets'), '/') . '/' . $slug;
        $source = $this->themePath($slug) . '/assets';

        if (!is_dir($public) || !is_file($public . '/style.css')) {
            if (is_dir($source)) {
                $this->publishAssets($slug);
            }
        }
    }

    public function installZip(array $upload): string
    {
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('PHP Zip extension is required for theme installation.');
        }
        if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file((string) ($upload['tmp_name'] ?? ''))) {
            throw new RuntimeException('Theme upload failed.');
        }
        if ((int) ($upload['size'] ?? 0) > 25 * 1024 * 1024) {
            throw new RuntimeException('Theme package may not exceed 25 MB.');
        }

        $zip = new ZipArchive();
        if ($zip->open((string) $upload['tmp_name']) !== true) {
            throw new RuntimeException('Invalid ZIP archive.');
        }

        $total = 0;
        if ($zip->numFiles > 2000) {
            $zip->close();
            throw new RuntimeException('Theme archive contains too many files.');
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $name = (string) ($stat['name'] ?? '');
            $total += (int) ($stat['size'] ?? 0);

            if ($name === '' || str_contains($name, "\0") || str_contains($name, '\\') || str_starts_with($name, '/') || preg_match('#(^|/)\.\.(/|$)#', $name)) {
                $zip->close();
                throw new RuntimeException('Theme ZIP contains an unsafe path.');
            }

            $opsys = 0;
            $attributes = 0;
            if ($zip->getExternalAttributesIndex($i, $opsys, $attributes) && $opsys === ZipArchive::OPSYS_UNIX) {
                $mode = ($attributes >> 16) & 0170000;
                if ($mode === 0120000) {
                    $zip->close();
                    throw new RuntimeException('Theme ZIP may not contain symbolic links.');
                }
            }
        }

        if ($total > 60 * 1024 * 1024) {
            $zip->close();
            throw new RuntimeException('Uncompressed theme package is too large.');
        }

        $manifestRaw = $zip->getFromName('theme.json');
        if (!is_string($manifestRaw)) {
            $zip->close();
            throw new RuntimeException('theme.json must be located at the root of the ZIP.');
        }

        $manifest = json_decode($manifestRaw, true);
        $slug = is_array($manifest) ? (string) ($manifest['slug'] ?? '') : '';
        if (!preg_match('/^[a-z0-9][a-z0-9-]{1,62}$/', $slug) || empty($manifest['name']) || empty($manifest['version'])) {
            $zip->close();
            throw new RuntimeException('theme.json is missing a valid slug, name or version.');
        }
        if ($slug === 'afiu-default') {
            $zip->close();
            throw new RuntimeException('The bundled afiu-default theme cannot be replaced from the admin panel.');
        }

        $destination = rtrim((string) $this->config->get('paths.themes'), '/') . '/' . $slug;
        if (is_dir($destination)) {
            $zip->close();
            throw new RuntimeException('A theme with this slug is already installed.');
        }
        if (!mkdir($destination, 0775, true) && !is_dir($destination)) {
            $zip->close();
            throw new RuntimeException('Unable to create theme directory.');
        }
        if (!$zip->extractTo($destination)) {
            $zip->close();
            $this->deleteTree($destination);
            throw new RuntimeException('Unable to extract theme package.');
        }
        $zip->close();

        if ($this->manifest($slug) === null) {
            $this->deleteTree($destination);
            throw new RuntimeException('Installed theme manifest is invalid.');
        }

        // Publish only safe static assets. Theme PHP views remain outside public/.
        $this->publishAssets($slug);
        return $slug;
    }

    public function manifest(string $slug): ?array
    {
        if (!preg_match('/^[a-z0-9][a-z0-9-]{1,62}$/', $slug)) {
            return null;
        }

        $file = $this->themePath($slug) . '/theme.json';
        if (!is_file($file)) {
            return null;
        }

        $data = json_decode((string) file_get_contents($file), true);
        return is_array($data)
            && ($data['slug'] ?? null) === $slug
            && isset($data['name'], $data['version'])
            ? $data
            : null;
    }

    private function themePath(string $slug): string
    {
        return rtrim((string) $this->config->get('paths.themes'), '/') . '/' . $slug;
    }

    private function fallbackMime(string $extension): string
    {
        return match ($extension) {
            'css' => 'text/css; charset=UTF-8',
            'js', 'mjs' => 'text/javascript; charset=UTF-8',
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'avif' => 'image/avif',
            'ico' => 'image/x-icon',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'otf' => 'font/otf',
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            default => 'application/octet-stream',
        };
    }

    private function deleteTree(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($path);
    }
}
