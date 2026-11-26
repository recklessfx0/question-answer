<?php

declare(strict_types=1);

namespace QuestionAnswer\Contracts\Http;

/**
 * Immutable result of matching an HTTP request against the compiled route table.
 *
 * See docs/FRAMEWORK_ARCHITECTURE.md and ADR 0003 / ADR 0004.
 */
final readonly class RouteMatch
{
    /**
     * @param string                    $name          Stable route name (e.g. "question.show").
     * @param string                    $handler       Controller/handler service ID or "Class::method".
     * @param array<string, string|int> $parameters    Validated typed route parameters.
     * @param string                    $group         Middleware group (web, public-artifact, api, mcp, admin, installer).
     * @param int                       $queryBudget   Declared max DB queries allowed for this route (PERF-007).
     * @param bool                      $cacheable     Whether anonymous public L1/HTTP caching is permitted.
     */
    public function __construct(
        public string $name,
        public string $handler,
        public array $parameters = [],
        public string $group = 'web',
        public int $queryBudget = 8,
        public bool $cacheable = false,
    ) {
        if ($this->name === '' || $this->handler === '') {
            throw new \InvalidArgumentException('RouteMatch requires non-empty name and handler.');
        }

        if ($this->queryBudget < 0) {
            throw new \InvalidArgumentException('Route queryBudget must be non-negative.');
        }
    }
}
