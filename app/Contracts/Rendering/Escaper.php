<?php

declare(strict_types=1);

namespace QuestionAnswer\Contracts\Rendering;

/**
 * Contextual output escaper enforcing sink-specific encoding (SEC-C-02, SEC-C-08).
 *
 * Implementations never substitute one context for another:
 * - html() for HTML text nodes
 * - attr() for quoted HTML attributes
 * - url() allow-listing http, https, mailto and root-relative paths (blocking javascript:/data:)
 * - jsonLd() encoding with JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT
 *   and never JSON_UNESCAPED_SLASHES so </script> breakout is impossible
 *
 * See docs/FRAMEWORK_ARCHITECTURE.md, docs/SEO.md and docs/SECURITY_REQUIREMENTS.md.
 */
interface Escaper
{
    public function html(string $value): string;

    public function attr(string $value): string;

    public function url(string $value): string;

    /**
     * @param array<string, mixed> $graph
     */
    public function jsonLd(array $graph): string;
}
