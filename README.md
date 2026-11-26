# QuestionAnswer

**Open-source knowledge infrastructure for humans and AI.**

QuestionAnswer is a planned self-hosted Q&A CMS with a lightweight PHP 8.5 framework, nested questions, answers and clarifications, mobile-first progressive enhancement, extensions, REST and MCP. It is designed for ordinary shared hosting.

> Status: pre-alpha planning repository. No CMS runtime is implemented yet. Do not deploy this repository as a CMS.

## Contributing via Colnera and GitHub

Read [project charter](docs/PROJECT_CHARTER.md), [architecture](docs/ARCHITECTURE.md), [framework architecture](docs/FRAMEWORK_ARCHITECTURE.md), [AGENTS.md](AGENTS.md), [contribution guide](CONTRIBUTING.md) and [roadmap](docs/ROADMAP.md).

- **Task coordination:** Tasks, acceptance criteria, dependencies, and reviews live on the **Colnera** project board. Contributors and AI agents connect to Colnera's MCP server (`get_project`, `list_tasks`, `get_task`, `claim_task`, `release_task`, `submit_work`, `list_pull_requests`, `list_briefs`), implement in an isolated branch, and open a GitHub PR with passing CI checks. See [Colnera integration](docs/COLNERA.md).
- **Local agent instructions:** Coding agents read [AGENTS.md](AGENTS.md) (and [CLAUDE.md](CLAUDE.md)) locally on the contributor's machine for repo conventions, security rules, and test commands.
- **GitHub Spec Kit (`specs/`):** Milestones and draft tasks are structured in [`specs/001-*`..`specs/008-*`](specs/001-m0-governance-and-contracts/tasks.md) for automatic import via Colnera Feature 065 and validated on every PR by `tools/validate_specs.py`.

## Maintainer setup

Link the GitHub repository to a Colnera project via the Colnera GitHub App, configure branch protection and required CI status checks, and import milestones from `specs/` (or optionally mirror backlog issues via `tools/import_issues.py --help`). See [GitHub setup](docs/GITHUB_SETUP.md) and [Colnera guide](docs/COLNERA.md).

## Goals

PHP 8.5; HTML/CSS/JS; no mandatory Node, Redis, Docker, SSH or worker on production; installable ZIP; browser installer and recoverable updater; phishing-resistant 2FA/passkeys and queued TLS SMTP; optional AI; public read-only MCP before scoped mutations. Lighthouse 100 in four categories is an aspirational reference-environment target, not a guarantee.

## Architecture and ADRs

- [Framework architecture](docs/FRAMEWORK_ARCHITECTURE.md) & [ADR 0004](docs/adr/0004-framework-architecture.md) — four unidirectional layers (`Contracts` / `Core` / `Modules` / `Extensions`) and deploy-time compilation
- [Localization](docs/I18N.md) & [ADR 0001](docs/adr/0001-localization.md) — allow-listed BCP 47 locales, PHP-array ICU catalogues, translation groups, RTL/bidi safety
- [SEO & agent representations](docs/SEO.md) & [ADR 0002](docs/adr/0002-seo-and-agent-representations.md) — one document model rendered to HTML, `.md`, JSON-LD, Atom/JSON feeds, streamed XML sitemaps, and `llms.txt`
- [Framework performance](docs/FRAMEWORK_PERFORMANCE.md) & [ADR 0003](docs/adr/0003-performance-architecture.md) — stage CPU budgets, read-model projections, 6-layer cache, invalidation bus
- [Authentication, 2FA & passkeys](docs/AUTH.md) & [ADR 0005](docs/adr/0005-authentication-mfa-passkeys.md) — three-state sessions, TOTP, WebAuthn/FIDO2 passkeys, recovery codes, `SecretBox` AEAD
- [Transactional mail & SMTP](docs/MAIL.md) & [ADR 0006](docs/adr/0006-mail-and-smtp.md) — authenticated TLS/STARTTLS SMTP, header/SSRF protection, optional DKIM, queued delivery

## Security

Report vulnerabilities privately through [the security policy](SECURITY.md) — never in a public issue. Requirements are in [security requirements](docs/SECURITY_REQUIREMENTS.md), trust boundaries and abuse cases in the [threat model](docs/THREAT_MODEL.md), and operator settings in the [hardening baseline](docs/HARDENING.md). CI runs PHP 8.5 lint, Composer validation, backlog & Spec Kit validation, secret scanning, workflow lint, action-pin policy, dependency review, and Python static analysis; every third-party action is pinned to a commit SHA.

## License

Apache-2.0 is the proposed project license; confirm before accepting contributions. See [LICENSE](LICENSE).
