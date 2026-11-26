<?php

declare(strict_types=1);

namespace QuestionAnswer\Contracts\Rendering;

/**
 * Typed wrapper marking HTML that was produced by an audited sanitizer or compiled renderer.
 *
 * Raw HTML cannot be interpolated in templates without passing a SafeHtml value
 * (SEC-C-02). User input must never be wrapped directly without sanitization.
 *
 * See docs/FRAMEWORK_ARCHITECTURE.md and docs/SECURITY_REQUIREMENTS.md (SEC-C-02).
 */
final readonly class SafeHtml
{
    private function __construct(
        private string $html,
    ) {
    }

    /**
     * Construct a SafeHtml value from already-sanitized or compiler-emitted markup.
     */
    public static function fromSanitized(string $sanitizedHtml): self
    {
        return new self($sanitizedHtml);
    }

    public function toString(): string
    {
        return $this->html;
    }

    public function __toString(): string
    {
        return $this->html;
    }
}
