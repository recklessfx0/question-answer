<?php

declare(strict_types=1);

namespace QuestionAnswer\Contracts\Content;

/**
 * Canonical content visibility levels shared across HTML, Markdown, JSON-LD,
 * feeds, sitemaps, search and cache keys (SEC-D-04).
 *
 * Only PUBLIC content may enter shared caches, sitemaps, feeds, JSON-LD,
 * Markdown (.md) responses or llms.txt.
 *
 * See docs/SEO.md, docs/FRAMEWORK_PERFORMANCE.md and docs/SECURITY_REQUIREMENTS.md.
 */
enum Visibility: string
{
    case PUBLIC = 'public';
    case DRAFT = 'draft';
    case PRIVATE = 'private';
    case MODERATED = 'moderated';
    case DELETED = 'deleted';

    public function isPublic(): bool
    {
        return $this === self::PUBLIC;
    }
}
