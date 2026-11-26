<?php

declare(strict_types=1);

namespace QuestionAnswer\Contracts\Database;

/**
 * Tracks per-request SQL query counts, normalized shapes and durations for
 * CI query-budget and N+1 regression enforcement (PERF-007).
 *
 * See docs/FRAMEWORK_PERFORMANCE.md and docs/TESTING.md.
 */
interface QueryProfiler
{
    public function record(string $sql, float $durationMs): void;

    public function queryCount(): int;

    public function totalDurationMs(): float;

    /**
     * @return list<array{sql: string, durationMs: float}>
     */
    public function entries(): array;

    public function reset(): void;
}
