# v0.3 — Public UI, Admin, Search, SEO & Agent Representations

Deliver full-text search, accessible mobile-first SSR UI, responsive Admin panel, canonical/hreflang/robots, JSON-LD, social cards, streamed sitemaps, Atom/JSON feeds, Markdown + llms.txt, and the cross-artifact invalidation bus.

## Milestone metadata

- **Milestone code:** `v0.3`
- **Tasks:** `T082`–`T111` (30 draft tasks in [tasks.md](tasks.md))
- **Format:** GitHub Spec Kit (ready for Colnera Feature 065 automatic import)

## Key references

- [docs/DESIGN_SYSTEM.md](../../docs/DESIGN_SYSTEM.md)
- [docs/SEO.md](../../docs/SEO.md)
- [docs/I18N.md](../../docs/I18N.md)
- [docs/FRAMEWORK_PERFORMANCE.md](../../docs/FRAMEWORK_PERFORMANCE.md)
- [docs/adr/0002-seo-and-agent-representations.md](../../docs/adr/0002-seo-and-agent-representations.md)

## Verification

Before submitting a pull request for any task in this milestone, run the local checks listed in [AGENTS.md](../../AGENTS.md) (`php -l`, `composer validate --strict`, `python3 tools/validate_backlog.py`, `python3 tools/validate_specs.py`, and applicable test suites) and confirm GitHub Actions CI passes.
