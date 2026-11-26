<?php

declare(strict_types=1);

namespace QuestionAnswer\Contracts\Logging;

/**
 * Structured application logger with centralized secret redaction and request correlation.
 *
 * Mirrors PSR-3 log levels. Implementations automatically apply Redactor to
 * message context before writing JSON log entries (SEC-D-01).
 *
 * See docs/FRAMEWORK_ARCHITECTURE.md and docs/SECURITY_REQUIREMENTS.md (SEC-D-01).
 */
interface Logger
{
    /**
     * @param array<string, mixed> $context
     */
    public function log(string $level, string $message, array $context = []): void;

    /**
     * @param array<string, mixed> $context
     */
    public function info(string $message, array $context = []): void;

    /**
     * @param array<string, mixed> $context
     */
    public function warning(string $message, array $context = []): void;

    /**
     * @param array<string, mixed> $context
     */
    public function error(string $message, array $context = []): void;

    /**
     * @param array<string, mixed> $context
     */
    public function critical(string $message, array $context = []): void;
}
