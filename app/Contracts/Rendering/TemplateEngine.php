<?php

declare(strict_types=1);

namespace QuestionAnswer\Contracts\Rendering;

/**
 * Renders deploy-time compiled PHP templates with contextual auto-escaping and extension slots.
 *
 * Request paths execute pre-compiled PHP files from storage/compiled/templates/
 * with zero runtime parsing or directory scanning (ADR 0003 D1).
 *
 * See docs/FRAMEWORK_ARCHITECTURE.md and docs/FRAMEWORK_PERFORMANCE.md.
 */
interface TemplateEngine
{
    /**
     * Render a named template (e.g. "web::question/show") with bound variables.
     *
     * @param array<string, mixed> $context
     *
     * @throws \InvalidArgumentException When the template name is unknown.
     */
    public function render(string $template, array $context = []): string;

    /**
     * Render all extension contributions registered for a named UI slot.
     *
     * @param array<string, mixed> $context
     */
    public function renderSlot(string $slotName, array $context = []): SafeHtml;
}
