<?php

declare(strict_types=1);

namespace QuestionAnswer\Contracts\Seo;

/**
 * Builds the schema.org JSON-LD graph for a public resource.
 *
 * The graph describes what the page visibly shows. Implementations must not
 * invent ratings, dates or authors, must not include hidden content, and must
 * emit localized names from the requested locale record. Values are plain text:
 * no HTML, no pre-escaped entities.
 *
 * The returned array is serialized by the framework. Implementations must not
 * serialize it themselves and must not place unencoded user text anywhere the
 * caller might concatenate into a script element: the framework owns the
 * encoding boundary that prevents a JSON-LD breakout.
 *
 * See docs/SEO.md and ADR 0002 (docs/adr/0002-seo-and-agent-representations.md).
 */
interface StructuredData
{
    /**
     * Resource types a graph may be requested for: question, topic, user, page.
     */
    public const TYPE_QUESTION = 'question';
    public const TYPE_TOPIC = 'topic';
    public const TYPE_USER = 'user';
    public const TYPE_PAGE = 'page';

    /**
     * Build the JSON-LD graph for one resource in one locale.
     *
     * The root node carries a stable `@id` derived from the canonical URL, so a
     * page and its feed entry describe the same entity.
     *
     * @param string $locale Locale tag already validated against the allow-list.
     *
     * @return array<string, mixed> A JSON-serializable graph node or `@graph` container.
     *
     * @throws \InvalidArgumentException When the type is unknown, the resource is
     *                                   not publicly visible, or the locale is not served.
     */
    public function graph(string $resourceType, string $resourceId, string $locale): array;
}
