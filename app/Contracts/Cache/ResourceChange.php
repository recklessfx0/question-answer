<?php

declare(strict_types=1);

namespace QuestionAnswer\Contracts\Cache;

/**
 * Immutable resource mutation descriptor sent to InvalidationBus on publish or hide.
 *
 * When $becameNonPublic is true (e.g. published -> hidden/deleted/moderated),
 * cache invalidation must run synchronously without serving stale content (SEC-D-04).
 *
 * See docs/FRAMEWORK_PERFORMANCE.md (section 8) and ADR 0003 (D5).
 */
final readonly class ResourceChange
{
    /**
     * @param string       $resourceType    e.g. "question", "answer", "topic", "user"
     * @param string       $resourceId      Stable resource identifier.
     * @param list<string> $locales         Affected validated locale tags.
     * @param list<string> $changedFields   Changed logical fields (e.g. ["title", "body", "status"]).
     * @param bool         $becameNonPublic True when visibility narrowed away from public.
     */
    public function __construct(
        public string $resourceType,
        public string $resourceId,
        public array $locales,
        public array $changedFields = [],
        public bool $becameNonPublic = false,
    ) {
        if ($this->resourceType === '' || $this->resourceId === '') {
            throw new \InvalidArgumentException('ResourceChange requires resourceType and resourceId.');
        }
    }
}
