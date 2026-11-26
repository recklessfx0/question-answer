# Authentication, 2FA, passkeys and account recovery

Design for user authentication, two-factor authentication (TOTP), phishing-resistant passkeys (WebAuthn/FIDO2), step-up re-authentication, recovery codes, account lifecycle tokens and authenticated secret encryption at rest. Provisional until [ADR 0005](adr/0005-authentication-mfa-passkeys.md) is approved; expands `SEC-001`, `SEC-002` and `SEC-007`. Security gates are in [security requirements](SECURITY_REQUIREMENTS.md) (`SEC-A-01`–`SEC-A-08`, `SEC-D-05`), threat boundaries in [threat model](THREAT_MODEL.md), and mail delivery in [mail](MAIL.md).

## Principles

1. **Phishing-resistant first for privileged actors.** Administrators and editors can register WebAuthn passkeys or hardware security keys; policy can enforce MFA by role.
2. **Every user may enable 2FA.** TOTP and passkeys are available to all accounts, not restricted to administrators.
3. **Fail-closed two-stage login.** A password-verified session awaiting a second factor is strictly quarantined in a `pending_mfa` state and can access only the MFA verification and logout endpoints.
4. **Encrypted secrets, hashed tokens.** Long-lived secrets that must be read back (TOTP seeds, SMTP passwords, AI keys, webhook secrets) are encrypted with versioned AEAD (`SecretBox`). Verification tokens and recovery codes are stored as one-way hashes only.
5. **Zero enumeration.** Login, password reset, email verification resend and MFA errors return uniform responses and timing regardless of whether an account exists (`SEC-A-04`).
6. **Zero mandatory external services.** Password hashing, TOTP, WebAuthn verification and AEAD encryption run natively on PHP 8.5 (`password_hash`, `hash_hmac`, `openssl`, `sodium`) without SaaS identity providers.

## Authentication methods

### 1. Password authentication (`SEC-A-01`, `SEC-A-04`)

- Hashed via `password_hash()` using `PASSWORD_ARGON2ID` with calibrated memory/time/thread parameters (documented fallback to `PASSWORD_BCRYPT` only when a host lacks Argon2id, reported in diagnostics) and automatic `password_needs_rehash()` upgrade on login.
- Policy follows OWASP ASVS 5.0: minimum length 12 characters (up to at least 128 characters allowed), full Unicode supported, no forced arbitrary composition rules, and rejection of passwords matching a bundled top-breached list or the account's email/handle.
- Per-account and per-IP rate limiting with progressive backoff and uniform failure messages.

### 2. Two-factor authentication — TOTP (`SEC-A-05`, `SEC-007`)

- **Standard:** RFC 6238 TOTP, SHA-1 (for universal authenticator compatibility) with 6 digits and a 30-second period, 160-bit minimum random secret generated via `random_bytes()`.
- **Storage:** Raw TOTP seed is encrypted at rest via `SecretBox` (bound with associated data `totp:{user_id}`) and never written to logs, sessions or HTML after enrollment confirmation.
- **Enrollment:** User scans QR / enters manual key and must confirm one valid code before TOTP becomes active. Recovery codes are generated and shown once on activation.
- **Replay & drift bounds:** Verification accepts at most `±1` time step (`±30 s` clock skew) and records `last_used_time_step` per credential; any code whose time step is `≤ last_used_time_step` is rejected to prevent replay within the validity window.
- **Throttling:** Strict per-user failure cap on the MFA challenge endpoint; exhausting attempts invalidates the `pending_mfa` session and forces a fresh primary login.

### 3. Passkeys and WebAuthn / FIDO2 (`SEC-A-07`)

Supports both **discoverable passkeys** (passwordless primary sign-in with user verification) and **WebAuthn security keys** (phishing-resistant second factor):

- **Challenge lifecycle:** Cryptographically random ≥32-byte challenge stored server-side in session with a short TTL (≤ 5 minutes), bound to the ceremony type (`registration` or `authentication`) and consumed atomically on first attempt (single-use).
- **RP ID & Origin checks:** Strictly validates `rpIdHash` against the configured canonical host and verifies `clientDataJSON.origin` matches the exact HTTPS origin (`https://host[:port]`) and `type` matches `webauthn.create` or `webauthn.get`. Works identically under subdirectory installations because WebAuthn scopes to origin/RP ID, not path.
- **Algorithms:** Supports COSE `ES256` (-7, P-256), `EdDSA` (-8, Ed25519 via `sodium`), and `RS256` (-257) using PHP 8.5 built-in `openssl`/`sodium`.
- **Attestation:** Defaults to `attestation: "none"` for user privacy and zero external CA dependencies.
- **Stored credential fields:** `id` (raw credential ID, unique), `user_id`, `public_key_cose`, `algorithm`, `sign_count`, `transports`, `backup_eligible`, `backup_state`, `user_verified_last`, `label`, `created_at`, `last_used_at`.
- **Clone detection:** When a non-zero `sign_count` is present on both stored and asserted credentials and `asserted <= stored`, authentication fails closed, the credential is flagged, an audit record is written, and the user is notified via [mail](MAIL.md).
- **User verification:** Discoverable passwordless login requires `UV` (User Verified) flag; 2FA security-key assertion requires `UP` (User Present) and prefers `UV`.
- **Multi-credential & management:** Users can register multiple passkeys (e.g., phone + hardware key), rename them, view last-used timestamps, and revoke individual keys after step-up re-authentication.

### 4. Recovery codes (`SEC-A-05`, `SEC-007`)

- Generated as 8–10 high-entropy codes (≥ 64 bits of entropy each, human-readable grouped base32/hex).
- Stored strictly as **one-way hashes** (Argon2id or keyed HMAC-SHA256 with per-code salt/ID) — never plaintext.
- Each code is single-use and marked consumed atomically inside a transaction.
- Using a recovery code writes an audit entry and sends a security notification email.
- Regenerating codes requires step-up re-authentication and invalidates all previous unused codes.

## Session states and step-up re-authentication (`SEC-A-02`, `SEC-A-05`)

Sessions carry explicit state rather than a single boolean `user_id`:

| State | Allowed operations | Transitions |
| --- | --- | --- |
| `anonymous` | Public read routes, login, registration, password reset | → `pending_mfa` or `authenticated` (with session ID regeneration) |
| `pending_mfa` | MFA verify (`TOTP`, `WebAuthn`, `recovery_code`) and logout **only**; short TTL (≤ 5 min) | → `authenticated` (with session ID regeneration) or destroyed on failure/expiry |
| `authenticated` | Normal authorized routes according to RBAC and object policies | → `authenticated + sudo_until` after step-up |

### Step-up ("sudo") re-authentication

Destructive and security-sensitive operations require a fresh proof of presence within a bounded window (default 15 minutes), verified via passkey, TOTP, or current password (plus active MFA if enrolled):

- Changing password or email address
- Adding, renaming or removing passkeys, TOTP or recovery codes
- Installing, updating or activating extensions or core updates
- Changing roles, permissions, SMTP settings, AI keys or signing keys
- Exporting or deleting an account

Every session record is tracked server-side with `last_seen_at`, `idle_expires_at`, `absolute_expires_at`, `user_agent_summary` and `ip_prefix`, enabling a **"Sign out all other devices"** action that revokes all other sessions immediately.

## Account lifecycle and email-backed flows (`SEC-A-08`)

All email-backed security flows dispatch through the transactional mail pipeline ([MAIL](MAIL.md)) and share strict token hygiene:

- **Token format:** ≥ 256 bits from `random_bytes()`, URL-safe encoded in the link, stored in the database strictly as `hash('sha256', $token)` (never plaintext).
- **Single-use & TTL:** Consumed atomically in a transaction; expired tokens fail closed. Issuing a new token for the same `(user_id, purpose)` invalidates prior unused tokens.
- **Email verification:** Required before posting or privileged actions (configurable policy); TTL 24 hours; resend is rate-limited and uniform.
- **Password reset:** TTL 30 minutes; request endpoint returns a uniform response whether or not the email exists (`SEC-A-04`); completing a reset hashes the new password, revokes all active sessions, rotates remember-me/API session state if bound, does **not** disable MFA, and emails a confirmation alert.
- **Email address change:** Requires step-up re-authentication, verifies ownership of the **new** address via single-use token (TTL 30 minutes), and immediately sends a security alert to the **old** address.
- **Security alert emails:** Sent (queued, localized in the recipient's locale) on password change, email change, passkey added/removed, TOTP enabled/disabled, recovery code used, and passkey clone-detection alert.

## Authenticated secret encryption at rest (`SecretBox`, `SEC-D-05`)

Rather than implementing ad-hoc encryption only for AI provider keys (`SEC-E-03`), the core framework provides one `SecretBox` contract backed by `sodium_crypto_aead_xchacha20poly1305_ietf_*`:

- **Scope:** Protects TOTP seeds, SMTP credentials, AI provider API keys, webhook signing secrets and external integration secrets.
- **Key versioning & rotation:** Ciphertext envelope stores `key_id` + nonce + ciphertext + tag, allowing online key rotation without downtime (`APP_KEY` + retired decrypt-only keys during rotation).
- **Associated data (AAD):** Every encryption call binds immutable context (e.g. `purpose:entity_id`) so a ciphertext copied from one row or field cannot be decrypted under another.

## CSRF boundary clarification (`SEC-A-06`)

- **Cookie-authenticated requests** (HTML forms, `fetch`/XHR JSON endpoints, cookie-backed admin and browser-initiated MCP calls) require a valid synchronizer CSRF token plus `Origin`/`Sec-Fetch-Site` checks.
- **Stateless token-authenticated API/MCP requests** (`Authorization: Bearer ...` where session cookies are explicitly ignored/rejected) are exempt from cookie CSRF tokens and instead enforce token validation, scope intersection (`SEC-B-02`) and object-level authorization.

## Contracts

Interfaces under `app/Contracts/Security/` (zero internal framework dependencies):

- `SecretBox` — authenticated encrypt/decrypt with context AAD and key rotation support.
- `TotpVerifier` — secret generation, `otpauth://` provisioning URI builder, bounded-window verification with replay guard.
- `PasskeyVerifier` — WebAuthn registration and assertion challenge creation and cryptographic verification.

## Security and testing gates

Every control carries fail-closed negative tests (`SEC-J-01`):

- `pending_mfa` session denied on every authenticated web, admin and JSON route.
- TOTP replay of the same code within 30 seconds rejected; out-of-window code rejected; rate limit terminates `pending_mfa`.
- WebAuthn wrong origin, wrong RP ID, replayed challenge, expired challenge, missing `UV` on passwordless login, and regressed `sign_count` all rejected.
- Consumed or tampered recovery code rejected; recovery code table contains no plaintext codes.
- Expired, reused or wrong-purpose password-reset / email-change tokens rejected; password-reset form returns uniform status/body for existing vs. unknown emails.
- `SecretBox` rejects tampered ciphertext and mismatched AAD context.

## Open decisions

Recorded in [ADR 0005](adr/0005-authentication-mfa-passkeys.md):

1. **WebAuthn library vs. built-in verifier:** Pure-PHP CBOR/COSE implementation (ES256/EdDSA/RS256 via `openssl`/`sodium`, zero dependencies) versus an audited minimal PHP WebAuthn package.
2. **Role MFA enforcement default:** Optional-at-install with a one-click "Require MFA for admins/editors" policy versus mandatory on first admin login after setup.
3. **Passkey backup/synced key policy:** Allow synced passkeys (iCloud/Google/1Password) for all roles including admins, or allow admins to restrict admin MFA to hardware-bound (`backup_eligible = false`) keys.
