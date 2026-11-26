<?php

declare(strict_types=1);

namespace QuestionAnswer\Contracts\Http;

/**
 * Single stage in the deterministic compiled HTTP middleware pipeline.
 *
 * See docs/FRAMEWORK_ARCHITECTURE.md and ADR 0004 (D4).
 */
interface Middleware
{
    /**
     * Process an incoming request, optionally delegating to $next.
     *
     * @param callable(Request): Response $next
     */
    public function process(Request $request, callable $next): Response;
}
