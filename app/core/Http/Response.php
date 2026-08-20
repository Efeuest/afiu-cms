<?php

declare(strict_types=1);

namespace AfiuCMS\Core\Http;

use Closure;
use JsonException;

final class Response
{
    public function __construct(
        private readonly string|Closure $body = '',
        private readonly int $status = 200,
        private array $headers = []
    ) {}

    public static function html(string $body, int $status = 200): self
    {
        return new self($body, $status, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    /** @throws JsonException */
    public static function json(array $data, int $status = 200): self
    {
        return new self(
            json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            $status,
            ['Content-Type' => 'application/json; charset=UTF-8']
        );
    }

    public static function redirect(string $location, int $status = 302): self
    {
        return new self('', $status, ['Location' => $location]);
    }

    public static function file(string $path, string $contentType, ?string $downloadName = null): self
    {
        $headers = [
            'Content-Type' => $contentType,
            'Content-Length' => (string) filesize($path),
            'X-Content-Type-Options' => 'nosniff',
        ];
        if ($downloadName !== null) {
            $headers['Content-Disposition'] = 'attachment; filename="' . addslashes($downloadName) . '"';
        }
        return new self(static fn () => readfile($path), 200, $headers);
    }

    public function withHeader(string $name, string $value): self
    {
        $clone = clone $this;
        $clone->headers[$name] = $value;
        return $clone;
    }

    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }
        if ($this->body instanceof Closure) {
            ($this->body)();
        } else {
            echo $this->body;
        }
    }
}
