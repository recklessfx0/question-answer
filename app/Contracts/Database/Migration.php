<?php

declare(strict_types=1);

namespace QuestionAnswer\Contracts\Database;

/**
 * Versioned, engine-compatible schema migration for core or an extension.
 *
 * Irreversible migrations must explicitly report isReversible() = false and
 * document their recovery procedure (SEC-G-06).
 *
 * See docs/SECURITY_REQUIREMENTS.md (SEC-G-06) and docs/ARCHITECTURE.md.
 */
interface Migration
{
    public function version(): string;

    public function description(): string;

    public function isReversible(): bool;

    public function up(Connection $connection): void;

    public function down(Connection $connection): void;
}
