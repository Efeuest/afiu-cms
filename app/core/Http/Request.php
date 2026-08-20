<?php

declare(strict_types=1);

namespace AfiuCMS\Core\Http;

final class Request
{
    private array $routeParams = [];

    public function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly array $query,
        private readonly array $body,
        private readonly array $files,
        private readonly array $server
    ) {}

    public static function capture(): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = rawurldecode($path);
        $path = $path === '/' ? '/' : '/' . trim($path, '/');

        $body = $_POST;
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input');
            $decoded = is_string($raw) ? json_decode($raw, true) : null;
            if (is_array($decoded)) {
                $body = $decoded;
            }
        }

        return new self($method, $path, $_GET, $body, $_FILES, $_SERVER);
    }

    public function method(): string { return $this->method; }
    public function path(): string { return $this->path; }
    public function isMethod(string $method): bool { return $this->method === strtoupper($method); }
    public function query(string $key, mixed $default = null): mixed { return $this->query[$key] ?? $default; }
    public function input(string $key, mixed $default = null): mixed { return $this->body[$key] ?? $default; }
    public function all(): array { return $this->body; }
    public function file(string $key): ?array { return isset($this->files[$key]) && is_array($this->files[$key]) ? $this->files[$key] : null; }
    public function server(string $key, mixed $default = null): mixed { return $this->server[$key] ?? $default; }

    public function withRouteParams(array $params): self
    {
        $clone = clone $this;
        $clone->routeParams = $params;
        return $clone;
    }

    public function param(string $key, mixed $default = null): mixed
    {
        return $this->routeParams[$key] ?? $default;
    }
}
