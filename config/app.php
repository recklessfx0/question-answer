<?php

declare(strict_types=1);

/**
 * Core application configuration template compiled at deploy/install time
 * into storage/compiled/config.php. Never parsed per HTTP request.
 */
return [
    'name' => 'QuestionAnswer',
    'env' => 'production',
    'url' => 'https://example.com',
    'timezone' => 'UTC',
    'debug' => false,
    'safe_mode_lock' => 'config/safe_mode.lock',
];
