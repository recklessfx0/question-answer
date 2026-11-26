<?php

declare(strict_types=1);

/**
 * Layered cache baseline (ADR 0003). Atomic file cache is the shared-hosting default;
 * APCu or Redis are optional uplifts behind the same CacheStore contract.
 */
return [
    'driver' => 'file',
    'format_version' => 1,
    'default_ttl_seconds' => 300,
    'stale_window_seconds' => 60,
    'lock_ttl_seconds' => 10,
    'page_cache_enabled' => true,
];
