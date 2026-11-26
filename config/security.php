<?php

declare(strict_types=1);

/**
 * Security, session, 2FA/TOTP and WebAuthn passkey baseline (ADR 0005, HARDENING.md).
 */
return [
    'session' => [
        'cookie_name' => '__Host-qa_session',
        'cookie_samesite' => 'Lax',
        'idle_ttl_seconds' => 7200,
        'absolute_ttl_seconds' => 43200,
        'pending_mfa_ttl_seconds' => 300,
        'sudo_ttl_seconds' => 900,
    ],
    'password' => [
        'min_length' => 12,
        'max_length' => 128,
    ],
    'mfa' => [
        'totp_window_steps' => 1,
        'recovery_code_count' => 8,
        'require_for_roles' => ['admin'],
    ],
    'passkeys' => [
        'enabled' => true,
        'challenge_ttl_seconds' => 300,
        'attestation' => 'none',
        'require_user_verification_passwordless' => true,
    ],
    'uploads' => [
        'max_bytes' => 8388608,
        'allowed_mime_types' => ['image/png', 'image/jpeg', 'image/webp'],
    ],
];
