# Hardening baseline

Operator checklist for a real deployment. The project ships secure defaults, but on shared hosting the host configuration wins: verify every line, and record deviations with the reason. Requirements are in [security requirements](SECURITY_REQUIREMENTS.md); boundaries and abuse cases in the [threat model](THREAT_MODEL.md).

## 1. Layout and permissions

- Document root is `public/` only. `app/`, `config/`, `database/`, `resources/`, `storage/`, `tests/`, `tools/` and `vendor/` sit outside it or are denied by server configuration.
- Directories `0750`, files `0640`, owned by the web user or a dedicated application user. Nothing world-writable; no `0777` anywhere.
- `.env` is `0600` and outside the document root. Never commit one; `.gitignore` already refuses it.
- `storage/` is writable by the application user only and is never served directly; downloads go through a controller that sets `Content-Disposition` and `X-Content-Type-Options: nosniff`.
- No `.git/`, editor backup, `.env`, `composer.lock` or dump file is reachable over HTTP. Confirm with an unauthenticated request for each path in §11.

## 2. PHP runtime

```ini
expose_php = Off
display_errors = Off
display_startup_errors = Off
log_errors = On
error_log = /path/outside/webroot/php-error.log
allow_url_include = Off
allow_url_fopen = Off
open_basedir = /path/to/instance:/tmp
disable_functions = exec,passthru,shell_exec,system,proc_open,popen,pcntl_exec
session.use_strict_mode = 1
session.use_only_cookies = 1
session.cookie_httponly = 1
session.cookie_secure = 1
session.cookie_samesite = Lax
session.sid_length = 48
session.sid_bits_per_character = 6
upload_max_filesize = 8M
post_max_size = 10M
max_execution_time = 30
max_input_time = 30
```

`allow_url_fopen = Off` and the `disable_functions` list break some host tooling; when a host forces them on, compensate with the application-level SSRF controls (SEC-C-05) and record the exception. `open_basedir` must include the instance root and the session/temp directories, nothing else. Keep OPcache on with `opcache.validate_timestamps=1` in production only if the deploy process does not clear it.

## 3. HTTP response baseline

Send on every response, HTML or not:

| Header | Value | Note |
| --- | --- | --- |
| `Strict-Transport-Security` | `max-age=31536000; includeSubDomains` | Only after HTTPS is proven on every hostname; add `preload` solely with a deliberate decision |
| `Content-Security-Policy` | Start `default-src 'self'; base-uri 'none'; frame-ancestors 'none'; form-action 'self'; object-src 'none'` | Add nonce-based `script-src` when inline script is needed; never `unsafe-inline` for scripts |
| `X-Content-Type-Options` | `nosniff` | |
| `Referrer-Policy` | `strict-origin-when-cross-origin` | |
| `Permissions-Policy` | `camera=(), microphone=(), geolocation=()` | Narrow per feature |
| `Cross-Origin-Opener-Policy` | `same-origin` | Optional; test with the extension UI |
| `X-Frame-Options` | omit when `frame-ancestors` is set | Do not send two conflicting directives |
| `Cache-Control` | `no-store` on authenticated HTML | `Vary: Cookie` on any personalised response |

Remove `X-Powered-By` and any framework banner. Error pages are generic in production; request IDs are fine, stack traces are not.

## 4. Cookies and sessions

- `Secure`, `HttpOnly`, `SameSite=Lax` (or `Strict` where flows permit), `__Host-` prefix where the cookie is host-scoped.
- Regenerate the session ID on privilege change; expire idle sessions; offer "sign out all devices".
- Authentication failures are rate limited per account and per IP, with a uniform response and a delay.
- MFA for administrator accounts; require re-authentication for destructive operations.

## 5. Secrets

- Per-install application key and database password are generated at install, never shipped in the archive.
- Database user: minimal grants on the application schema only; no `FILE`, `SUPER`, `PROCESS`, `GRANT OPTION`. Prefer a local socket or `127.0.0.1`.
- AI provider keys and MCP tokens are optional, stored encrypted, scoped, rotatable and revocable; never in the database plaintext when a key store is available.
- No secret in HTML, JavaScript, URLs, logs, error messages, issue trackers or backlog files. Redaction happens in the logger, not at the call site.
- Rotate on suspicion; document the rotation runbook with the deployment.

## 6. Uploads and storage

- Allow-list extensions and MIME types; verify with `finfo`, not the client header.
- Random server-side filenames, original name stored as metadata only.
- Store outside the document root; if that is impossible, disable script execution in that directory at the server level.
- Strip EXIF where metadata is not needed; bound size and count; scan or quarantine if the host offers a scanner.
- Serve through the application with `Content-Disposition: attachment` (or a safe inline type) and `nosniff`.

## 7. TLS

TLS 1.2 minimum, TLS 1.3 preferred; modern ciphers; redirect HTTP to HTTPS; no mixed content; HSTS only after the redirect is verified. Renewal must be monitored — an expired certificate is an outage and a downgrade path.

## 8. Backups

Encrypted at rest, off-site, retention-limited, never reachable by URL, restore-tested on a schedule. A backup that has never been restored is not a backup. Encrypt with a key that is not stored with the backup.

## 9. Maintenance tooling

`tools/import_issues.py` runs on a maintainer machine with an authenticated GitHub CLI, never on production and never with a production token; use `--repo` and dry-run first. `tools/validate_backlog.py` is read-only. Neither script is web-reachable.

## 10. Repository and CI

- Actions are pinned to full commit SHAs with a version comment; `security.yml` fails the build on a floating tag.
- Default token permissions are read-only; jobs request only what they need. No `pull_request_target` workflow receives secrets.
- `main` is protected: pull request required, independent review, required checks, no force push, no direct bot merge.
- Dependency alerts and security updates are on; secret scanning with push protection is on; private vulnerability reporting is on.
- Release signing keys and environment secrets live in a protected GitHub environment, not repository secrets.
- SBOM and signed artifact per release.

## 11. Verification

Run from a machine outside the host, unauthenticated:

| Check | Expected |
| --- | --- |
| `curl -sI https://host/.env`, `/.git/config`, `/composer.lock`, `/backup.sql` | `403` or `404`, never file content |
| `curl -sI https://host/` | HSTS, CSP, `nosniff`, `Referrer-Policy`, no `X-Powered-By` |
| `curl -sI http://host/` | `301` to HTTPS |
| Login form response | Cookie has `Secure`, `HttpOnly`, `SameSite`; no session ID in URL |
| Invalid login ×20 | Uniform message, rate-limited, no account enumeration |
| Upload a `.php` named `.jpg` | Refused or stored non-executable; requesting it never executes |
| `php -i \| grep -E 'display_errors\|allow_url_include\|open_basedir'` | `Off`, `Off`, set |
| Directory listing on `/storage/` | Disabled |
| Error trigger in production | Generic page, details only in the server log |

Record the results with the deployment; re-run after every host or PHP upgrade.
