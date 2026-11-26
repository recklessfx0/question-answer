<?php

declare(strict_types=1);

namespace QuestionAnswer\Contracts\Content;

/**
 * Canonical structured document model shared by HTML, Markdown, JSON-LD, feeds and sitemaps.
 *
 * See docs/SEO.md, docs/I18N.md and ADR 0002 (D1).
 */
final readonly class DocumentModel
{
    /**
     * @param string             $resourceType  e.g. "question", "answer", "clarification", "page"
     * @param string             $resourceId    Stable content node ID.
     * @param string             $locale        Validated BCP 47 locale tag.
     * @param string             $slug          Immutable per-locale URL slug.
     * @param string             $title         Plain-text title (bidi-stripped).
     * @param list<ContentBlock> $blocks        Ordered structured content blocks.
     * @param Visibility         $visibility    Visibility state gating public artifacts (SEC-D-04).
     * @param string             $revisionHash  Content revision hash driving cache keys and ETags.
     * @param string             $updatedAt     UTC RFC 3339 revision timestamp.
     * @param string             $authorName    Public display name of the author.
     * @param list<string>       $sources       Verified citation URLs / references.
     */
    public function __construct(
        public string $resourceType,
        public string $resourceId,
        public string $locale,
        public string $slug,
        public string $title,
        public array $blocks,
        public Visibility $visibility,
        public string $revisionHash,
        public string $updatedAt,
        public string $authorName,
        public array $sources = [],
    ) {
    }
}
