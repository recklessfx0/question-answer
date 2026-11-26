# v0.4 — Extension SDK, Language Packs & REST API

Deliver the declarative extension lifecycle, signed package integrity, safe mode, compile-time extension cost budgets, data-only language packs, and the versioned REST /api/v1 surface with OpenAPI and signed webhooks.

## Milestone metadata

- **Milestone code:** `v0.4`
- **Tasks:** `T112`–`T127` (16 draft tasks in [tasks.md](tasks.md))
- **Format:** GitHub Spec Kit (ready for Colnera Feature 065 automatic import)

## Key references

- [docs/EXTENSIONS.md](../../docs/EXTENSIONS.md)
- [docs/API_MCP_AI.md](../../docs/API_MCP_AI.md)
- [docs/FRAMEWORK_PERFORMANCE.md](../../docs/FRAMEWORK_PERFORMANCE.md)

## Verification

Before submitting a pull request for any task in this milestone, run the local checks listed in [AGENTS.md](../../AGENTS.md) (`php -l`, `composer validate --strict`, `python3 tools/validate_backlog.py`, `python3 tools/validate_specs.py`, and applicable test suites) and confirm GitHub Actions CI passes.
