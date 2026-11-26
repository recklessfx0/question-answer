<?php

declare(strict_types=1);

namespace QuestionAnswer\Contracts\Seo;

/**
 * Renders a published resource as Markdown for agents.
 *
 * Output is generated from the document model, never converted from rendered
 * HTML, so headings, lists, code blocks, tables and nested answers keep their
 * structure. The result is deterministic for the same content revision, so an
 * agent can cache it by ETag.
 *
 * Implementations must drop raw HTML, restrict links to http, https and mailto,
 * strip bidi control characters, and include the front matter described in
 * docs/SEO.md. Draft, private and permission-gated resources must be refused,
 * not rendered with redactions.
 *
 * See docs/SEO.md and ADR 0002 (docs/adr/0002-seo-and-agent-representations.md).
 */
interface MarkdownRepresentation
{
    /**
     * Render one resource as a complete Markdown document, front matter included.
     *
     * @param string $locale Locale tag already validated against the allow-list.
     *
     * @throws \InvalidArgumentException When the type is unknown, the resource is
     *                                   not publicly visible, or the locale is not served.
     */
    public function render(string $resourceType, string $resourceId, string $locale): string;
}
