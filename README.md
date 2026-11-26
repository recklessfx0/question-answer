# QuestionAnswer

**Open-source knowledge infrastructure for humans and AI.**

QuestionAnswer is a planned self-hosted Q&A CMS with a lightweight compiled PHP 8.5 framework, nested questions, answers and clarifications, mobile-first progressive enhancement, extensions, REST and MCP — engineered to run reliably on ordinary shared hosting.

> [!NOTE]
> **Status: pre-alpha architecture and contracts foundation.** No CMS runtime is implemented yet; do not deploy this repository as a CMS. Today it contains the governance and security baseline, 6 architectural specifications and proposed ADRs, all 17 `app/Contracts/` packages (`61` PHP 8.5 interfaces & immutable DTOs), the full `Core`/`Modules`/`config`/`resources/lang/en` scaffold, 8 Colnera-ready GitHub Spec Kit milestones (`159` tasks), and automated CI/security/boundary checks.

---

## Key goals & hosting baseline

- **PHP 8.5 strict types (`~8.5.0`)** with MySQL 8.4 LTS and MariaDB 10.11 LTS.
- **Ordinary shared hosting first:** no mandatory Node, Redis, APCu, Docker, SSH, or persistent worker daemon on production. Installable release ZIP, browser wizard with ownership proof, and signed atomic updater with backup/recovery.
- **Compiled modular monolith:** four unidirectional layers (`Contracts` / `Core` / `Modules` / `Extensions`) with deploy-time compiled container, route table, config, events and templates — zero runtime reflection or directory scanning.
- **Shared-hosting performance budgets:** `≤ 1.5 ms` cached CPU, `≤ 20 ms` uncached CPU, `≤ 8` queries, and `≤ 8 MB` memory on stock OPcache; Lighthouse 100 in four categories is an aspirational reference-environment target, not a guarantee.
- **Modern security & communications:** OWASP ASVS 5.0 L2+ target, three-state sessions (`anonymous` / `pending_mfa` / `authenticated`), step-up re-authentication, RFC 6238 TOTP, WebAuthn/FIDO2 passkeys & hardware keys, versioned AEAD `SecretBox`, and queued authenticated TLS/STARTTLS SMTP with optional DKIM.
- **Built-in SEO & agent readability:** one structured document model rendered to server-rendered HTML, `.md` Markdown, `llms.txt`, JSON-LD (`QAPage`), Atom 1.0 / JSON Feed 1.1, streamed gzip XML sitemaps, and 1200×630 social cards — never scraping HTML.
- **Multilingual by design:** allow-listed BCP 47 locales, OPcache-friendly PHP-array ICU catalogues, translation groups with four-eyes review, RTL/bidi safety, and data-only language packs.
- **Optional AI & MCP:** zero external AI required for baseline installs; public read-only MCP before scoped mutations.

---

## Architecture & ADRs

All ADRs are currently `Status: proposed` pending `M0` maintainer approval (`GOV-004`, `GOV-008`, `GOV-011`).

| Area | Specification | ADR | Highlights |
| --- | --- | --- | --- |
| **Core Framework** | [FRAMEWORK_ARCHITECTURE.md](docs/FRAMEWORK_ARCHITECTURE.md) | [ADR 0004](docs/adr/0004-framework-architecture.md) | 4 unidirectional layers, dual-phase build/request lifecycle, pre-boot L1 cache, 12-stage middleware pipeline, shared Web/REST/MCP/CLI spine |
| **Performance** | [FRAMEWORK_PERFORMANCE.md](docs/FRAMEWORK_PERFORMANCE.md) & [PERFORMANCE.md](docs/PERFORMANCE.md) | [ADR 0003](docs/adr/0003-performance-architecture.md) | Compiled artifacts, shared read-model projections, 6-layer cache (`L0`–`L5`), invalidation bus, single-flight stampede locks, CI benchmark gates |
| **Localization (i18n)** | [I18N.md](docs/I18N.md) | [ADR 0001](docs/adr/0001-localization.md) | BCP 47 allow-list, PHP-array ICU catalogues, unprefixed default + prefixed alternates, translation groups & stale detection, XLIFF 2.0, data-only packs |
| **SEO & Agent Views** | [SEO.md](docs/SEO.md) | [ADR 0002](docs/adr/0002-seo-and-agent-representations.md) | Single block model -> HTML, `.md`, JSON-LD, Atom/JSON feeds, streamed sitemaps, `llms.txt`, social cards, public-only leakage boundary |
| **Auth, 2FA & Passkeys** | [AUTH.md](docs/AUTH.md) | [ADR 0005](docs/adr/0005-authentication-mfa-passkeys.md) | Three-state sessions, step-up sudo, TOTP with replay floor, WebAuthn/FIDO2 passkeys (`ES256`/`EdDSA`/`RS256`), hashed recovery/reset tokens, `SecretBox` AEAD |
| **Mail & Secure SMTP** | [MAIL.md](docs/MAIL.md) | [ADR 0006](docs/adr/0006-mail-and-smtp.md) | `Mailer`/`MailTransport` contracts, implicit TLS `465` / mandatory `STARTTLS` `587`, CRLF & SSRF guards, optional DKIM, prioritized async DB queue |

Additional foundation docs: [PROJECT_CHARTER.md](docs/PROJECT_CHARTER.md) · [ARCHITECTURE.md](docs/ARCHITECTURE.md) · [DESIGN_SYSTEM.md](docs/DESIGN_SYSTEM.md) · [EXTENSIONS.md](docs/EXTENSIONS.md) · [API_MCP_AI.md](docs/API_MCP_AI.md) · [INSTALL_UPDATE.md](docs/INSTALL_UPDATE.md) · [ROADMAP.md](docs/ROADMAP.md) · [TESTING.md](docs/TESTING.md).

---

## Repository structure

```text
app/
  Contracts/                   # All 17 public contract packages (61 PHP 8.5 files, zero internal deps)
    Auth/ Cache/ Config/ Container/ Content/ Database/ Events/ Extension/
    Http/ Jobs/ Localization/ Logging/ Mail/ Rendering/ Security/ Seo/ Validation/
  Core/                        # 17 planned framework subsystem directories (depends only on Contracts)
  Modules/                     # 15 planned CMS domain & delivery modules (depend only on Contracts)
config/                        # Baseline PHP config templates compiled at deploy/install time
resources/
  lang/en/                     # Default English ICU catalogues (default, admin, validation, mail, installer, mcp)
  views/ assets/               # Planned SSR templates and native CSS/JS/SVG assets
public/                        # Web root (public/assets/ only; private files stay outside)
specs/                         # 8 GitHub Spec Kit milestones (T001–T159) for Colnera Feature 065
docs/                          # Architecture, security, performance, i18n, SEO, auth, mail & ADRs 0001–0006
backlog/                       # Legacy structured JSON/CSV backlog & dependency export (153 tasks)
tests/                         # Unit, Contract, Architecture, Integration, Security, Performance, E2E
tools/                         # Validators (validate_architecture.py, validate_specs.py, validate_backlog.py)
.github/workflows/             # Foundation checks (ci.yml) and Security checks (security.yml)
```

---

## Contributing with Colnera & AI agents

QuestionAnswer is structured as a **Colnera-coordinated showcase repository** (see [docs/COLNERA.md](docs/COLNERA.md)):

- **Tasks & reviews live in Colnera:** Colnera's GitHub App reads pull requests, CI check statuses, and basic repository metadata. Contributors submit work as pull requests; reviewers see live GitHub Actions pass/fail checks before accepting work and issuing rewards (simulated on demo environments).
- **MCP agent coordination:** Each contributor's AI agent connects to Colnera's MCP server with a project- or task-scoped token using `get_project`, `list_briefs`, `list_tasks`, `get_task`, `claim_task`, `release_task`, `submit_work`, and `list_pull_requests`.
- **Local agent instructions:** Coding agents read [AGENTS.md](AGENTS.md) (and [CLAUDE.md](CLAUDE.md)) directly on the contributor's machine for repository conventions, security rules, and local test commands.
- **Maintainer setup:** Install the Colnera GitHub App, link the repo to your Colnera project, and protect `main` with required status checks. See [docs/GITHUB_SETUP.md](docs/GITHUB_SETUP.md) and [CONTRIBUTING.md](CONTRIBUTING.md).

### GitHub Spec Kit milestones (`specs/`) — ready for Colnera Feature 065

Each `specs/NNN-name/` directory maps to a Colnera milestone and each unticked `- [ ] TXXX` item in `tasks.md` imports as a draft task with indented description bullets and `**Needs:**` dependencies:

| Milestone Folder | Scope | Tasks |
| --- | --- | --- |
| [001-m0-governance-and-contracts](specs/001-m0-governance-and-contracts/tasks.md) | Charter, governance, ADRs `0001`–`0006`, public contracts | `T001`–`T015` (15) |
| [002-v01-core-framework-data-and-cache](specs/002-v01-core-framework-data-and-cache/tasks.md) | Compiled core framework, PDO/migrations, logging, cache, core i18n, benchmarks | `T016`–`T050` (35) |
| [003-v02-security-auth-mail-and-qa-core](specs/003-v02-security-auth-mail-and-qa-core/tasks.md) | Auth, 2FA/TOTP, WebAuthn passkeys, `SecretBox`, TLS SMTP mail, Q&A core & projections | `T051`–`T081` (31) |
| [004-v03-public-ui-admin-search-and-seo](specs/004-v03-public-ui-admin-search-and-seo/tasks.md) | Search, accessible SSR UI, Admin panel, SEO/sitemaps/feeds/Markdown/`llms.txt` | `T082`–`T111` (30) |
| [005-v04-extensions-and-rest-api](specs/005-v04-extensions-and-rest-api/tasks.md) | Extension SDK/lifecycle, language packs, cost gate, REST `/api/v1` | `T112`–`T127` (16) |
| [006-v05-installer-and-updater](specs/006-v05-installer-and-updater/tasks.md) | Shared-hosting browser installer, release ZIP, signed atomic updater | `T128`–`T140` (13) |
| [007-v06-optional-ai-and-mcp](specs/007-v06-optional-ai-and-mcp/tasks.md) | Optional AI drafts/citations, read-only & scoped-write MCP server | `T141`–`T152` (12) |
| [008-v10-release-readiness-and-audit](specs/008-v10-release-readiness-and-audit/tasks.md) | Cross-DB integration, security/a11y/SEO/i18n/Lighthouse audits, signed release | `T153`–`T159` (7) |

---

## Local checks & CI

Every pull request runs two GitHub Actions workflows with read-only default permissions and full 40-character commit-SHA action pins:

- **Foundation checks** ([ci.yml](.github/workflows/ci.yml)): backlog dependency validation (`tools/validate_backlog.py`), Spec Kit milestone & dependency validation (`tools/validate_specs.py`), four-layer architecture & PSR-4 boundary validation (`tools/validate_architecture.py`), PHP 8.5 syntax lint (`php -l`), and strict Composer validation/audit.
- **Security checks** ([security.yml](.github/workflows/security.yml)): `gitleaks` secret scanning, `actionlint` workflow linting, commit-SHA pinning & no-`.env` policy checks, `bandit` static analysis on `tools/`, and lockfile-gated dependency review.

Run the local checks before opening a PR:

```bash
find . -type f -name '*.php' -not -path './vendor/*' -print0 | xargs -0 -r -n1 php -l
composer validate --strict
python3 tools/validate_backlog.py
python3 tools/validate_specs.py
python3 tools/validate_architecture.py
```

---

## Security

Report vulnerabilities privately through [SECURITY.md](SECURITY.md) — never in a public issue, pull request, or task board comment.

- **Controls & negative-test gates:** [docs/SECURITY_REQUIREMENTS.md](docs/SECURITY_REQUIREMENTS.md) (`SEC-A`–`SEC-J`)
- **Trust boundaries & abuse cases:** [docs/THREAT_MODEL.md](docs/THREAT_MODEL.md) (`B1`–`B10`, 16 abuse cases)
- **Deployment baseline:** [docs/HARDENING.md](docs/HARDENING.md) (§1–§12)

## License

Apache-2.0 is the proposed project license; confirm before accepting contributions. See [LICENSE](LICENSE).
