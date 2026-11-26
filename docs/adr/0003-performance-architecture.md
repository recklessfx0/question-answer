# ADR: Performance architecture
Status: proposed

## Context

The project has performance *budgets* and no performance *mechanism*. [PERFORMANCE](../PERFORMANCE.md) states user-facing targets (LCP, INP, cached PHP ≤20 ms, uncached ≤150 ms) but the architecture has no compiled-artifact strategy, no shared cache-key discipline, no invalidation story across the artifacts that localization and SEO added, and no gate that fails a regression. The framework tasks (`CORE-001`…`CORE-013`, `CACHE-001`…`CACHE-007`, `DB-001`…`DB-005`) each solve a subsystem without stating how the request path stays cheap.

Meanwhile the platform constraints are unusually sharp. PHP is request-per-process on ordinary shared hosting, so anything paid at boot is paid on every request. JIT is disabled by default since PHP 8.4 and rarely helps short request lives; preloading requires a persistent process, php.ini access and is unsupported on Windows, which rules it out as a baseline. The SEO work (HTML, Markdown, JSON-LD, Atom/JSON, sitemaps, `llms.txt`) and localization work (catalogues, per-locale records) multiply the representations of one resource. Done naively, each publish fans out into several queries, cache entries and invalidation paths, and each page load pays for formats it does not render.

Extensions are the other risk: a manifest system that discovers code at runtime makes every installed extension a per-request tax, and a hook model without a cost budget lets one listener destroy the page budget.

## Decision

**D1 — Compile at deploy.** The service container, route table, templates, configuration and Composer classmap are build artifacts. Production runs `--classmap-authoritative`; the request path performs no reflection, directory scan, config parse or environment read.

**D2 — Static routing.** Routes compile to a method-indexed table plus a segment tree with typed parameter constraints. Literal paths are array lookups; a method mismatch resolves from the same table without re-matching.

**D3 — Read-model projections.** Every representation of a resource (HTML, Markdown, JSON-LD, feed entry, sitemap entry, `llms.txt` link) renders from one cached projection assembled once per resource and locale. The projection key includes the revision hash and visibility, so a publish changes the key instead of requiring a flush.

**D4 — Layered cache with honest keys.** L0 HTTP/CDN, L1 page, L2 fragment, L3 data/projection, L4 compiled artifacts, L5 negative caching. Every key includes the dimensions that change output — locale, theme, visibility, representation — or the layer is not used. Private responses never enter a shared layer.

**D5 — Invalidation bus.** One bus receives `(resourceType, resourceId, changedFields, locales)` and resolves tags through a registry (`question:42`, `topic:7`, `locale:de`, `feed:site`, `sitemap:questions`). Artifact regeneration is queued, not performed in the publish request. Publish is two-phase: write and validate, then invalidate and warm; a failed warm leaves the previous artifact intact.

**D6 — Stampede control.** Cold keys are computed once behind a short, jittered single-flight lock; waiters serve stale where the artifact permits. Expensive artifacts are built by jobs, never by the first visitor.

**D7 — OPcache is the baseline; preloading and JIT are options.** The documented baseline is stock shared hosting: OPcache on, classmap authoritative, file cache. `opcache.validate_timestamps=0`, `opcache.preload` and `opcache.jit` are documented uplifts for dedicated hosts, measured rather than assumed. The framework must never depend on them for correctness or for meeting the shared-hosting budget.

**D8 — Data access for reads.** Prepared statements, projections instead of entity hydration on read paths, batched relation loads, keyset pagination, and a declared query count per page asserted in tests. An ORM may exist for write-side convenience; it is not on the page read path.

**D9 — Measured in CI.** A benchmark harness runs fixed workloads on fixed fixtures (empty, 1k, 10k, 100k; cold and warm; one and ten locales) and records p50/p95/p99, CPU, peak memory, query count and bytes. Baselines are committed; a regression beyond tolerance fails the build.

**D10 — Extensions declare, never discover.** Extension routes, services, hooks, templates and locales are manifest-declared and compiled at install. Listeners are lazy. A cost budget applies: the same query and render-time gates as core, measured with the extension enabled.

## Alternatives

- **Runtime-reflection container (the common framework default).** Convenient and autowiring-friendly, but pays reflection and parameter introspection on every request and hides coupling. Rejected for the hot path; a compile step removes the cost while keeping autowiring ergonomics at build time.
- **Adopt an existing full framework (Laravel, Symfony full-stack, Laminas).** Faster to start and battle-tested, but contradicts the charter's "own lightweight framework, no mandatory heavy production services", ships kilobytes of unused abstractions to shared hosting, and would subordinate the extension model to a vendor's. Rejected; specific components remain reusable behind contracts.
- **Long-running runtime (RoadRunner, FrankenPHP worker mode, Swoole).** Removes per-request boot and enables preloading, but requires process supervision not available on shared hosting, introduces state-leak and memory-growth classes of bugs, and changes the lifecycle assumed by sessions, extensions and the job runner. Rejected for the baseline; may be revisited by a later ADR as an optional deployment profile.
- **Full-page cache only.** Simple and effective for anonymous traffic, but breaks on per-user state, permissions and locale variance, and leaves the uncached path slow. Rejected; layers L1–L3 exist because variance is real.
- **Long TTL plus flush on publish.** Simplest invalidation, but flushing a shared cache under write load causes stampedes and cold pages, and a single tag change wipes unrelated entries. Rejected for tag-and-version invalidation.
- **JIT-first tuning.** Attractive because it sounds free, but JIT is off by default since 8.4 and benefits long-running compute, not short request handlers; tuning it first misallocates effort. Rejected as a first step.
- **ORM on the read path.** Reduces boilerplate but hydrates objects the page never uses, invites N+1 and hides query shape. Rejected for reads; permitted for writes.

## Security and performance implications

- Cache keys that omit visibility are a data-leak vulnerability, not a bug: a private answer served from a public page cache is a breach. D4's key discipline and the leakage tests in SEC-D-04 cover it.
- Stale artifacts are bounded: a stale window is documented per layer, and a stale artifact never serves content that has become private.
- Single-flight locks must not become a denial-of-service: short TTL, jitter, and a fallback that computes without the lock when acquisition fails.
- File cache lives outside the document root with restrictive permissions (SEC-I-01); a world-readable cache directory leaks payloads.
- Benchmark fixtures are generated, never production data; recorded results must not embed customer content.
- The build step is a privileged operation: compiled artifacts are written by the deploy process, not by a web request.

## Shared hosting implications

- Everything required for the shared-hosting budget works with OPcache enabled and nothing else: classmap authoritative, compiled artifacts in `storage/`, file cache, atomic writes.
- No APCu, Redis, preloading, JIT, worker process or cron is required for correctness; each is an uplift detected at install and reported as a capability.
- The build step runs during install/update (already privileged) and in the release ZIP for a pre-built tree; a host that cannot run Composer still receives compiled artifacts.
- Artifact regeneration must fit a request-sized job: bounded batches, resumable, with the previous artifact served until the new one is complete.

## Compatibility and migration

- No public API changes: contracts stay; compilation is an internal representation.
- Existing tasks gain acceptance criteria rather than new identifiers where they already own the subsystem (`CORE-004`, `CORE-005`, `CORE-008`, `CACHE-005`…); `PERF-*` tasks cover the cross-cutting pieces.
- The cache format version is part of every key, so a framework upgrade that changes serialization cannot read old entries; on upgrade the cache namespace is bumped and entries rebuild.
- The installer gains an artifact-build step and a capability report; updates rebuild artifacts atomically with the existing maintenance lock and journal (`UPD-004`, `UPD-005`).
- `PERFORMANCE.md` budgets remain the user-facing contract; this ADR adds the stage budgets and the enforcement mechanism without weakening them.

## Consequences

Positive: a cache hit returns before boot; an uncached page spends its time in the database and template, not the framework; one projection serves six representations; invalidation is explicit and testable; extensions cannot silently tax every request; regressions fail CI instead of reaching users.

Negative: a build step and its failure modes, more artifacts to version and invalidate, and a harness that must be maintained as the schema evolves. Extensions become slightly harder to write (declare rather than discover), which is the intended trade.

Neutral: more class files and a `storage/compiled/` directory; more test surface.

## Approval

Proposed by the performance workstream. Requires independent maintainer approval together with the performance budgets (`GOV-007`) and is recorded by setting `Status:` to `accepted` with the approver and date. D1, D3 and D5 are the decisions most expensive to reverse and are the review focus. Implementation tasks are blocked until approval; `PERF-001` is the gate.
