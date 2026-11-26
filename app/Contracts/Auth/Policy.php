<?php

declare(strict_types=1);

namespace QuestionAnswer\Contracts\Auth;

/**
 * Domain-specific resource authorization policy registered at compile time.
 *
 * See docs/FRAMEWORK_ARCHITECTURE.md and docs/SECURITY_REQUIREMENTS.md (SEC-B-01).
 */
interface Policy
{
    /**
     * Resource class or resource-type identifier this policy governs.
     */
    public function resourceType(): string;

    /**
     * Evaluate whether $actor may perform $ability on $resource.
     */
    public function evaluate(Actor $actor, string $ability, object|string $resource): bool;
}
