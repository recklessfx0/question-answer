<?php

declare(strict_types=1);

namespace QuestionAnswer\Contracts\Seo;

/**
 * Supplies sitemap entries for one shard of one locale.
 *
 * Implementations must return only canonical, indexable, published URLs that the
 * requesting locale can serve, and must anchor `lastmod` to the content revision
 * rather than the generation time. Nothing private, drafted or permission-gated
 * may appear: the same authorization filter used for HTML applies here.
 *
 * Reads are keyset-paginated and bounded so a large corpus streams into an
 * XMLWriter without loading it. Implementations must not sort or scan the whole
 * table per call.
 *
 * See docs/SEO.md and ADR 0002 (docs/adr/0002-seo-and-agent-representations.md).
 */
interface SitemapSource
{
    /**
     * Shard name used in the sitemap URL, for example "questions".
     */
    public function name(): string;

    /**
     * One page of entries after a cursor, ordered stably by the cursor key.
     *
     * The cursor is opaque to the caller and comes from the previous page; null
     * starts at the beginning. An empty result marks the end of the shard.
     *
     * @param string $locale Locale tag already validated against the allow-list.
     *
     * @return iterable<int, array{loc: string, lastmod: string}> Absolute URL and RFC 3339 timestamp.
     */
    public function entries(string $locale, ?string $cursor, int $limit): iterable;

    /**
     * Newest revision timestamp in the shard, used as the sitemap index lastmod.
     *
     * Returns null when the shard is empty for the locale.
     */
    public function lastModified(string $locale): ?string;
}
