<?php

declare(strict_types=1);

namespace QuestionAnswer\Contracts\Security;

/**
 * Fixed/sliding window rate limiter for authentication, MFA, mail, API and artifact endpoints.
 *
 * See docs/SECURITY_REQUIREMENTS.md (SEC-A-04, SEC-C-09) and docs/MAIL.md.
 */
interface RateLimiter
{
    /**
     * Consume one unit for $key under $maxAttempts per $windowSeconds.
     *
     * @return array{allowed: bool, remaining: int, retryAfterSeconds: int}
     */
    public function hit(string $key, int $maxAttempts, int $windowSeconds): array;

    /**
     * Clear counters for $key (e.g. after a verified login).
     */
    public function reset(string $key): void;
}
