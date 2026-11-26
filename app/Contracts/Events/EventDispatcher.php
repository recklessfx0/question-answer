<?php

declare(strict_types=1);

namespace QuestionAnswer\Contracts\Events;

/**
 * Dispatches domain and framework events to compiled, lazily instantiated listeners.
 *
 * Mirrors PSR-14 semantics. Listeners are declared at build/install time and
 * instantiated via the compiled container only when an event fires.
 *
 * See docs/FRAMEWORK_ARCHITECTURE.md and ADR 0003 (D10).
 */
interface EventDispatcher
{
    /**
     * Dispatch an event object to all registered listeners in declared priority order.
     *
     * @template T of object
     * @param T $event
     *
     * @return T
     */
    public function dispatch(object $event): object;
}
