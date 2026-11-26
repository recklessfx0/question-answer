# Threat model

Living document. Required before implementation of any security-relevant feature; reviewed at every milestone gate (M0, v0.2, v0.4, v0.5, v0.6, v1.0) and after any change to a trust boundary. A pull request that adds an entry point, a credential, an outbound request, a file write outside `storage/` or a new trust boundary updates this file in the same change. Controls are enumerated in [security requirements](SECURITY_REQUIREMENTS.md); the operator baseline is [hardening](HARDENING.md).

## Assets

| Asset | Why it matters | Impact if lost or corrupted |
| --- | --- | --- |
| Published Q&A, evidence and citations | The product's only output; readers and AI agents rely on it | Misinformation, reputational damage, poisoned downstream answers |
| Drafts, revisions and editorial approvals | Provenance of what was published and by whom | Lost accountability, inability to retract |
| Accounts, sessions and credentials | Access to authoring and administration | Full content and configuration compromise |
| Personal data (accounts, private questions, exports) | Legal and ethical obligation | Privacy breach, regulatory exposure |
| Extension code | Executes with application privileges | Remote code execution, data exfiltration |
| Secrets: database credentials, app key, AI provider keys, MCP tokens | Grant direct access to systems | Pivot to host, provider billing abuse, impersonation |
| Backups and exports | Full copy of the instance | Total disclosure if leaked |
| Update artifacts and signing keys | Trust anchor for every installation | Mass compromise of all deployments |
| Audit and error logs | Detection and forensics | Undetected intrusion, leaked payloads |

## Actors

Anonymous reader or scraper · registered user · editor · administrator · extension author · MCP client or integrated application · AI provider and the model output it returns · supply-chain attacker (dependency, action, update feed) · neighbouring tenant on a shared host · malicious or careless insider with maintainer rights.

## Trust boundaries

| # | Boundary | Untrusted side | Threats | Required control |
| --- | --- | --- | --- | --- |
| B1 | Browser → application | Every request byte: path, query, body, headers, cookies, uploads, JSON | Injection, XSS, CSRF, parameter tampering, oversized payloads | Validate at the boundary, encode at the sink, server-side authorization (SEC-C, SEC-B) |
| B2 | Editor/admin UI → privileged operations | Same as B1 plus the operator's browser session | Session theft, CSRF, replayed admin actions | HttpOnly/SameSite cookies, CSRF tokens, re-authentication for destructive actions (SEC-A) |
| B3 | API/MCP client → knowledge services | Tokens, scopes, requested resource IDs | Scope confusion, IDOR, token replay | Resource-level authorization on every object; scopes constrain, never authorize (SEC-B) |
| B4 | Core → extension code | Extension PHP | Privilege escalation to core, hidden self-update, data exfiltration | Extensions are trusted executable code, installed only by an administrator after review and integrity verification; no sandbox claimed (SEC-F) |
| B5 | Application → AI provider | Prompts, retrieved content, model output | Prompt injection, secret leakage into prompts, model output treated as authority | AI output and retrieved content are untrusted data; no tool call or state change without explicit actor authorization (SEC-E) |
| B6 | Application → outbound network | URLs from users, imports, webhooks, provider base URLs | SSRF to metadata, loopback and private ranges; redirect pivots | Scheme allow-list, resolve-then-check, block loopback/private/link-local/CGNAT ranges at dial time and on redirects (SEC-C) |
| B7 | Distributor → installer/updater | Update archives, metadata, mirrors | Archive traversal, symlink escape, replay, downgrade, forged signature | Signed metadata and artifacts, pinned keys with rotation, canonical path checks, downgrade protection, atomic staged apply (SEC-G) |
| B8 | Repository → CI | Pull-request code, workflow edits, dependency manifests | Token exfiltration, workflow injection, unpinned action compromise | Least-privilege `permissions`, commit-pinned actions, no `pull_request_target` with secrets, environment approval for release (SEC-H) |
| B9 | Application → filesystem and database | Paths, filenames, SQL parameters | Path traversal, executable uploads, SQL injection, world-readable secrets | Canonical path checks, storage outside the web root, non-executable upload directory, parameterized queries, `0600` secrets (SEC-C, SEC-I) |

## Abuse cases

1. **Content as instruction.** A published answer, imported record or evidence snippet tells the assistant to perform an action. Control: provenance labels on retrieved text, AI output marked untrusted, tool execution requires an authenticated actor's explicit intent and authorization (SEC-E-01…03).
2. **Scope used as authorization.** A read-only MCP token requests a mutation, or a valid scope is replayed against another tenant's resource. Control: object-level checks independent of scopes (SEC-B-02).
3. **Extension install as RCE.** A crafted extension archive writes outside its directory or ships an autoloader that loads attacker code. Control: administrator-only install, archive path validation, diff shown before activation, no self-update channel (SEC-F-01…03).
4. **Archive traversal at install or update.** `../`, absolute paths, symlinks or hard links escape the extraction root. Control: entry-by-entry validation against a canonical root, symlink refusal (SEC-G-03).
5. **SSRF from import or fetch.** A URL points at cloud metadata, the host's admin panel or a shared-host neighbour. Control: deny non-HTTP schemes and non-public addresses at resolve and dial time, re-validate redirects, bound response size and time (SEC-C-05).
6. **Stored XSS through authoring fields.** An editor or contributor submits markup that executes in an administrator's session. Control: contextual escaping, CSP without inline script, `HttpOnly` cookies (SEC-C-02, SEC-C-03).
7. **Export and backup leakage.** An export includes another user's private question or a backup is reachable by URL. Control: scope exports to the requester, encrypt backups at rest, keep them outside the web root (SEC-D-02, SEC-I-03).
8. **Shared-host encroachment.** Another account on the same host reads `config/`, `storage/` or the database credentials through loose permissions or path traversal. Control: `open_basedir`, `0750`/`0640` permissions, credentials outside the document root (SEC-I-01).
9. **CI as an exfiltration path.** A pull request edits a workflow to print a secret or uses a floating action tag that resolves to compromised code. Control: read-only default permissions, commit-pinned actions, no secrets on fork PRs, required review for `.github/` (SEC-H-01…03).
10. **Dependency substitution.** A package typo-squat or a compromised release enters through Composer or a CI action. Control: lockfile, exact versions, review of new dependencies, SBOM per release (SEC-H-04).
11. **Answer poisoning.** An unverified contributor publishes plausible but wrong content that agents then cite. Control: editorial verification state, evidence requirement, revision audit trail (SEC-D-03).
12. **Secret echoes.** Tokens, passwords or prompt payloads appear in logs, error pages or stack traces. Control: redaction at the logger, generic production error pages (SEC-D-01).
13. **Translation poisoning and direction spoofing.** A machine translation is published as editorial fact, a malicious language pack overrides a security-relevant message, or bidi override characters make one string render as another in an administrator's session. Control: four-eyes publication with machine-draft labelling and disclosure, catalogue precedence with signed packs, escaped output, and bidi control stripping on input and import (SEC-D-03, SEC-C-07, SEC-C-02).
14. **Artifact leakage and SEO poisoning.** A sitemap, feed, JSON-LD graph, Markdown response or `llms.txt` exposes a draft, a private question or another user's content; or the site marks up content that the page does not show in order to win rich results. Control: every artifact is built from the same authorization-filtered query as the HTML, structured data is asserted against visible content, and leakage tests run in CI (SEC-D-04, SEC-C-08).

## Assumptions and non-goals

- One deployment serves one organisation; multi-tenant isolation is not claimed unless a later ADR introduces it.
- Extensions are trusted code, not a sandbox boundary.
- The operator is responsible for TLS termination, host patching, database hardening and filesystem permissions; the project ships defaults and a checklist.
- No protection is claimed against a compromised host, a malicious administrator or physical access.
- The project conducts no offensive testing; it only defends its own surface.

## Review triggers

New entry point or route · new credential or token type · new outbound request path · new file write or upload path · any change under `.github/` · any change to extension loading, installer or updater · any change to how AI or MCP output is consumed · new locale, catalogue or translation path · new public representation, feed or sitemap route · new third-party dependency · first public release.
