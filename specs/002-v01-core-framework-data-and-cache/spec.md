# v0.1 — Core Framework, Data, Cache & Localization

Deliver the PHP 8.5 compiled core runtime (bootstrap, config, HTTP, compiled container/router/templates, validation, events, jobs), PDO/migrations, redacted JSON logging, atomic cache, core i18n, and CI benchmark gates.

## Milestone metadata

- **Milestone code:** `v0.1`
- **Tasks:** `T016`–`T050` (35 draft tasks in [tasks.md](tasks.md))
- **Format:** GitHub Spec Kit (ready for Colnera Feature 065 automatic import)

## Key references

- [docs/FRAMEWORK_ARCHITECTURE.md](../../docs/FRAMEWORK_ARCHITECTURE.md)
- [docs/FRAMEWORK_PERFORMANCE.md](../../docs/FRAMEWORK_PERFORMANCE.md)
- [docs/I18N.md](../../docs/I18N.md)
- [docs/TESTING.md](../../docs/TESTING.md)
- [docs/adr/0001-localization.md](../../docs/adr/0001-localization.md)
- [docs/adr/0003-performance-architecture.md](../../docs/adr/0003-performance-architecture.md)

## Verification

Before submitting a pull request for any task in this milestone, run the local checks listed in [AGENTS.md](../../AGENTS.md) (`php -l`, `composer validate --strict`, `python3 tools/validate_backlog.py`, `python3 tools/validate_specs.py`, and applicable test suites) and confirm GitHub Actions CI passes.
