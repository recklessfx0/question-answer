<?php

declare(strict_types=1);

namespace QuestionAnswer\Contracts\Localization;

/**
 * Selects the locale for a request from explicit input and the Accept-Language header.
 *
 * Resolution order is fixed: explicit locale, then the caller-supplied user
 * preference, then a bounded Accept-Language parse, then the site default. The
 * contract takes primitives rather than a request object so it stays usable
 * from web, REST, MCP and CLI without depending on the HTTP contract.
 *
 * Implementations must reject malformed and control-character input, clamp
 * quality values, ignore wildcards, and never return a locale absent from the
 * allow-list. The fallback chain is bounded to exact tag, language-only, then
 * default; it never traverses unrelated locales.
 *
 * See docs/I18N.md and ADR 0001 (docs/adr/0001-localization.md).
 */
interface LocaleResolver
{
    /**
     * Resolve the effective locale for one unit of work.
     *
     * @param list<string> $available Installed and enabled locale tags.
     * @param string       $default   Site default; must be present in $available.
     *
     * @throws \InvalidArgumentException When $default is not in $available or $available is empty.
     */
    public function resolve(
        ?string $explicitLocale,
        ?string $acceptLanguage,
        array $available,
        string $default,
    ): string;

    /**
     * Ordered fallback chain for a locale, ending at the default locale.
     *
     * The tag itself is not included unless it is available; the result is never
     * empty because it always terminates at the default.
     *
     * @param list<string> $available Installed and enabled locale tags.
     * @param string       $default   Site default; must be present in $available.
     *
     * @return list<string>
     *
     * @throws \InvalidArgumentException When $default is not in $available or $available is empty.
     */
    public function fallbackChain(string $locale, array $available, string $default): array;
}
