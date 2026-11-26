<?php

declare(strict_types=1);

/**
 * Transactional mail and secure SMTP configuration baseline (ADR 0006, MAIL.md).
 * Defaults to "log" until configured and verified by an administrator.
 */
return [
    'transport' => 'log',
    'from' => [
        'address' => 'noreply@example.com',
        'name' => 'QuestionAnswer',
    ],
    'smtp' => [
        'host' => 'smtp.example.com',
        'port' => 465,
        'encryption' => 'smtps',
        'verify_peer' => true,
        'verify_peer_name' => true,
        'connect_timeout_seconds' => 10,
        'command_timeout_seconds' => 15,
        'allow_local_relay' => false,
    ],
    'dkim' => [
        'enabled' => false,
        'selector' => 'qa',
        'algorithm' => 'rsa-sha256',
    ],
    'queue' => [
        'max_attempts' => 5,
    ],
];
