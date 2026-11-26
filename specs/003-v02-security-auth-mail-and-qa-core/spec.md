# v0.2 — Security, Auth/2FA/Passkeys, SMTP Mail & Q&A Core

Deliver authentication, sessions, RBAC, CSRF/headers, rate limits, upload guards, SecretBox AEAD encryption, TOTP 2FA, WebAuthn/FIDO2 passkeys, queued TLS SMTP mail, and the core Q&A content model with shared read projections.

## Milestone metadata

- **Milestone code:** `v0.2`
- **Tasks:** `T051`–`T081` (31 draft tasks in [tasks.md](tasks.md))
- **Format:** GitHub Spec Kit (ready for Colnera Feature 065 automatic import)

## Key references

- [docs/AUTH.md](../../docs/AUTH.md)
- [docs/MAIL.md](../../docs/MAIL.md)
- [docs/SECURITY_REQUIREMENTS.md](../../docs/SECURITY_REQUIREMENTS.md)
- [docs/THREAT_MODEL.md](../../docs/THREAT_MODEL.md)
- [docs/adr/0005-authentication-mfa-passkeys.md](../../docs/adr/0005-authentication-mfa-passkeys.md)
- [docs/adr/0006-mail-and-smtp.md](../../docs/adr/0006-mail-and-smtp.md)

## Verification

Before submitting a pull request for any task in this milestone, run the local checks listed in [AGENTS.md](../../AGENTS.md) (`php -l`, `composer validate --strict`, `python3 tools/validate_backlog.py`, `python3 tools/validate_specs.py`, and applicable test suites) and confirm GitHub Actions CI passes.
