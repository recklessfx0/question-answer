<?php

declare(strict_types=1);

namespace QuestionAnswer\Contracts\Security;

/**
 * Verifies WebAuthn / FIDO2 passkey registration and authentication ceremonies.
 *
 * Supports both discoverable passkeys (passwordless login requiring UV) and
 * WebAuthn security keys (2FA factor requiring UP). Implementations enforce
 * exact origin and RP ID matching, single-use challenge equality, supported
 * COSE algorithms (ES256, EdDSA, RS256), and sign-count clone detection.
 *
 * Takes primitives and associative arrays rather than HTTP request objects so
 * web, admin and recovery flows share one audited cryptographic boundary.
 *
 * See docs/AUTH.md, docs/SECURITY_REQUIREMENTS.md (SEC-A-07) and ADR 0005.
 */
interface PasskeyVerifier
{
    /**
     * Generate a cryptographically random base64url challenge (>= 32 bytes).
     */
    public function createChallenge(): string;

    /**
     * Verify a WebAuthn registration response (navigator.credentials.create).
     *
     * @param string $clientDataJson    Raw clientDataJSON bytes.
     * @param string $attestationObject Raw CBOR attestationObject bytes.
     * @param string $expectedChallenge Expected base64url challenge from server session.
     * @param string $expectedOrigin    Exact HTTPS origin (e.g. "https://example.com").
     * @param string $expectedRpId      Expected Relying Party ID (e.g. "example.com").
     * @param bool   $requireUserVerification Whether the UV flag must be set.
     *
     * @return array{
     *     credentialId: string,
     *     publicKeyCose: string,
     *     algorithm: int,
     *     signCount: int,
     *     backupEligible: bool,
     *     backupState: bool,
     *     userVerified: bool,
     * }
     *
     * @throws \InvalidArgumentException When payload encoding or inputs are malformed.
     * @throws \RuntimeException         When cryptographic, origin, RP ID or challenge verification fails.
     */
    public function verifyRegistration(
        string $clientDataJson,
        string $attestationObject,
        string $expectedChallenge,
        string $expectedOrigin,
        string $expectedRpId,
        bool $requireUserVerification = true,
    ): array;

    /**
     * Verify a WebAuthn authentication assertion (navigator.credentials.get).
     *
     * Implementations must fail closed if both stored and asserted sign counts
     * are non-zero and the asserted count does not strictly increase (clone signal).
     *
     * @param string $clientDataJson    Raw clientDataJSON bytes.
     * @param string $authenticatorData Raw authenticatorData bytes.
     * @param string $signature         Raw assertion signature bytes.
     * @param string $publicKeyCose     Stored COSE public key bytes for the credential.
     * @param int    $storedSignCount   Last persisted signature counter.
     * @param string $expectedChallenge Expected base64url challenge from server session.
     * @param string $expectedOrigin    Exact HTTPS origin (e.g. "https://example.com").
     * @param string $expectedRpId      Expected Relying Party ID (e.g. "example.com").
     * @param bool   $requireUserVerification True for passwordless login; false allowed for 2FA-only keys.
     *
     * @return array{
     *     signCount: int,
     *     backupState: bool,
     *     userVerified: bool,
     * }
     *
     * @throws \InvalidArgumentException When payload encoding or inputs are malformed.
     * @throws \RuntimeException         When verification fails or sign-count regression indicates cloning.
     */
    public function verifyAssertion(
        string $clientDataJson,
        string $authenticatorData,
        string $signature,
        string $publicKeyCose,
        int $storedSignCount,
        string $expectedChallenge,
        string $expectedOrigin,
        string $expectedRpId,
        bool $requireUserVerification = true,
    ): array;
}
