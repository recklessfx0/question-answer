<?php

declare(strict_types=1);

namespace QuestionAnswer\Contracts\Events;

/**
 * Marker contract for immutable cross-module domain events.
 *
 * Modules publish domain events instead of calling another module's internals
 * directly (ADR 0004 D1).
 *
 * See docs/FRAMEWORK_ARCHITECTURE.md and ADR 0004.
 */
interface DomainEvent
{
    /**
     * Stable dotted event name (e.g. "question.published", "user.mfa_enabled").
     */
    public function eventName(): string;

    /**
     * UTC RFC 3339 timestamp when the event occurred.
     */
    public function occurredAt(): string;
}
