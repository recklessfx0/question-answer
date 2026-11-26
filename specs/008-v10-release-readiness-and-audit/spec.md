# v1.0 — Integration, Audits & Signed Release

Run cross-database integration tests, full security/accessibility/SEO/i18n/Lighthouse audits on shared hosting, address alpha/beta feedback, and publish the signed v1.0 release with SBOM and recovery evidence.

## Milestone metadata

- **Milestone code:** `v1.0`
- **Tasks:** `T153`–`T159` (7 draft tasks in [tasks.md](tasks.md))
- **Format:** GitHub Spec Kit (ready for Colnera Feature 065 automatic import)

## Key references

- [docs/TESTING.md](../../docs/TESTING.md)
- [docs/PERFORMANCE.md](../../docs/PERFORMANCE.md)
- [docs/HARDENING.md](../../docs/HARDENING.md)
- [docs/ROADMAP.md](../../docs/ROADMAP.md)

## Verification

Before submitting a pull request for any task in this milestone, run the local checks listed in [AGENTS.md](../../AGENTS.md) (`php -l`, `composer validate --strict`, `python3 tools/validate_backlog.py`, `python3 tools/validate_specs.py`, and applicable test suites) and confirm GitHub Actions CI passes.
