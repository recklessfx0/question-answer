<?php

declare(strict_types=1);

namespace QuestionAnswer\Contracts\Security;

/**
 * Generates and verifies RFC 6238 TOTP two-factor authentication secrets.
 *
 * Implementations enforce a bounded clock-skew window (at most +/- 1 time step
 * of 30 seconds) and replay protection by rejecting any matched time step less
 * than or equal to the credential's last used time step. Raw secrets are stored
 * encrypted via SecretBox, never in plaintext or logs.
 *
 * See docs/AUTH.md, docs/SECURITY_REQUIREMENTS.md (SEC-A-05) and ADR 0005.
 */
interface TotpVerifier
{
    /**
     * Generate a cryptographically random Base32-encoded TOTP secret (>= 160 bits).
     */
    public function generateSecret(): string;

    /**
     * Build an otpauth://totp/ provisioning URI for QR enrollment.
     *
     * @throws \InvalidArgumentException When issuer, account label or secret is invalid.
     */
    public function provisioningUri(string $secret, string $issuer, string $accountLabel): string;

    /**
     * Verify a 6-digit TOTP code against a secret with replay protection.
     *
     * Returns the matched integer time step on success so the caller can persist
     * it atomically as the new replay floor, or null when verification fails.
     *
     * @param string   $secret           Base32-encoded TOTP secret.
     * @param string   $code             User-supplied 6-digit code.
     * @param int|null $lastUsedTimeStep Highest previously accepted time step, or null if unused.
     * @param int|null $timestamp        Unix timestamp for deterministic testing; null uses current time.
     *
     * @return int|null Accepted time step strictly greater than $lastUsedTimeStep, or null on failure.
     */
    public function verify(
        string $secret,
        string $code,
        ?int $lastUsedTimeStep = null,
        ?int $timestamp = null,
    ): ?int;
}
