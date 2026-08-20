<?php

declare(strict_types=1);

namespace AfiuCMS\Core;

use RuntimeException;
use ZipArchive;

final class ThemeManager
{
    public function __construct(private readonly Config $config, private readonly Settings $settings) {}

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
        $theme = $this->manifest($slug);
        if ($theme === null) {
            throw new RuntimeException('Theme not found or invalid.');
        }
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

    public function asset(string $slug, string $path): array
    {
        if ($this->manifest($slug) === null) {
            throw new RuntimeException('Theme not found.');
        }
        $assetsRoot = realpath($this->themePath($slug) . '/assets');
        $file = realpath($this->themePath($slug) . '/assets/' . ltrim($path, '/'));
        if ($assetsRoot === false || $file === false || !str_starts_with($file, $assetsRoot . DIRECTORY_SEPARATOR) || !is_file($file)) {
            throw new RuntimeException('Theme asset not found.');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file) ?: 'application/octet-stream';
        return [$file, $mime];
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
        return is_array($data) && ($data['slug'] ?? null) === $slug && isset($data['name'], $data['version']) ? $data : null;
    }

    private function themePath(string $slug): string
    {
        return rtrim((string) $this->config->get('paths.themes'), '/') . '/' . $slug;
    }

    private function deleteTree(string $path): void
    {
        if (!is_dir($path)) return;
        $items = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($path);
    }
}
