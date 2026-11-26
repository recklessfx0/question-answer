<?php

declare(strict_types=1);

namespace QuestionAnswer\Contracts\Content;

/**
 * Cached, serializable read-model projection assembled once per resource and locale
 * and shared across HTML, Markdown, JSON-LD, feeds and sitemaps (ADR 0003 D3, PERF-004).
 *
 * Cache key MUST incorporate resourceType, resourceId, locale, revisionHash and visibility.
 *
 * See docs/FRAMEWORK_PERFORMANCE.md (section 6) and ADR 0003.
 */
final readonly class ReadProjection
{
    /**
     * @param DocumentModel        $document        Canonical structured document.
     * @param list<string>         $publishedLocales Published sibling locales for hreflang reciprocity.
     * @param array<string, mixed> $metadata        Topic, vote counts, accepted answer ID, breadcrumbs, etc.
     */
    public function __construct(
        public DocumentModel $document,
        public array $publishedLocales = [],
        public array $metadata = [],
    ) {
    }

    public function cacheKey(int $schemaVersion = 1): string
    {
        return sprintf(
            'proj:v%d:%s:%s:%s:%s:%s',
            $schemaVersion,
            $this->document->resourceType,
            $this->document->resourceId,
            $this->document->locale,
            $this->document->revisionHash,
            $this->document->visibility->value,
        );
    }
}
