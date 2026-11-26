<?php

declare(strict_types=1);

namespace QuestionAnswer\Contracts\Container;

/**
 * Declarative build-time service registration entry consumed by ContainerCompiler.
 *
 * See docs/FRAMEWORK_ARCHITECTURE.md and ADR 0004.
 */
final readonly class ServiceDefinition
{
    /**
     * @param string            $id           Service interface or class name.
     * @param class-string      $class        Concrete class to instantiate.
     * @param list<string>      $dependencies Ordered constructor dependency service IDs.
     * @param bool              $shared       Whether one instance is reused per request.
     * @param list<string>      $tags         Optional build-time grouping tags (e.g. "sitemap.source").
     */
    public function __construct(
        public string $id,
        public string $class,
        public array $dependencies = [],
        public bool $shared = true,
        public array $tags = [],
    ) {
        if ($this->id === '') {
            throw new \InvalidArgumentException('Service ID must not be empty.');
        }
    }
}
