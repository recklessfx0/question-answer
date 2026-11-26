<?php

declare(strict_types=1);

namespace QuestionAnswer\Contracts\Extension;

use QuestionAnswer\Contracts\Container\ContainerBuilder;

/**
 * Unified declarative contract implemented by both core CMS modules (app/Modules/*)
 * and executable third-party extensions (extensions/*).
 *
 * Invoked strictly at build/install/activation time by ArtifactCompiler — never
 * discovered by scanning directories on HTTP requests (ADR 0004 D2, D8).
 *
 * See docs/FRAMEWORK_ARCHITECTURE.md and docs/EXTENSIONS.md.
 */
interface ModuleInterface extends ArtifactContributor
{
    /**
     * Stable unique module or extension identifier (e.g. "core/questions").
     */
    public function id(): string;

    /**
     * Register service definitions and interface bindings into the build-time container.
     */
    public function registerServices(ContainerBuilder $builder): void;

    /**
     * Ordered list of Migration class names owned by this module.
     *
     * @return list<class-string>
     */
    public function migrations(): array;
}
