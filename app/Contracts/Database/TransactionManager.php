<?php

declare(strict_types=1);

namespace QuestionAnswer\Contracts\Database;

/**
 * Explicit database transaction boundary with savepoint support for nesting.
 *
 * See docs/ARCHITECTURE.md and docs/FRAMEWORK_ARCHITECTURE.md.
 */
interface TransactionManager
{
    /**
     * Execute $callback inside a transaction, committing on success and rolling back on any Throwable.
     *
     * @template T
     * @param callable(): T $callback
     *
     * @return T
     */
    public function run(callable $callback): mixed;

    public function nestingLevel(): int;
}
