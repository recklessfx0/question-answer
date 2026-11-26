<?php

declare(strict_types=1);

namespace QuestionAnswer\Contracts\Security;

/**
 * Hashes and verifies user passwords using Argon2id (with documented fallback)
 * and transparent rehash-on-login.
 *
 * See docs/AUTH.md and docs/SECURITY_REQUIREMENTS.md (SEC-A-01).
 */
interface PasswordHasher
{
    public function hash(string $plainPassword): string;

    public function verify(string $plainPassword, string $storedHash): bool;

    public function needsRehash(string $storedHash): bool;
}
