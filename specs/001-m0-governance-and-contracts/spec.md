# M0 — Governance, Architecture & Public Contracts

Approve charter, license, governance, hosting baseline, security requirements, ADRs 0001–0006, and public framework contracts before parallel implementation starts.

## Milestone metadata

- **Milestone code:** `M0`
- **Tasks:** `T001`–`T015` (15 draft tasks in [tasks.md](tasks.md))
- **Format:** GitHub Spec Kit (ready for Colnera Feature 065 automatic import)

## Key references

- [docs/PROJECT_CHARTER.md](../../docs/PROJECT_CHARTER.md)
- [docs/ARCHITECTURE.md](../../docs/ARCHITECTURE.md)
- [docs/FRAMEWORK_ARCHITECTURE.md](../../docs/FRAMEWORK_ARCHITECTURE.md)
- [docs/SECURITY_REQUIREMENTS.md](../../docs/SECURITY_REQUIREMENTS.md)
- [docs/THREAT_MODEL.md](../../docs/THREAT_MODEL.md)
- [docs/adr/0004-framework-architecture.md](../../docs/adr/0004-framework-architecture.md)

## Verification

Before submitting a pull request for any task in this milestone, run the local checks listed in [AGENTS.md](../../AGENTS.md) (`php -l`, `composer validate --strict`, `python3 tools/validate_backlog.py`, `python3 tools/validate_specs.py`, and applicable test suites) and confirm GitHub Actions CI passes.
