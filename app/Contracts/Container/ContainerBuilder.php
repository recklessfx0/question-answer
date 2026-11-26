<?php

declare(strict_types=1);

namespace QuestionAnswer\Contracts\Container;

/**
 * Build-time registry used by core modules and extensions to declare services.
 *
 * Validated for missing bindings and circular dependencies during compilation,
 * never executed on the HTTP request path.
 *
 * See docs/FRAMEWORK_ARCHITECTURE.md and ADR 0004.
 */
interface ContainerBuilder
{
    /**
     * Register a concrete service definition.
     *
     * @throws \InvalidArgumentException When a duplicate incompatible definition is registered.
     */
    public function register(ServiceDefinition $definition): void;

    /**
     * Alias an interface or symbolic ID to an existing service ID.
     */
    public function alias(string $alias, string $targetId): void;

    /**
     * Return all registered service definitions for compilation.
     *
     * @return array<string, ServiceDefinition>
     */
    public function definitions(): array;
}
