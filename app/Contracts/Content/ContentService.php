<?php

declare(strict_types=1);

namespace QuestionAnswer\Contracts\Content;

use QuestionAnswer\Contracts\Auth\Actor;

/**
 * Shared read boundary for Q&A content projections consumed by Web, SEO, Feeds,
 * Markdown, REST and MCP so authorization and visibility never diverge (SEC-D-04).
 *
 * See docs/FRAMEWORK_ARCHITECTURE.md, docs/SEO.md and ADR 0003 (D3).
 */
interface ContentService
{
    /**
     * Load a read projection authorized for $actor in a validated $locale, or null if absent/hidden.
     */
    public function findProjection(
        string $resourceType,
        string $resourceId,
        string $locale,
        Actor $actor,
    ): ?ReadProjection;

    /**
     * Load a publicly visible read projection by per-locale slug, or null if not public.
     */
    public function findPublicBySlug(
        string $resourceType,
        string $slug,
        string $locale,
    ): ?ReadProjection;
}
