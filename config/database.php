<?php

declare(strict_types=1);

/**
 * Database connection baseline (MySQL 8.4 LTS / MariaDB 10.11 LTS).
 * Credentials are injected at deploy/install compile time, never committed here.
 */
return [
    'driver' => 'mysql',
    'host' => '127.0.0.1',
    'port' => 3306,
    'database' => 'questionanswer',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'emulate_prepares' => false,
    'strict_mode' => true,
    'timezone' => '+00:00',
];
