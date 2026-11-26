# ADR: Authentication, 2FA (TOTP), passkeys (WebAuthn) and secret encryption
Status: proposed

## Context

[SECURITY_REQUIREMENTS](../SECURITY_REQUIREMENTS.md) targets OWASP ASVS 5.0 Level 2 with selected Level 3 controls for privileged workflows, and [backlog/tasks.json](../../backlog/tasks.json) includes `SEC-001` (authentication), `SEC-002` (sessions) and `SEC-007` (MFA and recovery). However, the repository currently has only a single-line requirement for administrator MFA (`SEC-A-05`), no specification for TOTP replay/storage or recovery codes, zero coverage of phishing-resistant passkeys (WebAuthn/FIDO2), no specification for email-backed account lifecycle tokens (verification, password reset, email change), and secret encryption at rest scoped only to AI credentials (`SEC-E-03`).

Implementing authentication without deciding these boundaries leads to predictable vulnerabilities: MFA bypass through unprotected intermediate sessions, plaintext TOTP/SMTP secrets in the database, replayable OTP codes, unrevoked sessions after password resets, and insecure ad-hoc recovery flows.

Constraints: PHP 8.5 strict types on ordinary shared hosting, zero mandatory external identity SaaS or daemons, native `password_hash`/`openssl`/`sodium`, and compatibility with subdirectory deployments.

## Decision

**D1 — Explicit three-state session machine.** Sessions transition explicitly between `anonymous`, `pending_mfa` (quarantined to MFA verification and logout only, ≤ 5 min TTL) and `authenticated`, regenerating the session ID on every authentication and privilege transition (`SEC-A-02`).

**D2 — Step-up ("sudo") re-authentication.** Destructive and security-sensitive actions (credential/MFA changes, email change, extension install, updates, role/key changes, account deletion) require a fresh verification within a bounded window (default 15 minutes).

**D3 — TOTP (RFC 6238) available to all users.** 160-bit random seeds encrypted at rest via `SecretBox`, `±1` step drift maximum, replay protection via persisted `last_used_time_step`, per-user challenge throttling, and optional admin policy to require MFA by role (`admin`, `editor`).

**D4 — Passkeys and WebAuthn (FIDO2) as first-class auth.** Support both discoverable passkeys (passwordless login with `UV` required) and WebAuthn security keys (2FA factor) using `ES256`, `EdDSA` and `RS256`, single-use server-side challenges (≤ 5 min TTL), strict origin + RP ID verification, `attestation: "none"` default, and `sign_count` clone detection.

**D5 — Hashed single-use recovery codes.** Generated on MFA activation (8–10 codes, ≥ 64 bits entropy each), stored strictly as one-way hashes, consumed atomically once, audited and alerted by email on use, and regeneratable only after step-up re-authentication.

**D6 — Unified account-lifecycle token hygiene.** Email verification, password reset and email-change tokens use ≥ 256-bit `random_bytes()`, are stored strictly as SHA-256 hashes, have short purpose-specific TTLs, invalidate prior unused tokens on reissue, and enforce uniform anti-enumeration responses (`SEC-A-04`, `SEC-A-08`). Password reset revokes active sessions and never disables MFA.

**D7 — Generalized `SecretBox` AEAD encryption at rest.** One core contract (`app/Contracts/Security/SecretBox.php`) backed by `sodium_crypto_aead_xchacha20poly1305_ietf_*` with versioned `key_id` envelopes and mandatory associated data (AAD) protects TOTP seeds, SMTP credentials, AI provider keys, webhook secrets and integration secrets (`SEC-D-05`).

**D8 — Server-tracked sessions and global revocation.** Sessions persist server-side metadata (idle/absolute expiry, device summary) to support "Sign out all other devices" and immediate revocation on password reset or admin disable.

**D9 — Clarified CSRF scope.** Cookie-authenticated state changes (HTML, JSON, cookie-backed admin/MCP) require synchronizer CSRF tokens plus `Origin`/`Sec-Fetch-Site` checks; stateless `Authorization: Bearer` API/MCP endpoints ignore session cookies and rely on token + scope + object authorization (`SEC-A-06`, `SEC-B-02`).

## Alternatives

- **SMS or email OTP as primary 2FA.** Vulnerable to SIM swapping, inbox takeover and SMS provider billing/dependencies on shared hosting. Rejected as core MFA; TOTP and WebAuthn work offline and cost nothing.
- **TOTP only, deferring Passkeys indefinitely.** Leaves admin workflows (installer, updater, extension activation) exposed to real-time phishing proxies, falling short of ASVS 5.0 L3 intent for privileged actions. Rejected; WebAuthn is built into the auth contract from `v0.2`.
- **External SaaS auth (Auth0, Clerk, Cognito).** Violates the self-hosted, zero-mandatory-external-service charter rule. Rejected (optional OIDC/SSO may exist later as an extension).
- **Per-subsystem ad-hoc encryption for TOTP, SMTP and AI keys.** Multiplies cryptographic misuse and key-rotation bugs. Rejected in favour of D7 (`SecretBox`).

## Security and performance implications

- **Eliminates MFA bypass & replay:** Quarantined `pending_mfa` state, `last_used_time_step` checks, single-use WebAuthn challenges and `sign_count` monitoring close the classic 2FA implementation flaws.
- **Eliminates plaintext secret exposure on DB dump:** `SecretBox` with AAD ensures a read-only SQL/backup leak does not yield usable TOTP seeds, SMTP credentials or AI keys without the out-of-band application key.
- **Performance:** TOTP and WebAuthn assertions run only during login or step-up ceremonies, adding zero cost to normal page requests or cached public routes. Argon2id parameters are tuned during `HOST-001` to stay within shared-hosting memory limits (128 MB baseline).

## Shared hosting implications

- All primitives use PHP 8.5 standard extensions (`password_hash`, `hash_hmac`, `openssl`, `sodium`). No background daemon or external API is required.
- WebAuthn validates origin and RP ID (host), so subdirectory deployments (`/qa/`) work without special DNS or rewrite hacks, provided HTTPS is active (already required by `HARDENING.md`).
- If a host disables `sodium`, the installer fails closed rather than silently falling back to unencrypted secret storage.

## Compatibility and migration

- Additive tables (`user_mfa_totp`, `user_passkeys`, `user_recovery_codes`, `auth_tokens`, `user_sessions`) introduced in `v0.2` (`SEC-001`–`SEC-007`).
- `SecretBox` key versioning supports non-disruptive key rotation via CLI or admin maintenance task without locking out enrolled TOTP users or breaking SMTP/AI configs.
- `app/Contracts/Security/` interfaces (`SecretBox`, `TotpVerifier`, `PasskeyVerifier`) depend on PHP primitives only, matching ADR 0004 layer D1.

## Consequences

Positive: phishing-resistant authentication for administrators and users, unified secret encryption across all subsystems, clear session state machine, and complete ASVS 5.0 coverage for account recovery. Negative: WebAuthn CBOR/COSE verification adds test and maintenance surface, and HTTPS becomes strictly mandatory to test passkeys locally/staging (localhost is exempt per WebAuthn spec). Neutral: password-reset and security-alert flows depend on the transactional mail subsystem ([ADR 0006](0006-mail-and-smtp.md)).

## Open decisions

1. Pure-PHP minimal CBOR/COSE WebAuthn verifier versus a small audited PHP WebAuthn library.
2. Whether admin MFA is optional with a toggleable policy or mandatory on first admin login after installation.
3. Whether administrators can enforce hardware-bound (non-synced) passkeys for privileged roles.

## Approval

Proposed for `GOV-008`, `GOV-011` and `SEC-001`–`SEC-007`. Requires independent maintainer and security review, recorded by setting `Status:` to `accepted` with approver and date before `SEC-001` implementation begins.
