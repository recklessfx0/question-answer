<?php

declare(strict_types=1);

namespace QuestionAnswer\Contracts\Logging;

/**
 * Redacts credentials, tokens, session IDs and sensitive payloads centrally
 * inside the logging and error-reporting pipeline.
 *
 * See docs/SECURITY_REQUIREMENTS.md (SEC-D-01) and ADR 0004 (D7).
 */
interface Redactor
{
    /**
     * Register a known runtime secret value (e.g. DB password, app key, SMTP password)
     * so exact occurrences are scrubbed from messages and context.
     */
    public function registerSecret(string $secret): void;

    /**
     * Return a redacted copy of structured log context.
     *
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    public function redactContext(array $context): array;

    /**
     * Return a redacted string with any registered secrets or bearer/cookie patterns masked.
     */
    public function redactString(string $input): string;
}
