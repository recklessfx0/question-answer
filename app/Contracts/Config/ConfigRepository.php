<?php

declare(strict_types=1);

namespace QuestionAnswer\Contracts\Config;

/**
 * Immutable read-only access to compiled application configuration.
 *
 * Configuration is merged and compiled at deploy/install time into a plain PHP
 * array artifact (storage/compiled/config.php). Request paths never parse .env
 * files or scan config directories.
 *
 * See docs/FRAMEWORK_ARCHITECTURE.md and ADR 0003 / ADR 0004.
 */
interface ConfigRepository
{
    /**
     * Check whether a dotted configuration key exists.
     */
    public function has(string $key): bool;

    /**
     * Retrieve a configuration value by dotted key, returning $default if absent.
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Retrieve a required string value.
     *
     * @throws \InvalidArgumentException When the key is missing or not a string.
     */
    public function string(string $key): string;

    /**
     * Retrieve a required integer value.
     *
     * @throws \InvalidArgumentException When the key is missing or not an integer.
     */
    public function int(string $key): int;

    /**
     * Retrieve a required boolean value.
     *
     * @throws \InvalidArgumentException When the key is missing or not a boolean.
     */
    public function bool(string $key): bool;
}
