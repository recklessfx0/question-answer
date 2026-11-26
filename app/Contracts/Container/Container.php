<?php

declare(strict_types=1);

namespace QuestionAnswer\Contracts\Container;

/**
 * Runtime service container backed by deploy-time compiled closure factories.
 *
 * Mirrors PSR-11 semantics without requiring reflection at request time. Domain
 * code receives dependencies via constructor injection; this contract is used
 * at composition boundaries (kernel, router, job runner, event dispatcher).
 *
 * See docs/FRAMEWORK_ARCHITECTURE.md and ADR 0003 / ADR 0004.
 */
interface Container
{
    /**
     * Resolve a service by interface or class identifier.
     *
     * @template T of object
     * @param class-string<T>|string $id
     *
     * @return ($id is class-string<T> ? T : mixed)
     *
     * @throws \RuntimeException When the service is unknown or cannot be constructed.
     */
    public function get(string $id): mixed;

    /**
     * Report whether a service identifier is registered in the compiled graph.
     */
    public function has(string $id): bool;
}
