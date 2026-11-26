<?php

declare(strict_types=1);

namespace QuestionAnswer\Contracts\Cache;

/**
 * Tag-indexed cache extension enabling surgical invalidation without global flushes.
 *
 * See docs/FRAMEWORK_PERFORMANCE.md (section 7-8) and ADR 0003 (D4, D5).
 */
interface TaggedCache extends CacheStore
{
    /**
     * Store a value associated with one or more coarse invalidation tags.
     *
     * @param list<string> $tags e.g. ["question:42", "topic:7", "locale:de"]
     */
    public function setWithTags(string $key, mixed $value, int $ttlSeconds, array $tags, int $staleSeconds = 0): bool;

    /**
     * Invalidate all keys indexed under the given tags.
     *
     * @param list<string> $tags
     *
     * @return int Number of invalidated keys.
     */
    public function invalidateTags(array $tags): int;
}
