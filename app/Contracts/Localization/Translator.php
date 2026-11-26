<?php

declare(strict_types=1);

namespace QuestionAnswer\Contracts\Localization;

/**
 * Renders UI messages for a validated locale.
 *
 * The locale argument must already satisfy the installed allow-list; this
 * contract never accepts an unvalidated tag and never builds a path from one.
 * Rendering follows ICU MessageFormat: named placeholders only, with plural and
 * select supported through the platform intl extension.
 *
 * Catalogue values are data. Implementations must not evaluate them, must not
 * return unescaped HTML, and must fail closed on a malformed pattern. Escaping
 * belongs to the template layer, per output context.
 *
 * See docs/I18N.md and ADR 0001 (docs/adr/0001-localization.md).
 */
interface Translator
{
    /**
     * Render one message in the given locale, falling back per the bounded chain.
     *
     * @param array<string, string|int|float> $parameters Named ICU parameters.
     *
     * @throws \InvalidArgumentException When the message ID is unknown or a
     *                                   parameter does not satisfy the pattern.
     */
    public function translate(string $messageId, array $parameters = [], ?string $locale = null): string;

    /**
     * Report whether the message exists in the requested locale or its fallbacks.
     */
    public function has(string $messageId, ?string $locale = null): bool;

    /**
     * Return a translator bound to another validated locale.
     */
    public function withLocale(string $locale): self;
}
