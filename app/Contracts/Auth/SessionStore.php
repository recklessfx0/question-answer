<?php

declare(strict_types=1);

namespace QuestionAnswer\Contracts\Auth;

/**
 * Server-tracked session store supporting idle/absolute expiry, session ID
 * regeneration, pending_mfa quarantine and global device revocation.
 *
 * See docs/AUTH.md, docs/SECURITY_REQUIREMENTS.md (SEC-A-02, SEC-A-03) and ADR 0005.
 */
interface SessionStore
{
    /**
     * Read an active session payload by opaque session ID, or null if absent/expired.
     *
     * @return array<string, mixed>|null
     */
    public function read(string $sessionId): ?array;

    /**
     * Persist session data with explicit idle and absolute expiry Unix timestamps.
     *
     * @param array<string, mixed> $payload
     */
    public function write(string $sessionId, array $payload, int $idleExpiresAt, int $absoluteExpiresAt): void;

    /**
     * Rotate a session ID on login or privilege change, invalidating the old ID.
     *
     * @return string Newly generated high-entropy session ID.
     */
    public function regenerate(string $oldSessionId): string;

    /**
     * Destroy one session immediately.
     */
    public function destroy(string $sessionId): void;

    /**
     * Revoke every session belonging to a user (except an optional current session ID).
     */
    public function revokeForUser(string $userId, ?string $exceptSessionId = null): int;
}
