<?php

declare(strict_types=1);

namespace QuestionAnswer\Contracts\Extension;

/**
 * Build-time descriptor returned by modules and extensions so ArtifactCompiler
 * compiles routes, event listeners, template namespaces, UI slots and admin nav
 * with zero runtime discovery (ADR 0003 D10, PERF-008).
 *
 * See docs/FRAMEWORK_ARCHITECTURE.md and docs/EXTENSIONS.md.
 */
interface ArtifactContributor
{
    /**
     * Declarative route definitions to compile into storage/compiled/routes.php.
     *
     * @return list<array{
     *     name: string,
     *     method: string,
     *     path: string,
     *     handler: string,
     *     group?: string,
     *     queryBudget?: int,
     *     cacheable?: bool,
     * }>
     */
    public function routes(): array;

    /**
     * Declarative event listener bindings (event class/name => list of [listenerServiceId, priority]).
     *
     * @return array<string, list<array{service: string, priority: int}>>
     */
    public function eventListeners(): array;

    /**
     * Map of template namespace => absolute directory path for compile-time template builds.
     *
     * @return array<string, string>
     */
    public function templateNamespaces(): array;

    /**
     * Map of UI slot name => list of [templateName, priority].
     *
     * @return array<string, list<array{template: string, priority: int}>>
     */
    public function templateSlots(): array;
}
