<?php

declare(strict_types=1);

namespace AfiuCMS\Core;

use RuntimeException;

final class View
{
    public function __construct(private readonly Config $config) {}

    public function render(string $view, array $data = [], ?string $layout = null): string
    {
        $base = (string) $this->config->get('paths.views');
        $file = $base . '/' . str_replace('.', '/', $view) . '.php';
        if (!is_file($file)) {
            throw new RuntimeException("View not found: {$view}");
        }
        $content = $this->include($file, $data);
        if ($layout === null) {
            return $content;
        }
        $layoutFile = $base . '/' . str_replace('.', '/', $layout) . '.php';
        if (!is_file($layoutFile)) {
            throw new RuntimeException("Layout not found: {$layout}");
        }
        return $this->include($layoutFile, $data + ['content' => $content]);
    }

    private function include(string $file, array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        return (string) ob_get_clean();
    }
}
