<?php

declare(strict_types=1);

namespace QuestionAnswer\Contracts\Auth;

/**
 * Immutable identity of the current caller across web, REST, MCP and CLI.
 *
 * Explicit session states (anonymous, pending_mfa, authenticated) prevent
 * MFA bypasses on partially authenticated sessions (SEC-A-02, SEC-A-05).
 * When authenticated via a scoped API/MCP token, $tokenScopes constrains
 * (never expands) the actor's object-level permissions (SEC-B-02).
 *
 * See docs/AUTH.md, docs/FRAMEWORK_ARCHITECTURE.md and ADR 0005.
 */
final readonly class Actor
{
    public const STATE_ANONYMOUS = 'anonymous';
    public const STATE_PENDING_MFA = 'pending_mfa';
    public const STATE_AUTHENTICATED = 'authenticated';

    /**
     * @param string|null       $userId      Authenticated user ID, or null when anonymous.
     * @param string            $state       One of STATE_ANONYMOUS, STATE_PENDING_MFA, STATE_AUTHENTICATED.
     * @param list<string>      $roles       Assigned role slugs (e.g. "admin", "editor", "member").
     * @param list<string>|null $tokenScopes Non-null when acting via API/MCP token; constrains permissions.
     * @param int|null          $sudoUntil   Unix timestamp until which step-up verification is valid.
     * @param string            $locale      Stored user locale preference or site default.
     */
    public function __construct(
        public ?string $userId = null,
        public string $state = self::STATE_ANONYMOUS,
        public array $roles = [],
        public ?array $tokenScopes = null,
        public ?int $sudoUntil = null,
        public string $locale = 'en',
    ) {
        if (!in_array($this->state, [self::STATE_ANONYMOUS, self::STATE_PENDING_MFA, self::STATE_AUTHENTICATED], true)) {
            throw new \InvalidArgumentException('Invalid actor session state.');
        }

        if ($this->state !== self::STATE_ANONYMOUS && ($this->userId === null || $this->userId === '')) {
            throw new \InvalidArgumentException('Non-anonymous actor must carry a userId.');
        }
    }

    public static function anonymous(string $locale = 'en'): self
    {
        return new self(userId: null, state: self::STATE_ANONYMOUS, locale: $locale);
    }

    public function isAuthenticated(): bool
    {
        return $this->state === self::STATE_AUTHENTICATED && $this->userId !== null;
    }

    public function isPendingMfa(): bool
    {
        return $this->state === self::STATE_PENDING_MFA;
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles, true);
    }

    public function hasValidSudo(int $now): bool
    {
        return $this->isAuthenticated() && $this->sudoUntil !== null && $this->sudoUntil >= $now;
    }
}
