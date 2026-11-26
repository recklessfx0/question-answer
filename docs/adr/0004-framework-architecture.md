# ADR: Core framework architecture and module boundaries
Status: proposed

## Context

[ARCHITECTURE](../ARCHITECTURE.md) outlines a request flow, directory layout and a rule to approve public contracts before parallel coding (`GOV-004`, `GOV-011`), while ADRs [0001](0001-localization.md), [0002](0002-seo-and-agent-representations.md) and [0003](0003-performance-architecture.md) specify localization, SEO/agent representations and performance compilation mechanics. What is still missing is the unifying framework architecture that defines exact dependency rules between `Contracts`, `Core`, `Modules` and `Extensions`, how build-time compilation fits with the request lifecycle, how Web/REST/MCP/CLI share one authorization and projection spine, and which contract packages must be frozen before `v0.1` (`CORE-001`–`CORE-013`, `DB-001`–`DB-005`, `CACHE-001`–`CACHE-007`) starts in parallel.

Without an explicit boundary model, modular monoliths collapse into cross-module coupling, framework classes leak CMS domain assumptions, and REST/MCP surfaces duplicate or bypass web authorization and visibility rules.

Constraints: PHP 8.5 strict types on ordinary shared hosting, no mandatory Node/Redis/APCu/Docker/SSH/worker daemon, release ZIP deployment, OWASP ASVS 5.0 L2+ controls ([SECURITY_REQUIREMENTS](../SECURITY_REQUIREMENTS.md)), and the stage CPU budgets in [FRAMEWORK_PERFORMANCE](../FRAMEWORK_PERFORMANCE.md).

## Decision

**D1 — Four unidirectional dependency layers.** `app/Contracts` depends on nothing internal; `app/Core` depends only on `app/Contracts`; `app/Modules/*` depend only on `app/Contracts` (never on `app/Core` concrete classes and never on another module's internals); `extensions/*` depend only on `app/Contracts`. Enforced in CI by static architecture boundary checks.

**D2 — Dual-phase compiled lifecycle.** Discovery, configuration merging, dependency graph validation, route-table generation, event listener mapping and template compilation execute at build, deploy, install, update or extension-activation time via `ArtifactCompiler`, writing atomic PHP artifacts to `storage/compiled/`. Request execution performs zero reflection and zero directory scanning.

**D3 — Pre-boot L1 page cache fast path.** `public/index.php` checks L1 public page cache for anonymous `GET`/`HEAD` requests before container boot, emitting cached bytes with security headers and `ETag`/`304` handling. Authenticated or personalized traffic never enters shared caches.

**D4 — Explicit compiled middleware pipeline.** Each route group (`web`, `public-artifact`, `api`, `mcp`, `admin`, `installer`) runs a deterministic, compiled middleware stack with fixed security ordering: correlation/timing → error handling & redaction → security headers → request limits → path normalization → locale allow-list → static routing → rate limiting → authentication → CSRF → cache control → controller dispatch.

**D5 — Multi-surface application spine.** Web HTML, `.md` Markdown, feeds, sitemaps, REST `/api/v1`, MCP and CLI are thin adapters over shared Application Services. Authorization always runs at the object level inside the application layer; API/MCP token scopes only constrain permissions and never grant access on their own (`SEC-B-02`).

**D6 — CQRS-lite read/write separation.** Write commands execute through repositories inside explicit `PDO` transactions (`ATTR_EMULATE_PREPARES => false`), enforcing domain invariants and emitting `ResourceChange` events to `InvalidationBus`. Read paths consume cached Read-Model Projections shared across HTML, Markdown, JSON-LD, feeds and sitemaps (`PERF-004`).

**D7 — Security primitives as core framework boundaries.** Contextual output escaping (`html`, `attr`, `url`, `jsonLd` + typed `SafeHtml`), centralized logger secret redaction (`SecretRedactor`), and a single SSRF-safe `OutboundHttpClient` (DNS resolution check, private/metadata IP block, redirect re-validation, size/time bounds) are core framework contracts, not per-module conventions.

**D8 — Shared declarative module model.** Core modules (`app/Modules/*`) and third-party extensions (`extensions/*`) register routes, services, listeners, migrations, templates and slots through the same declarative `ModuleInterface`/manifest compiled into `storage/compiled/`. A `config/safe_mode.lock` recovery flag boots core-only compiled artifacts if an extension faults.

**D9 — Bounded opportunistic job execution.** Background work (sitemaps, feeds, IndexNow, cleanup, updates) queues in a database-backed `jobs` table and runs via optional cron or a strictly time-boxed, single-flight opportunistic runner on hosts without cron.

**D10 — PSR interoperability baseline.** Adopt PSR-4 (autoloading), PSR-3 (logging), PSR-11 (container), PSR-14 (events), PSR-16 (cache base extended with tags/locks) and PSR-20 (clock). HTTP message contract choice (native PSR-7/15 vs. lean PHP 8.5 `readonly` types with an optional PSR-7 bridge) is recorded as an explicit open decision for `GOV-011`.

## Alternatives

- **Adopting a full-stack third-party framework (Laravel, Symfony full-stack).** Faster initial scaffolding, but violates the charter's lightweight own-framework requirement, pulls large dependency trees onto shared hosting, and couples extension contracts to an external vendor's release cycle. Rejected (aligned with ADR 0003).
- **Flat MVC structure without module boundaries.** Simpler initially, but invites circular dependencies across Q&A, users, SEO, localization, API and MCP, making parallel workstreams and future extraction/maintenance fragile. Rejected in favour of D1.
- **Runtime service discovery and dynamic reflection container.** Avoids a compile step, but violates shared-hosting CPU budgets (`≤ 20 ms` uncached) and pushes wiring errors from deploy time into production requests. Rejected in favour of D2.
- **Separate codebases or controllers with duplicated auth/query logic for REST and MCP.** Fast to prototype, but creates authorization drift and public-artifact leakage risks (`SEC-B-01`, `SEC-D-04`). Rejected in favour of D5.
- **Mandatory Redis or daemon worker for queues and caching.** Simplifies async work, but disqualifies ordinary shared hosting. Rejected in favour of atomic file cache + D9.

## Security and performance implications

- **Boundary enforcement reduces blast radius:** Isolating `app/Core` from `app/Modules` and requiring `Authorizer`, `OutboundHttpClient`, `Escaper` and `SecretRedactor` at the contract layer makes secure paths the default and auditable in one place.
- **Visibility-safe invalidation:** Any transition from public to private/moderated/deleted must invalidate L1/L2/L3 caches synchronously without stale-while-revalidate (`SEC-D-04`).
- **Compiler as a privileged surface:** `ArtifactCompiler` writes executable PHP into `storage/compiled/`; compilation must only run from trusted build, CLI, installer, updater or administrator-approved extension lifecycle paths — never from unauthenticated requests (`B7`, `B9`).
- **Performance predictability:** Pre-boot L1 hits return before container creation; uncached misses use OPcache-backed compiled arrays and projections with per-route query budgets asserted in CI.

## Shared hosting implications

- Release ZIPs ship pre-built `storage/compiled/` artifacts and a classmap-authoritative autoloader, requiring zero Composer or CLI access on the target host.
- `storage/compiled/` and `storage/cache/` stay outside `public/` with restrictive permissions (`0750`/`0640`).
- Atomic staging-directory swaps prevent partial writes on slow shared filesystems.
- Cron is optional; opportunistic job execution keeps periodic tasks bounded within standard PHP `max_execution_time` limits.

## Compatibility and migration

- No runtime code exists yet; adopting this blueprint before `CORE-001` avoids retrofitting module boundaries, compiled containers and multi-surface services later.
- Existing drafted contracts (`app/Contracts/Localization/*`, `app/Contracts/Seo/*`) already follow D1 (primitives and allow-listed tags only, zero `app/Core` dependencies) and remain unchanged.
- Versioning of `app/Contracts` begins at v1.0; breaking changes before v1.0 require an ADR update and affected workstream sign-off.

## Consequences

Positive: clear ownership for parallel `v0.1`–`v0.6` workstreams, enforceable framework/CMS separation, one authorization and projection spine across Web/REST/MCP, shared-hosting viability without sacrificing sub-20ms uncached budgets, and safe-mode recovery for extensions. Negative: upfront investment in `ArtifactCompiler` and architecture boundary tests, and stricter discipline when modules need cross-cutting data. Neutral: dev mode needs an ergonomic auto-recompile trigger so the compile step does not slow contributors down.

## Open decisions for approval

To be resolved when accepting this ADR under `GOV-004` and `GOV-011`:

1. **HTTP messages:** Minimal PSR-7/PSR-15 directly in `app/Contracts/Http` vs. lean PHP 8.5 `readonly` `Request`/`Response` with an optional PSR-7 bridge.
2. **Template engine:** Restricted PHP template dialect with static checks vs. lightweight custom auto-escaping template compiler.
3. **Document blocks:** Validated JSON block storage (+ plain-text search column) vs. strict CommonMark parsed to cached blocks on write.
4. **Dev compilation:** Always run compiled artifacts (auto-recompiled in dev) vs. dynamic uncompiled container in dev.
5. **Runtime dependencies:** PSR interface packages + PHP 8.5 built-ins only vs. allowing specific audited libraries (e.g., CommonMark or HTML sanitizer).

## Approval

Proposed for `GOV-004` (architecture and module boundaries) and `GOV-011` (public framework contracts). Requires independent maintainer approval, recorded by changing `Status:` to `accepted` with approver and date, and resolution of the five open decisions above before `CORE-001` begins. D1, D2 and D5 are the most expensive decisions to reverse and are the primary review focus.
