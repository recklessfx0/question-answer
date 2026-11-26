<?php

declare(strict_types=1);

namespace QuestionAnswer\Contracts\Content;

/**
 * Single typed block inside a structured DocumentModel (ADR 0002 D1).
 *
 * Supported types include paragraph, heading, list, code, quote, table,
 * image and citation. All representations (HTML, Markdown, JSON-LD, feeds)
 * render directly from blocks rather than scraping HTML.
 *
 * See docs/SEO.md and ADR 0002.
 */
final readonly class ContentBlock
{
    /**
     * @param string               $type Block type identifier (e.g. "paragraph", "code", "heading").
     * @param array<string, mixed> $data Plain-text / structured block payload (no raw untrusted HTML).
     */
    public function __construct(
        public string $type,
        public array $data,
    ) {
        if ($this->type === '') {
            throw new \InvalidArgumentException('ContentBlock type must not be empty.');
        }
    }
}
