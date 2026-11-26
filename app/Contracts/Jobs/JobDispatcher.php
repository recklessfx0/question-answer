<?php

declare(strict_types=1);

namespace QuestionAnswer\Contracts\Jobs;

/**
 * Enqueues bounded background jobs executed by optional cron or the
 * single-flight opportunistic post-response runner on shared hosting (CORE-013).
 *
 * See docs/FRAMEWORK_ARCHITECTURE.md and ADR 0004 (D9).
 */
interface JobDispatcher
{
    /**
     * Persist a job to the queue and return its tracking ID.
     */
    public function dispatch(Job $job): string;
}
