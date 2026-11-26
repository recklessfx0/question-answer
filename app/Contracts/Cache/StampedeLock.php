<?php

declare(strict_types=1);

namespace QuestionAnswer\Contracts\Cache;

/**
 * Single-flight lock protecting cold keys against cache stampedes (CACHE-004).
 *
 * Locks use short jittered TTLs so a crashed worker never deadlocks a key.
 *
 * See docs/FRAMEWORK_PERFORMANCE.md (section 9) and ADR 0003 (D6).
 */
interface StampedeLock
{
    /**
     * Attempt to acquire a non-blocking lock for $key with a bounded TTL.
     *
     * @return string|null Opaque lock token on acquisition, or null if already held.
     */
    public function acquire(string $key, int $ttlSeconds = 10): ?string;

    /**
     * Release a previously acquired lock only if the token still matches.
     */
    public function release(string $key, string $token): void;
}
