<?php

declare(strict_types=1);

namespace QuestionAnswer\Contracts\Http;

/**
 * Immutable HTTP request value object passed through the middleware pipeline.
 *
 * Header lookups are case-insensitive. Implementations of RequestLimitsMiddleware
 * cap header counts, URI length and body bytes before constructing or parsing.
 *
 * See docs/FRAMEWORK_ARCHITECTURE.md and ADR 0004.
 */
final readonly class Request
{
    /**
     * @param string                             $method     Uppercase HTTP verb (GET, POST, etc.).
     * @param string                             $path       Normalized URL path starting with "/".
     * @param array<string, string|list<string>> $query      Query string parameters.
     * @param array<string, string>              $headers    Lowercased header names to values.
     * @param array<string, string>              $cookies    Cookie name to value map.
     * @param string                             $body       Raw request body bytes (size-bounded).
     * @param array<string, mixed>               $attributes Pipeline-attached attributes (requestId, locale, actor, route).
     * @param string                             $scheme     "https" or "http".
     * @param string                             $host       Validated Host header value.
     * @param string                             $clientIp   Resolved client IP address.
     */
    public function __construct(
        public string $method,
        public string $path,
        public array $query = [],
        public array $headers = [],
        public array $cookies = [],
        public string $body = '',
        public array $attributes = [],
        public string $scheme = 'https',
        public string $host = 'localhost',
        public string $clientIp = '127.0.0.1',
    ) {
        if ($this->method === '' || $this->path === '' || $this->path[0] !== '/') {
            throw new \InvalidArgumentException('HTTP request requires a non-empty method and root-relative path.');
        }
    }

    public function header(string $name, ?string $default = null): ?string
    {
        return $this->headers[strtolower($name)] ?? $default;
    }

    public function attribute(string $name, mixed $default = null): mixed
    {
        return $this->attributes[$name] ?? $default;
    }

    public function withAttribute(string $name, mixed $value): self
    {
        $attributes = $this->attributes;
        $attributes[$name] = $value;

        return new self(
            method: $this->method,
            path: $this->path,
            query: $this->query,
            headers: $this->headers,
            cookies: $this->cookies,
            body: $this->body,
            attributes: $attributes,
            scheme: $this->scheme,
            host: $this->host,
            clientIp: $this->clientIp,
        );
    }
}
