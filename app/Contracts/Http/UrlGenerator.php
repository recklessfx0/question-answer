<?php

declare(strict_types=1);

namespace QuestionAnswer\Contracts\Http;

/**
 * Generates canonical relative and absolute URLs from compiled named routes.
 *
 * Single source of truth for HTML links, canonicals, hreflang alternates,
 * sitemaps, Atom/JSON feeds and Markdown (.md) representations so URLs never drift.
 *
 * See docs/FRAMEWORK_ARCHITECTURE.md, docs/I18N.md and docs/SEO.md.
 */
interface UrlGenerator
{
    /**
     * Generate a root-relative path for a named route and validated parameters.
     *
     * @param array<string, string|int> $parameters
     * @param array<string, string|int> $query
     *
     * @throws \InvalidArgumentException When the route name is unknown or required parameters are missing.
     */
    public function path(string $routeName, array $parameters = [], array $query = []): string;

    /**
     * Generate an absolute canonical URL for a named route.
     *
     * @param array<string, string|int> $parameters
     * @param array<string, string|int> $query
     *
     * @throws \InvalidArgumentException When the route name is unknown or required parameters are missing.
     */
    public function absolute(string $routeName, array $parameters = [], array $query = []): string;
}
