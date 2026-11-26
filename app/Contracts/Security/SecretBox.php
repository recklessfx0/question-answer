<?php

declare(strict_types=1);

namespace QuestionAnswer\Contracts\Security;

/**
 * Authenticated encryption at rest for long-lived application secrets.
 *
 * Protects secrets that the runtime must read back — TOTP seeds, SMTP
 * credentials, DKIM private keys, AI provider keys and webhook signing secrets
 * — using versioned AEAD envelopes so keys can rotate online without downtime.
 *
 * Every encryption call binds immutable associated data (AAD), such as
 * "totp:42" or "smtp:default", preventing ciphertext substitution across rows
 * or purposes. Implementations must fail closed on tampered ciphertext, unknown
 * key IDs or mismatched AAD, and must never log plaintext or key material.
 *
 * See docs/AUTH.md, docs/SECURITY_REQUIREMENTS.md (SEC-D-05) and ADR 0005.
 */
interface SecretBox
{
    /**
     * Encrypt a plaintext secret under the current active key and bind it to AAD.
     *
     * @param string $plaintext      Non-empty secret bytes to protect.
     * @param string $associatedData Non-empty immutable context (e.g. "totp:42").
     *
     * @return string Versioned, ASCII-safe ciphertext envelope.
     *
     * @throws \InvalidArgumentException When $plaintext or $associatedData is empty.
     * @throws \RuntimeException         When encryption fails or no active key is configured.
     */
    public function encrypt(string $plaintext, string $associatedData): string;

    /**
     * Decrypt a versioned ciphertext envelope and verify its AAD binding.
     *
     * @param string $envelope       Ciphertext envelope returned by encrypt().
     * @param string $associatedData Exact context supplied when encrypting.
     *
     * @throws \RuntimeException When authentication fails, AAD mismatches, or the key ID is unknown.
     */
    public function decrypt(string $envelope, string $associatedData): string;

    /**
     * Report whether the envelope was encrypted with a retired key and should be re-encrypted.
     */
    public function needsReencrypt(string $envelope): bool;
}
