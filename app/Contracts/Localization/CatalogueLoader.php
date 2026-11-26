<?php

declare(strict_types=1);

namespace QuestionAnswer\Contracts\Localization;

/**
 * Loads one message domain for one locale.
 *
 * Core catalogues are PHP arrays; layers above core (language packs, theme,
 * site overrides) resolve through the same contract, so a caller never needs to
 * know which layer supplied a key. Implementations apply the documented
 * precedence and report a version that changes whenever the effective catalogue
 * changes, so callers can key their cache by locale, domain and version.
 *
 * Loading is lazy and bounded: one locale and one domain per call, no directory
 * scanning, no eager load of every domain.
 *
 * See docs/I18N.md and ADR 0001 (docs/adr/0001-localization.md).
 */
interface CatalogueLoader
{
    /**
     * Effective messages for a locale and domain after precedence is applied.
     *
     * @return array<string, string> Message ID to ICU pattern.
     *
     * @throws \InvalidArgumentException When the locale is not in the allow-list or the domain is unknown.
     */
    public function load(string $locale, string $domain = 'default'): array;

    /**
     * Domains available for a locale.
     *
     * @return list<string>
     */
    public function domains(string $locale): array;

    /**
     * Cache version of the effective catalogue for a locale and domain.
     */
    public function version(string $locale, string $domain = 'default'): string;
}
