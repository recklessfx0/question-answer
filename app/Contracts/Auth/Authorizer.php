<?php

declare(strict_types=1);

namespace QuestionAnswer\Contracts\Auth;

/**
 * Central server-side object authorization boundary shared by web, REST and MCP.
 *
 * Implementations always evaluate object-level policies server-side and fail
 * closed on unknown abilities or pending_mfa actors (SEC-B-01). When the actor
 * holds API/MCP $tokenScopes, the required scope must ALSO be present (SEC-B-02).
 *
 * See docs/FRAMEWORK_ARCHITECTURE.md and docs/SECURITY_REQUIREMENTS.md (SEC-B).
 */
interface Authorizer
{
    /**
     * Report whether $actor may perform $ability on $resource.
     */
    public function allows(Actor $actor, string $ability, object|string $resource, ?string $requiredScope = null): bool;

    /**
     * Assert that $actor may perform $ability on $resource, throwing on denial.
     *
     * @throws \RuntimeException When authorization is denied.
     */
    public function authorize(Actor $actor, string $ability, object|string $resource, ?string $requiredScope = null): void;
}
