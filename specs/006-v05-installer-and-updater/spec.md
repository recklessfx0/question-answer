# v0.5 — Browser Installer & Signed Atomic Updater

Deliver shared-hosting browser setup with ownership proof, private path checks, release ZIP packaging, signed update metadata, archive traversal defense, verified backups, and resumable atomic updates.

## Milestone metadata

- **Milestone code:** `v0.5`
- **Tasks:** `T128`–`T140` (13 draft tasks in [tasks.md](tasks.md))
- **Format:** GitHub Spec Kit (ready for Colnera Feature 065 automatic import)

## Key references

- [docs/INSTALL_UPDATE.md](../../docs/INSTALL_UPDATE.md)
- [docs/HARDENING.md](../../docs/HARDENING.md)
- [docs/SECURITY_REQUIREMENTS.md](../../docs/SECURITY_REQUIREMENTS.md)

## Verification

Before submitting a pull request for any task in this milestone, run the local checks listed in [AGENTS.md](../../AGENTS.md) (`php -l`, `composer validate --strict`, `python3 tools/validate_backlog.py`, `python3 tools/validate_specs.py`, and applicable test suites) and confirm GitHub Actions CI passes.
