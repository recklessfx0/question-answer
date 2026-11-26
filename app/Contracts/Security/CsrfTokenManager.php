<?php

declare(strict_types=1);

namespace QuestionAnswer\Contracts\Security;

/**
 * Issues and verifies session-bound synchronizer CSRF tokens using constant-time comparison.
 *
 * See docs/AUTH.md and docs/SECURITY_REQUIREMENTS.md (SEC-A-06).
 */
interface CsrfTokenManager
{
    public function token(string $sessionId, string $purpose = 'default'): string;

    public function verify(string $sessionId, string $submittedToken, string $purpose = 'default'): bool;

    public function rotate(string $sessionId, string $purpose = 'default'): string;
}
