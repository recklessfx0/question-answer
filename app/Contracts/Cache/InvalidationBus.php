<?php

declare(strict_types=1);

namespace QuestionAnswer\Contracts\Cache;

/**
 * Central invalidation bus mapping a ResourceChange to affected cache tags and
 * artifact regeneration jobs (pages, fragments, projections, sitemaps, feeds,
 * llms.txt, Markdown and social cards).
 *
 * See docs/FRAMEWORK_PERFORMANCE.md (section 8) and ADR 0003 (D5).
 */
interface InvalidationBus
{
    public function publish(ResourceChange $change): void;
}
