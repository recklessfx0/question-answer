<?php

declare(strict_types=1);

namespace QuestionAnswer\Contracts\Security;

/**
 * Append-only audit log for security-sensitive and administrative actions.
 *
 * Records actor ID, action name, target resource, outcome and redacted metadata
 * (never passwords, tokens or private payloads).
 *
 * See docs/SECURITY_REQUIREMENTS.md (SEC-B-04, SEC-D-01) and docs/AUTH.md.
 */
interface AuditLogger
{
    /**
     * @param array<string, scalar|null> $metadata Redacted scalar metadata only.
     */
    public function record(
        string $action,
        ?string $actorUserId,
        string $targetType,
        string $targetId,
        string $outcome = 'success',
        array $metadata = [],
    ): void;
}
