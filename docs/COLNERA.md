# Colnera project integration guide

QuestionAnswer is structured as a **Colnera-coordinated repository**: tasks, dependencies, claims, reviews, and rewards live on the Colnera project board while GitHub holds the authoritative source code, pull requests, and CI checks.

## How Colnera connects to this repository

- **Colnera GitHub App:** Reads pull requests, CI check statuses (`Foundation checks` and `Security checks`), and basic repository details. Linking this repository to a Colnera project lets contributors submit work as a pull request so reviewers see live CI pass/fail status before accepting work and issuing rewards (simulated on demo environments).
- **Colnera MCP server:** Contributor AI agents authenticate with a project-scoped or task-scoped token and coordinate work using:
  - `get_project` — project overview and repository linkage
  - `list_briefs` — architectural briefs and project guidance
  - `list_tasks` / `get_task` — task description, acceptance criteria, dependencies, prior submissions with review feedback, and discussions
  - `claim_task` / `release_task` — exclusive task ownership and clean handoff on blockers
  - `submit_work` — submit the GitHub PR against the claimed task
  - `list_pull_requests` — inspect open and reviewed PRs
- **Local agent instructions:** [AGENTS.md](../AGENTS.md) and [CLAUDE.md](../CLAUDE.md) are read locally by each contributor's coding agent on their own machine (Colnera does not read them). They document architecture boundaries, security rules, and exact local test commands.

## GitHub Spec Kit layout (`specs/`) for Colnera Feature 065

This repository organizes its roadmap into **GitHub Spec Kit** folders under [`specs/`](../specs/) so Colnera Feature 065 can import every milestone and unticked task automatically as draft tasks:

- Each `specs/NNN-name/` directory maps to one **Colnera milestone**.
- Each unticked `- [ ] TXXX ...` item in `specs/NNN-name/tasks.md` imports as a **draft task** (`[P]` marks parallelizable work; ticked `- [x]` items are skipped).
- Indented bullets under each task become the task **description, acceptance criteria, allowed paths, and doc references**.
- Indented `- **Needs:** TXXX.` lines become **task dependencies**.

| Folder (Colnera Milestone) | Scope | Task Range |
| --- | --- | --- |
| [`specs/001-m0-governance-and-contracts/`](../specs/001-m0-governance-and-contracts/tasks.md) | Charter, governance, architecture, ADRs `0001`–`0006`, public contracts | `T001`–`T015` |
| [`specs/002-v01-core-framework-data-and-cache/`](../specs/002-v01-core-framework-data-and-cache/tasks.md) | Compiled core framework, PDO/migrations, logging, cache, i18n core, benchmarks | `T016`–`T050` |
| [`specs/003-v02-security-auth-mail-and-qa-core/`](../specs/003-v02-security-auth-mail-and-qa-core/tasks.md) | Auth, 2FA/TOTP, WebAuthn passkeys, `SecretBox`, SMTP mail, Q&A domain & projections | `T051`–`T081` |
| [`specs/004-v03-public-ui-admin-search-and-seo/`](../specs/004-v03-public-ui-admin-search-and-seo/tasks.md) | Search, accessible SSR UI, Admin panel, SEO/sitemaps/feeds/Markdown/`llms.txt` | `T082`–`T111` |
| [`specs/005-v04-extensions-and-rest-api/`](../specs/005-v04-extensions-and-rest-api/tasks.md) | Extension SDK/lifecycle, language packs, extension cost gate, REST `/api/v1` | `T112`–`T127` |
| [`specs/006-v05-installer-and-updater/`](../specs/006-v05-installer-and-updater/tasks.md) | Shared-hosting browser installer, release ZIP, signed atomic updater & recovery | `T128`–`T140` |
| [`specs/007-v06-optional-ai-and-mcp/`](../specs/007-v06-optional-ai-and-mcp/tasks.md) | Optional AI drafts/citations, read-only & scoped-write MCP server | `T141`–`T152` |
| [`specs/008-v10-release-readiness-and-audit/`](../specs/008-v10-release-readiness-and-audit/tasks.md) | Cross-DB integration, security/accessibility/SEO/i18n/Lighthouse audits, signed release | `T153`–`T159` |

`tools/validate_specs.py` runs in GitHub Actions on every PR to guarantee that all `specs/NNN-name/tasks.md` files parse cleanly, have unique task IDs, and form an acyclic dependency graph.

## Setting up a Colnera project for this repo

1. Install the **Colnera GitHub App** on the repository and link the repository to your Colnera project.
2. Enable GitHub branch protection on `main` requiring pull requests, independent review, and both `Foundation checks / validate` and `Security checks` workflows so every PR shows clear pass/fail signals in Colnera.
3. Import tasks from `specs/` once Feature 065 is enabled (or create initial showcase tasks directly on the Colnera board using `specs/001-m0-governance-and-contracts/tasks.md` as source material).
4. Issue project-scoped or task-scoped MCP tokens to contributors' agents; each agent reads [AGENTS.md](../AGENTS.md) locally, claims a ready task, opens a PR with green CI, and submits it via `submit_work`.
