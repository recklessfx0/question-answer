<?php

declare(strict_types=1);

namespace QuestionAnswer\Contracts\Http;

/**
 * Immutable HTTP response value object.
 *
 * Rejects CR/LF control characters in header names and values to prevent
 * response splitting (SEC-C-06, SEC-C-07).
 *
 * See docs/FRAMEWORK_ARCHITECTURE.md and ADR 0004.
 */
final readonly class Response
{
    /**
     * @param int                                $status  HTTP status code (100–599).
     * @param string                             $body    Response body bytes.
     * @param array<string, string|list<string>> $headers Header name to value(s).
     */
    public function __construct(
        public int $status = 200,
        public string $body = '',
        public array $headers = [],
    ) {
        if ($this->status < 100 || $this->status > 599) {
            throw new \InvalidArgumentException('HTTP status must be between 100 and 599.');
        }

        foreach ($this->headers as $name => $values) {
            self::assertNoControlBytes($name, 'header name');
            foreach (is_array($values) ? $values : [$values] as $value) {
                self::assertNoControlBytes($value, "header {$name}");
            }
        }
    }

    public function withHeader(string $name, string $value): self
    {
        $headers = $this->headers;
        $headers[$name] = $value;

        return new self($this->status, $this->body, $headers);
    }

    public function withAddedHeader(string $name, string $value): self
    {
        $headers = $this->headers;
        if (!isset($headers[$name])) {
            $headers[$name] = $value;
        } elseif (is_array($headers[$name])) {
            $headers[$name][] = $value;
        } else {
            $headers[$name] = [$headers[$name], $value];
        }

        return new self($this->status, $this->body, $headers);
    }

    private static function assertNoControlBytes(string $value, string $field): void
    {
        if (preg_match('/[\r\n\x00]/', $value) === 1) {
            throw new \InvalidArgumentException("HTTP {$field} contains disallowed control characters.");
        }
    }
}
