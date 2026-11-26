<?php

declare(strict_types=1);

namespace QuestionAnswer\Contracts\Cache;

/**
 * Base key-value cache store (PSR-16-aligned) backed by atomic file cache by
 * default, with optional APCu or Redis adapters on dedicated hosts.
 *
 * Keys must include every variance dimension (locale, theme, visibility,
 * representation, format version). Private authenticated content must never
 * enter shared caches (ADR 0003 D4, SEC-D-04).
 *
 * See docs/FRAMEWORK_PERFORMANCE.md and ADR 0003.
 */
interface CacheStore
{
    public function get(string $key, mixed $default = null): mixed;

    public function set(string $key, mixed $value, int $ttlSeconds): bool;

    public function delete(string $key): bool;

    public function has(string $key): bool;
}
