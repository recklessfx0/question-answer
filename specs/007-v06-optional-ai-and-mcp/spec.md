# v0.6 — Optional AI Gateway & MCP Server

Deliver the opt-in AI provider gateway (draft answers, grounded citations, cost/privacy controls) and the official-spec MCP server (read-only tools/resources first, then scoped draft/feedback mutations and SSRF-safe outbound client).

## Milestone metadata

- **Milestone code:** `v0.6`
- **Tasks:** `T141`–`T152` (12 draft tasks in [tasks.md](tasks.md))
- **Format:** GitHub Spec Kit (ready for Colnera Feature 065 automatic import)

## Key references

- [docs/API_MCP_AI.md](../../docs/API_MCP_AI.md)
- [docs/SECURITY_REQUIREMENTS.md](../../docs/SECURITY_REQUIREMENTS.md)
- [docs/THREAT_MODEL.md](../../docs/THREAT_MODEL.md)

## Verification

Before submitting a pull request for any task in this milestone, run the local checks listed in [AGENTS.md](../../AGENTS.md) (`php -l`, `composer validate --strict`, `python3 tools/validate_backlog.py`, `python3 tools/validate_specs.py`, and applicable test suites) and confirm GitHub Actions CI passes.
