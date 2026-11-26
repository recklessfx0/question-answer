# CLAUDE.md

Follow [AGENTS.md](AGENTS.md) as the authoritative local instruction set for this repository.

## Quick reference

- **Coordination:** Tasks, acceptance criteria, dependencies, and review feedback live in Colnera via MCP (`get_project`, `list_briefs`, `list_tasks`, `get_task`, `claim_task`, `release_task`, `submit_work`, `list_pull_requests`). Always call `get_task` and `claim_task` before editing.
- **Stack:** PHP 8.5 (`declare(strict_types=1);`), PSR-4 `QuestionAnswer\` -> `app/`, native HTML/CSS/JS, MySQL 8.4 / MariaDB 10.11, shared-hosting baseline (no mandatory Node/Redis/Docker/worker).
- **Boundaries:** `app/Contracts/` (no internal deps) <- `app/Core/` (no `Modules` deps) <- `app/Modules/*` & `extensions/` (depend only on `app/Contracts/`).
- **Local checks before PR:**
  - `find . -type f -name '*.php' -not -path './vendor/*' -print0 | xargs -0 -r -n1 php -l`
  - `composer validate --strict`
  - `python3 tools/validate_backlog.py`
  - `python3 tools/validate_specs.py`
- **Security:** Read [docs/SECURITY_REQUIREMENTS.md](docs/SECURITY_REQUIREMENTS.md) and [docs/THREAT_MODEL.md](docs/THREAT_MODEL.md) before touching security-sensitive surfaces; add fail-closed negative tests (`SEC-J-01`) and update the threat model in the same commit. Never self-merge or weaken `.github/` checks.
