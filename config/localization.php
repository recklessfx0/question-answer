<?php

declare(strict_types=1);

/**
 * Localization baseline (ADR 0001, I18N.md).
 * Default locale is unprefixed; non-default enabled locales are path-prefixed.
 */
return [
    'default_locale' => 'en',
    'enabled_locales' => ['en'],
    'prefix_default_locale' => false,
    'max_accept_language_ranges' => 20,
    'domains' => ['default', 'admin', 'validation', 'mail', 'installer', 'mcp'],
];
