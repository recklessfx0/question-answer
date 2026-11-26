<?php

declare(strict_types=1);

namespace QuestionAnswer\Contracts\Http;

/**
 * Matches requests against a deploy-time compiled static route table and segment tree.
 *
 * Implementations never compile regexes or scan controllers on the request path.
 *
 * See docs/FRAMEWORK_ARCHITECTURE.md and ADR 0003 (D2).
 */
interface Router
{
    /**
     * Match an HTTP method and normalized path.
     *
     * Returns null when no route matches the path. When the path matches another
     * method, allowedMethods() supplies the exact Allow header list for 405.
     */
    public function match(string $method, string $path): ?RouteMatch;

    /**
     * Return allowed HTTP methods for a matched path when the requested method is unsupported.
     *
     * @return list<string> Empty when the path does not exist at all (404).
     */
    public function allowedMethods(string $path): array;
}
