<?php

declare(strict_types=1);

namespace QuestionAnswer\Contracts\Extension;

/**
 * Immutable validated extension manifest declared in extension.json (EXT-001).
 *
 * PHP extensions execute with application privileges (SEC-F-02); language packs
 * ("languages" type) are the sole data-only exception shipping no PHP entrypoint.
 *
 * See docs/EXTENSIONS.md, docs/SECURITY_REQUIREMENTS.md (SEC-F) and ADR 0004 (D8).
 */
final readonly class ExtensionManifest
{
    public const VALID_TYPES = [
        'plugin',
        'theme',
        'module',
        'widget',
        'provider',
        'adapter',
        'mcp-tool',
        'languages',
    ];

    /**
     * @param string                $id           Unique vendor/name slug (e.g. "acme/sitemap-extra").
     * @param string                $name         Human-readable display name.
     * @param string                $version      Semver string.
     * @param string                $type         One of VALID_TYPES.
     * @param string                $coreConstraint Compatible core version constraint.
     * @param string                $phpConstraint  Compatible PHP constraint (e.g. "~8.5.0").
     * @param string                $license      SPDX license identifier.
     * @param string|null           $entrypoint   PHP ModuleInterface class name, or null for "languages" packs.
     * @param array<string, string> $dependencies Map of required extension IDs to version constraints.
     * @param list<string>          $capabilities Declared informational capabilities (routes, hooks, templates, etc.).
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $version,
        public string $type,
        public string $coreConstraint,
        public string $phpConstraint,
        public string $license,
        public ?string $entrypoint = null,
        public array $dependencies = [],
        public array $capabilities = [],
    ) {
        if (!in_array($this->type, self::VALID_TYPES, true)) {
            throw new \InvalidArgumentException("Unsupported extension type: {$this->type}");
        }

        if ($this->type === 'languages' && $this->entrypoint !== null) {
            throw new \InvalidArgumentException('Language packs are data-only and must not declare a PHP entrypoint.');
        }

        if ($this->type !== 'languages' && ($this->entrypoint === null || $this->entrypoint === '')) {
            throw new \InvalidArgumentException("Executable extension type '{$this->type}' requires an entrypoint.");
        }
    }
}
