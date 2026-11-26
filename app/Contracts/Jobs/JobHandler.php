<?php

declare(strict_types=1);

namespace QuestionAnswer\Contracts\Jobs;

/**
 * Executes one registered background job type within bounded time and memory limits.
 *
 * See docs/FRAMEWORK_ARCHITECTURE.md and CORE-013.
 */
interface JobHandler
{
    public function supports(): string;

    /**
     * Execute the job; throw \RuntimeException on retryable failure.
     */
    public function handle(Job $job): void;
}
