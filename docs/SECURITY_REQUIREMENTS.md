# Security requirements

Target OWASP ASVS 5.0 Level 2, with selected Level 3 controls for high-risk workflows. Requirement IDs are the project's own vocabulary; the ASVS mapping is produced and evidenced during each release audit, not asserted here. A requirement is met only when the evidence column exists and the negative test fails closed. Threat boundaries are in the [threat model](THREAT_MODEL.md); operator settings in [hardening](HARDENING.md).

Gates: **Merge** — required before code lands on `main`. **Release** — required before any published release. **Milestone** — required at the named milestone gate.

## A. Authentication and sessions

| ID | Requirement | Evidence | Gate |
| --- | --- | --- | --- |
| SEC-A-01 | Passwords hashed with `password_hash`, Argon2id where the runtime provides it, with a documented fallback and rehash-on-login. | Unit test plus stored-hash inspection. | Merge |
| SEC-A-02 | Session IDs regenerated on login and privilege change; idle and absolute timeouts enforced server-side. | Integration test of session lifecycle. | Merge |
| SEC-A-03 | Session cookies `Secure`, `HttpOnly`, `SameSite`, `__Host-` prefixed where host-scoped. | Header assertion in an HTTP test. | Merge |
| SEC-A-04 | Uniform authentication failure responses with per-account and per-IP throttling; no account enumeration by message, status or timing. | Negative test driving repeated failures. | Release |
| SEC-A-05 | MFA available for administrator accounts and required for destructive operations; re-authentication before sensitive changes. | Flow test with and without the second factor. | Release |
| SEC-A-06 | CSRF tokens validated on every state-changing request, including JSON and MCP-initiated mutations. | Negative test without a token. | Merge |

## B. Authorization

| ID | Requirement | Evidence | Gate |
| --- | --- | --- | --- |
| SEC-B-01 | Every object access checks the authenticated actor's permission on that object server-side; no client-supplied role or owner field is trusted. | Matrix test across roles and objects. | Merge |
| SEC-B-02 | API and MCP scopes constrain an authorization decision but never replace it; resource-level checks run regardless of scope. | Test where a valid broad scope is refused on an unauthorized object. | Merge |
| SEC-B-03 | Identifier enumeration does not disclose existence, ownership or content differences. | Response-comparison test. | Release |
| SEC-B-04 | Administrative operations require an administrator session and are independently audited. | Audit-record test. | Release |

## C. Input, output and egress

| ID | Requirement | Evidence | Gate |
| --- | --- | --- | --- |
| SEC-C-01 | All SQL goes through prepared statements with bound parameters; no string interpolation of values into queries. | Static check plus injection attempt in a test. | Merge |
| SEC-C-02 | Output is escaped contextually (HTML, attribute, URL, JavaScript, JSON); no contextual substitution. | XSS payload suite per sink. | Merge |
| SEC-C-03 | CSP without inline or `eval` script; no `unsafe-inline` for script sources. | Header assertion plus rendered-page check. | Release |
| SEC-C-04 | Uploads validated by allow-list and `finfo`, stored non-executable outside the document root, served with `nosniff`. | Malicious-upload test. | Release |
| SEC-C-05 | Server-side requests accept only allow-listed schemes and hosts; resolved IPs are checked against loopback, private, link-local, CGNAT and metadata ranges at dial time and again on every redirect; responses are size- and time-bounded. | SSRF test against a metadata address and a redirect pivot. | Release |
| SEC-C-06 | Request bodies, headers, upload sizes and pagination bounds are capped; malformed input is rejected before work is done. | Boundary tests at each limit. | Merge |
| SEC-C-07 | Locale input is validated against an installed allow-list before use in a path, cache key, header or catalogue load; `Accept-Language` parsing is bounded and rejects control characters; catalogue values are escaped at output and never evaluated. | Negative tests for unknown locale, traversal-shaped tag and CRLF header; bidi control characters stripped from input and imports. | Merge |
| SEC-C-08 | Structured data and XML artifacts are encoded for their serialization context: JSON-LD cannot be terminated by user text, sitemaps and feeds are written through an XML writer with no DTD or external entities, and Markdown drops raw HTML and restricts link schemes. | Breakout tests with `</script>` and entity payloads in titles and answers; XML validation of generated artifacts. | Merge |

## D. Data, privacy and logging

| ID | Requirement | Evidence | Gate |
| --- | --- | --- | --- |
| SEC-D-01 | Tokens, passwords, session IDs and sensitive payloads are redacted in logs, error pages and telemetry; production errors are generic. | Log-capture test with a seeded secret. | Merge |
| SEC-D-02 | Personal data retention, export and deletion are implemented per content type, and exports are scoped to the requester. | Export/deletion test. | Release |
| SEC-D-03 | Publication requires an editorial verification state and preserves the revision history with author and time. | State-machine test. | Milestone v0.3 |
| SEC-D-04 | Public artifacts — sitemaps, feeds, JSON-LD, Markdown and `llms.txt` — contain only published, publicly visible content and are built from the same authorization-filtered query as the HTML page. | Leakage tests over drafts, private questions and moderated content in every artifact. | Release |

## E. AI and MCP

| ID | Requirement | Evidence | Gate |
| --- | --- | --- | --- |
| SEC-E-01 | Retrieved content and model output are treated as untrusted data: they can never authorize an operation, select a privileged tool, or change scope. | Prompt-injection test that attempts a state change and is refused. | Merge |
| SEC-E-02 | Any tool call or mutation proposed by AI requires an authenticated actor's explicit intent and passes the same authorization as a direct request. | End-to-end test with an authorized and an unauthorized actor. | Release |
| SEC-E-03 | AI provider credentials are encrypted at rest, scoped, revocable and never sent to the model or written to logs. | Secret-scanning test plus redaction assertion. | Release |
| SEC-E-04 | MCP servers enforce token expiry, per-token scopes and revocation; public access is read-only until scoped mutation is designed and audited. | Token lifecycle test. | Milestone v0.6 |
| SEC-E-05 | Outbound calls to provider base URLs pass the SSRF controls; private endpoints require explicit configuration. | Configuration test with a private address refused by default. | Milestone v0.6 |

## F. Extensions

| ID | Requirement | Evidence | Gate |
| --- | --- | --- | --- |
| SEC-F-01 | Extension installation is administrator-only, requires explicit approval, and displays the archive contents and signature state before activation. | Install-path test. | Milestone v0.4 |
| SEC-F-02 | Extensions are documented as trusted executable PHP with application privileges; the manifest grants no sandbox and no privilege reduction is claimed. | Documentation review plus ADR. | Milestone v0.4 |
| SEC-F-03 | Extensions cannot self-update or fetch code at runtime; updates follow the signed installer path. | Runtime network-denial test for the extension API. | Milestone v0.4 |

## G. Install and update integrity

| ID | Requirement | Evidence | Gate |
| --- | --- | --- | --- |
| SEC-G-01 | Update metadata and artifacts are signed; keys are pinned, rotation is documented, and an unknown key fails closed. | Signature failure tests. | Milestone v0.5 |
| SEC-G-02 | Replay and downgrade are refused; version monotonicity is enforced. | Old-version-replay test. | Milestone v0.5 |
| SEC-G-03 | Archive entries cannot traverse outside the target root: `..`, absolute paths, symlinks and hard links are refused. | Traversal fixture suite. | Milestone v0.5 |
| SEC-G-04 | Updates apply atomically to a staged copy with a maintenance lock, journal, health check and bounded resumable execution. | Interrupted-update recovery test. | Milestone v0.5 |
| SEC-G-05 | Installation requires privileged authorization and integrity verification before writing. | Unprivileged-install refusal test. | Milestone v0.5 |
| SEC-G-06 | Database migrations document reversibility or recovery; irreversible steps are named in release notes. | Migration review record. | Release |

## H. Supply chain and CI

| ID | Requirement | Evidence | Gate |
| --- | --- | --- | --- |
| SEC-H-01 | Every GitHub Action is pinned to a full commit SHA; a floating tag fails the policy job. | `security.yml` policy job. | Merge |
| SEC-H-02 | Workflow token permissions are read-only by default and scoped per job; no workflow receives secrets on a fork pull request. | Workflow review plus settings default. | Merge |
| SEC-H-03 | Changes under `.github/` require CODEOWNERS review and are never self-approved. | Branch protection configuration. | Release |
| SEC-H-04 | Dependencies are exact where possible, locked where the ecosystem allows, reviewed on addition, and audited in CI; each release carries an SBOM. | `composer audit` job plus SBOM artifact. | Release |
| SEC-H-05 | Secret scanning with push protection and private vulnerability reporting are enabled on the repository. | Repository settings record. | Release |

## I. Operations and hosting

| ID | Requirement | Evidence | Gate |
| --- | --- | --- | --- |
| SEC-I-01 | The deployment baseline in [hardening](HARDENING.md) is applied: document root, permissions, `open_basedir`, disabled functions, error handling. | Signed checklist per environment. | Release |
| SEC-I-02 | The database account holds least privilege and the database is not reachable from the public internet. | Grant listing. | Release |
| SEC-I-03 | Backups are encrypted, off-site, retention-limited and restore-tested. | Restore test record. | Release |
| SEC-I-04 | Health checks, log redaction and alerting exist before the first release. | Monitoring configuration. | Release |

## J. Verification and gates

| ID | Requirement | Evidence | Gate |
| --- | --- | --- | --- |
| SEC-J-01 | Every security control above has at least one negative test that fails closed. | Test suite mapping table. | Merge |
| SEC-J-02 | Security-sensitive changes carry an impact statement in the pull request and, when a boundary moves, a threat-model update. | Pull request record. | Merge |
| SEC-J-03 | Release requires no unresolved high or critical security finding, passing gates, a signed artifact and maintainer approval. | Release checklist. | Release |
| SEC-J-04 | Findings are tracked privately until fixed, then disclosed per [security policy](../SECURITY.md). | Advisory record. | Release |

Secure defaults and negative tests are mandatory, not optional. A requirement waived for a release is recorded with the reason, the owner and the expiry, and expires at the next milestone.
