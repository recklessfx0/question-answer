# ADR: Transactional mail and secure SMTP delivery
Status: proposed

## Context

Authentication flows (email verification, password reset, email-change confirmation, MFA/passkey security alerts in [ADR 0005](0005-authentication-mfa-passkeys.md)) and Q&A notifications (`QA-010`) require reliable, secure outbound email delivery. Until now, the repository referenced a `mail` translation domain in [I18N](../I18N.md) and a lazy mailer example in [FRAMEWORK_PERFORMANCE](../FRAMEWORK_PERFORMANCE.md), but had no mail architecture, no contracts, no SMTP security requirements and no threat-model coverage for outbound email.

Relying on PHP's built-in `mail()` function on shared hosting fails both reliability and security bars: it depends on host-configured sendmail binaries, cannot authenticate cleanly to external relays, lacks strict TLS verification, and exposes classic header-injection and envelope-spoofing bugs. Conversely, requiring an external email SaaS violates the self-hosted charter.

Constraints: PHP 8.5 on ordinary shared hosting, no mandatory daemon/Redis/SaaS, OWASP ASVS 5.0 security controls, `CORE-013` DB-backed job queue, and `SecretBox` credential encryption ([ADR 0005](0005-authentication-mfa-passkeys.md)).

## Decision

**D1 — Pluggable mail contracts in `app/Contracts/Mail/`.** Define `EmailMessage` (immutable validated message), `MailTransport` (delivery backend) and `Mailer` (application entry point with `queue()` as default). Core ships `SmtpTransport`, `LogTransport` and `NullTransport`; external HTTP email providers (SES, Postmark, Mailgun, etc.) integrate as `providers` extensions behind `MailTransport`.

**D2 — Authenticated SMTP over strict TLS as baseline.** `SmtpTransport` supports implicit TLS (`smtps`, port 465) and mandatory `STARTTLS` (port 587) with `verify_peer = true`, `verify_peer_name = true` and TLS 1.2+ minimum. Silent TLS downgrade is forbidden. Plaintext is disabled by default and permitted only for an explicitly allowed loopback relay (`127.0.0.1`/`::1`) with an Admin warning.

**D3 — Header-injection and SSRF controls at the boundary (`SEC-C-09`).** `EmailMessage` rejects `\r`, `\n`, NUL and control characters in all headers and addresses and enforces a custom-header allow-list. SMTP hosts pass DNS resolution checks blocking metadata, link-local, CGNAT and private ranges unless explicitly allowed for a local host relay (`SEC-C-05`).

**D4 — Encrypted credentials and log redaction.** SMTP passwords and optional DKIM private keys are stored encrypted via `SecretBox` (`SEC-D-05`), redacted centrally by `SecretRedactor` (`SEC-D-01`), and never echoed back in Admin forms.

**D5 — Asynchronous queued delivery with priority and backoff.** Application code calls `Mailer::queue()` by default so HTTP requests never block on SMTP sockets. Security emails use `high` priority; transient `4xx`/network errors retry with bounded exponential backoff; permanent `5xx` errors fail without retrying into a redacted dead-letter view.

**D6 — Localized, context-escaped multipart templates.** Emails render both `text/plain` and context-escaped `text/html` using the `mail` i18n domain in the recipient's stored locale, with RTL direction applied where required and bidi control characters stripped.

**D7 — Anti-email-bombing rate limits and minimal sensitive payloads.** Outbound mail enforces per-recipient, per-IP and global rate caps. Auth emails carry only single-use expiring tokens and never passwords, TOTP seeds or recovery codes. Non-security notifications include signed RFC 8058 `List-Unsubscribe` headers.

**D8 — Optional built-in DKIM signing and Admin diagnostics.** Support optional RSA-2048 / Ed25519 DKIM signing via PHP `openssl`/`sodium` for shared hosts that do not sign outbound mail, plus a step-up authenticated Admin "Send test email" diagnostic tool.

## Alternatives

- **PHP `mail()` as default.** Zero configuration on some hosts, but unreliable deliverability, no TLS/AUTH control, frequent host disablement and fragile envelope handling. Rejected.
- **Require a third-party email API (SES, Postmark, Resend).** Great deliverability, but breaks self-hosted independence and forces external vendor accounts. Rejected as baseline; supported via optional `providers` extensions.
- **Synchronous inline SMTP send in web requests.** Simpler than queueing, but adds 300 ms–5 s latency to login/post/reset requests, leaks timing signals on password-reset enumeration (`SEC-A-04`), and drops mail on transient relay blips. Rejected as default.

## Security and performance implications

- **Closes SMTP abuse vectors:** Strict TLS verification prevents credential theft in transit; SSRF host checks prevent using the Admin SMTP test tool to probe internal networks or cloud metadata; CRLF checks close email header injection; rate limits prevent email bombing.
- **Timing side-channel protection:** Queuing password-reset emails keeps response times uniform between existing and non-existing accounts (`SEC-A-04`).
- **Performance:** Zero SMTP latency on the HTTP request path; `Mailer` and `SmtpTransport` remain lazy services never instantiated on read requests (`FRAMEWORK_PERFORMANCE.md`).

## Shared hosting implications

- Works with standard PHP 8.5 `openssl` socket streams on ports 465 or 587 (many shared hosts block outbound port 25, so 465/587 are the defaults).
- Hosts without cron still deliver queued mail via `CORE-013`'s bounded opportunistic post-response runner (`fastcgi_finish_request()` where available).
- Admin diagnostics surface host port blocks, TLS errors and missing SPF/DKIM/DMARC records clearly without leaking secrets.

## Compatibility and migration

- Additive contracts in `app/Contracts/Mail/` with zero internal dependencies (ADR 0004 layer D1).
- Reuses `CORE-013` job queue, `I18N` `mail` domain, `CORE-008` template escaper, `LOG-002` redactor and `SecretBox` from ADR 0005.
- New installations default to `LogTransport` until the administrator configures and tests SMTP in the installer or `ADMIN-004`.

## Consequences

Positive: reliable, standards-compliant email delivery on shared hosting, uniform response timing for auth flows, strong protection against SMTP credential/header/SSRF attacks, and clean extensibility for API mail providers. Negative: requires maintaining either a small native SMTP state machine or an audited mail dependency, plus queue visibility in Admin. Neutral: `SEC-001`/`SEC-007` and `QA-010` gain a clean dependency on `Mailer`.

## Open decisions

1. Native minimal PHP `stream_socket_client` SMTP client (zero runtime deps) versus an audited external package (`symfony/mailer` or `phpmailer`).
2. Shipping optional DKIM signing in core versus as a first-party extension.
3. Whether high-priority auth emails always queue asynchronously or may attempt one short-timeout synchronous send with automatic queue fallback when cron is unavailable.

## Approval

Proposed alongside [ADR 0004](0004-framework-architecture.md) and [ADR 0005](0005-authentication-mfa-passkeys.md) for `GOV-008` and `GOV-011`. Requires independent maintainer and security review, recorded by setting `Status:` to `accepted` with approver and date before mail implementation begins.
