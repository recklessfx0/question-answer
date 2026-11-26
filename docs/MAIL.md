# Transactional mail and secure SMTP delivery

Design for outbound email delivery across authentication, security alerts, editorial workflows and user notifications: `Mailer` and `MailTransport` contracts, native authenticated SMTP with strict TLS, header-injection and SSRF protections, encrypted credentials, optional DKIM signing, asynchronous queued delivery, rate limits, localized templates and admin diagnostics. Provisional until [ADR 0006](adr/0006-mail-and-smtp.md) is approved. Security gates are in [security requirements](SECURITY_REQUIREMENTS.md) (`SEC-C-09`, `SEC-D-05`), threat boundaries in [threat model](THREAT_MODEL.md), localization rules in [I18N](I18N.md), and job scheduling in [framework architecture](FRAMEWORK_ARCHITECTURE.md).

## Principles

1. **Never rely on PHP `mail()` as the default.** Shared-host `mail()` lacks reliable authentication, masks delivery failures, risks envelope-sender spoofing and frequently lands in spam. Authenticated SMTP over TLS is the baseline transport.
2. **Fail closed on TLS and headers.** Invalid TLS certificates, unexpected TLS downgrades and `CR`/`LF` control bytes in any header or address are rejected before bytes leave the server (`SEC-C-09`).
3. **Never block HTTP requests on SMTP.** Every email is rendered, validated and persisted to the database job queue (`CORE-013`) so web requests return immediately and transient SMTP outages retry cleanly.
4. **Recipient locale, context-escaped templates.** Emails render multipart `text/plain` + `text/html` using the `mail` translation domain in the **recipient's stored locale**, never the triggering actor's locale.
5. **Minimal sensitive data in mail.** Emails never contain passwords, TOTP seeds, recovery codes or full private question bodies; links carry single-use expiring tokens only (`SEC-A-08`).
6. **Pluggable without lock-in.** Core ships native SMTP plus dev/test `Log` and `Null` transports; HTTP API providers (SES, Postmark, Mailgun, Resend, Brevo) may plug in as `providers` extensions behind `MailTransport`.

## Delivery pipeline

```
Application Service (Auth / Moderation / QA Notification)
        │
        ├──► Mailer::queue(EmailMessage)   [Validates headers, recipient locale, rate limit]
        │         └──► JobQueue (DB jobs table, encrypted or minimal payload)
        │                   │
        │                   ▼  (Cron or bounded opportunistic runner)
        │         MailDeliveryJob ──► TemplateEngine (mail domain: text/plain + text/html)
        │                   │         └──► Optional DKIM signer (RSA/Ed25519 via openssl)
        │                   ▼
        │         MailTransport::send()
        │                   ├── SmtpTransport (Implicit TLS 465 / Mandatory STARTTLS 587)
        │                   ├── Extension HTTP Provider (via OutboundHttpClient SSRF guard)
        │                   └── LogTransport / NullTransport (dev & tests)
```

## Native SMTP transport (`SmtpTransport`)

Designed for ordinary PHP 8.5 shared hosting with zero mandatory external daemon:

- **Encryption modes:**
  - `smtps` (implicit TLS, default port `465`) — preferred.
  - `starttls` (explicit `STARTTLS` upgrade, port `587`) — **mandatory**: if the server does not advertise `STARTTLS` or the TLS handshake fails, the connection aborts immediately (no silent plaintext fallback).
  - Plaintext SMTP (`none`) is disabled by default and permitted **only** when explicitly configured for a local relay (`127.0.0.1`/`::1`) with a visible warning in Admin diagnostics.
- **Certificate verification:** Enforces `verify_peer = true`, `verify_peer_name = true`, TLS 1.2 minimum (TLS 1.3 preferred), and system CA bundle. Certificate pinning or self-signed bypass is never enabled silently.
- **Authentication:** Supports `AUTH PLAIN`, `AUTH LOGIN` and `AUTH CRAM-MD5` (over TLS only). SMTP credentials are stored encrypted at rest via `SecretBox` (`SEC-D-05`), redacted from all logs and stack traces (`SEC-D-01`), and never re-displayed in Admin HTML forms.
- **Timeouts & bounds:** Strict connect timeout (≤ 10 s), command/read timeout (≤ 15 s), bounded SMTP greeting/response line reads, and strict attachment/body size caps.
- **SSRF protection (`B6` / `SEC-C-05`, `SEC-C-09`):** Configuring an SMTP host resolves DNS and rejects cloud metadata, link-local, CGNAT and private ranges unless the operator explicitly enables `allow_local_smtp_relay` in host config (e.g., `127.0.0.1` for shared-host local MSA).

## Message construction and header safety

- **Strict RFC 5322 validation:** `EmailMessage` rejects any `\r`, `\n`, NUL or C0 control characters in `From`, `To`, `Cc`, `Bcc`, `Reply-To`, `Subject`, display names and custom header names/values before queueing and again before serialization.
- **Allowed headers allow-list:** Custom headers are restricted to an explicit allow-list (`List-Unsubscribe`, `List-Unsubscribe-Post`, `Auto-Submitted`, `Message-ID`, `In-Reply-To`, `References`, `X-Auto-Response-Suppress`).
- **MIME encoding:** Subjects and display names use RFC 2047 UTF-8 Base64/Q-encoding; bodies use `multipart/alternative` (`text/plain; charset=utf-8` + `text/html; charset=utf-8`) with quoted-printable/base64 transfer encoding.
- **Contextual HTML escaping:** HTML email templates pass through the same compiled `Escaper` as web templates (`SEC-C-02`); user-authored snippets (question titles, display names) are always escaped and stripped of bidi control characters (`SEC-C-07`).

## Deliverability and email authentication

- **Envelope alignment:** `From` address and envelope `MAIL FROM` (`Return-Path`) use a configured site domain address, never a user-supplied email address (prevents SPF/DMARC spoofing failures).
- **Optional built-in DKIM signing:** When enabled by an administrator, signs outbound messages with `rsa-sha256` (2048-bit minimum) or `ed25519-sha256` using PHP `openssl`/`sodium`, signing `From`, `To`, `Subject`, `Date`, `Message-ID`, `MIME-Version`, `Content-Type` and body hash (`relaxed/relaxed`). The private DKIM key is stored encrypted via `SecretBox` (`SEC-D-05`), and Admin displays the exact DNS TXT record to publish.
- **List-Unsubscribe:** Non-security notification emails (answer alerts, digests, mentions) include one-click signed `List-Unsubscribe` / `List-Unsubscribe-Post` (RFC 8058) headers and `Auto-Submitted: auto-generated`; security-critical emails (password reset, MFA alerts, email change) omit unsubscribe headers.

## Queued delivery, retries and anti-abuse

- **Queue separation & priority:** Security-critical emails (password reset, verification, MFA alerts) enqueue at `high` priority ahead of routine Q&A notifications (`normal`) and digests (`low`).
- **Retry policy:** Transient `4xx` SMTP responses and network timeouts retry with bounded exponential backoff (e.g. 1 min, 5 min, 15 min, 60 min — max 5 attempts). Permanent `5xx` responses fail immediately without retrying and record a redacted failure event.
- **Dead-letter handling:** Exhausted jobs remain visible in Admin diagnostics (recipient masked or restricted to admins, error code/category preserved, sensitive tokens stripped or never stored in plaintext in the job row).
- **Anti-email-bombing limits:** Per-recipient, per-IP and global per-minute rate limits cap outbound messages (especially unauthenticated flows like password reset and registration verification).
- **PII & token retention:** Queued auth-email payloads purge immediately on delivery or expiry; mail delivery logs record message ID, template key, status, latency and SMTP response code — never reset URLs or message bodies.

## Localization (`I18N.md`)

- All email subjects, headings, button labels and footers resolve through `Translator` using the `mail` domain (`resources/lang/{locale}/mail.php`).
- Direction (`ltr`/`rtl`) is set on the HTML email root according to the recipient's locale.
- Fallback follows the bounded chain in [ADR 0001](adr/0001-localization.md).

## Administration and diagnostics (`ADMIN-004`, `ADMIN-005`)

The Admin Mail panel provides:

- Transport selection (`SMTP`, installed provider extension, or `Log` in non-production), host, port, encryption mode (`smtps` / `starttls`), username, write-only password field, `From` name/address and optional `Reply-To`.
- Optional DKIM key generator / selector configuration with copyable DNS TXT record.
- **"Send test email"** button (step-up authenticated, rate-limited) returning clear, credential-redacted connection/TLS/auth diagnostics.
- Queue health: pending count, failed/dead-letter count, last successful delivery timestamp, and SPF/DKIM/DMARC setup checklist.

## Contracts

Interfaces under `app/Contracts/Mail/` (zero internal framework dependencies):

- `EmailMessage` — immutable, validated mail message value object (rejects header injection at construction).
- `MailTransport` — low-level transport boundary (`send(EmailMessage $message): MailReceipt`), implemented by `SmtpTransport`, `LogTransport`, `NullTransport` and extension API providers.
- `Mailer` — application-facing contract (`queue(...)` default, plus `sendNow(...)` for explicit admin test-send only).

## Security and testing gates

Every mail security control carries fail-closed negative tests (`SEC-J-01`):

- `CR`/`LF`/NUL injection in `To`, `From`, `Reply-To`, `Subject` and custom headers rejected at `EmailMessage` construction.
- `STARTTLS` server omitting `STARTTLS` capability or presenting an invalid/mismatched certificate fails closed without transmitting credentials or body.
- SMTP host pointing to cloud metadata (`169.254.169.254`) or private/loopback IP rejected by default (`SEC-C-05`, `SEC-C-09`).
- Seeded SMTP password and auth reset tokens never appear in logs, error pages or dead-letter summaries (`SEC-D-01`).
- Per-recipient and per-IP rate limits block email-bombing bursts.
- Localized HTML email escapes XSS payloads in user-supplied question titles and display names (`SEC-C-02`).

## Open decisions

Recorded in [ADR 0006](adr/0006-mail-and-smtp.md):

1. **SMTP engine:** Lean native PHP `stream_socket_client` SMTP client (zero external dependencies) versus reusing an audited minimal library (`symfony/mailer` or `phpmailer`).
2. **Built-in DKIM scope:** Ship optional DKIM signing in core `v0.2`/`v0.3` or defer DKIM signing to a core-maintained extension.
3. **Sync fallback when cron is absent:** Whether security-critical auth emails (password reset / verification) may attempt one short-timeout immediate send with queue fallback on failure, or always go strictly through the opportunistic post-response queue.
