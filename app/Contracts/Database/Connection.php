<?php

declare(strict_types=1);

namespace QuestionAnswer\Contracts\Database;

/**
 * Parameterized PDO database connection contract (MySQL 8.4 LTS / MariaDB 10.11 LTS).
 *
 * All values pass through bound parameters (SEC-C-01) with native prepared
 * statements (ATTR_EMULATE_PREPARES = false) and UTC session timezone.
 *
 * See docs/FRAMEWORK_ARCHITECTURE.md and docs/SECURITY_REQUIREMENTS.md (SEC-C-01).
 */
interface Connection
{
    /**
     * Execute a SELECT query and return all matching rows as associative arrays.
     *
     * @param array<int|string, scalar|null> $params
     *
     * @return list<array<string, mixed>>
     */
    public function fetchAll(string $sql, array $params = []): array;

    /**
     * Execute a SELECT query and return the first row, or null when absent.
     *
     * @param array<int|string, scalar|null> $params
     *
     * @return array<string, mixed>|null
     */
    public function fetchOne(string $sql, array $params = []): ?array;

    /**
     * Execute a write statement (INSERT, UPDATE, DELETE) and return affected row count.
     *
     * @param array<int|string, scalar|null> $params
     */
    public function execute(string $sql, array $params = []): int;

    /**
     * Return the last auto-generated insert identifier.
     */
    public function lastInsertId(): string;
}
