# Framework performance architecture

How the core gets fast, stays fast, and stays extensible: the request-lifecycle budget, the compiled-artifact strategy, the cache layers and their invalidation, the measurement harness, and the extension cost model. Provisional until [ADR 0003](adr/0003-performance-architecture.md) is approved; tracked as the `performance` workstream (`PERF-*`, alongside the existing `CACHE-*`). Budgets are in [performance](PERFORMANCE.md); lifecycle in [architecture](ARCHITECTURE.md); artifact load in [SEO](SEO.md) and [I18N](I18N.md).

## Where the time actually goes

A cached page should never reach PHP, and an uncached one should be dominated by the database and template, not the framework. The budget is a contract, measured per stage:

| Stage | Cached hit target | Uncached text page target | Notes |
| --- | --- | --- | --- |
| HTTP cache / page cache lookup | ≤ 1 ms | ≤ 1 ms | The single biggest win: return before booting |
| Boot + autoload + config | 0 ms | ≤ 2 ms | Classmap authoritative, no discovery, compiled config |
| Routing | 0 ms | ≤ 0.3 ms | Static table lookup, no regex compile |
| Container resolution | 0 ms | ≤ 0.5 ms | Compiled factories, lazy services |
| Database | 0 ms | ≤ 8 ms / ≤ 8 queries | Keyset pagination, projections, batched reads |
| Render (HTML) | 0 ms | ≤ 5 ms | Templates compiled to PHP |
| Serialize + headers | ≤ 0.5 ms | ≤ 1 ms | Streamed output, small head |
| **Total CPU** | **≤ 1.5 ms** | **≤ 20 ms** | Wall time depends on host and DB |

These are targets for a documented reference environment, not guarantees (see [PERFORMANCE](PERFORMANCE.md)). Measure p50/p95/p99; a single average hides the regressions that matter.

## Ten rules the core follows

1. **Do the least work per request.** Nothing in the hot path scans directories, reads environment files, parses configuration formats or reflects over classes.
2. **Compile at deploy, not at runtime.** Container, routes, templates, catalogues and the classmap are artifacts produced by a build step.
3. **Cache at the highest layer that can answer.** CDN/HTTP → page → fragment → data. Each layer that answers stops the ones below it.
4. **Invalidate by tag and version, never by flush.** A publish invalidates exactly what changed, per locale and per artifact type.
5. **One query shape per page.** A page declares its queries; N+1 is a bug, and a cached page issues zero.
6. **Lazy by default, eager only on measure.** Services are constructed when used; the container returns shared instances without re-resolving.
7. **Stream and bound.** Responses stream; sitemap, feed and export generation stream from cursors and are never unbounded per request.
8. **Measure in CI.** A harness with fixed fixtures and a regression tolerance, or the budgets above are decoration.
9. **Keep extensions declarative.** No runtime discovery; hooks and routes are declared in a manifest and compiled in.
10. **No fabrication.** If a number was not measured on a recorded environment, it is a target, not a result.

## 1. Boot and autoloading

- `composer dump-autoload --classmap-authoritative` in production builds: known classes resolve from a map with no filesystem stat, and misses do not fall back to PSR-4 scans. APCu autoloading is the alternative when the classmap cannot be authoritative, because it also caches misses.
- No `class_exists()` probing for optional features in the hot path; features are declared in configuration and compiled.
- Configuration is compiled to a plain PHP array artifact from `.env` plus `config/*.php`; reading and parsing the environment happens at build time and in the installer, never per request.
- Bootstrap executes a fixed, short sequence and returns a handler; there is no plugin discovery loop before routing.

## 2. Compiled container

The container is the most common place a "fast" framework quietly slows down. The core compiles a service graph into plain PHP:

```php
// storage/compiled/container.php — generated at deploy
return [
    ResponseFactory::class => static fn (Container $c): ResponseFactory => new ResponseFactory(),
    QuestionService::class => static fn (Container $c): QuestionService => new QuestionService(
        $c->get(QuestionRepository::class),
        $c->get(CacheStore::class),
        $c->get(Clock::class),
    ),
];
```

- No reflection, no attribute scanning, no parameter-name introspection at request time.
- Shared instances are stored once per request; `get()` on an already-built service is an array lookup.
- Services are lazy: a controller that never touches the mailer never builds it.
- Domain code receives dependencies through constructors; a global service locator is banned in domain code (already in [ARCHITECTURE](ARCHITECTURE.md)).
- Extension services are compiled into the same graph at build/install time, not discovered per request.

## 3. Routing

- Routes compile into a static table keyed by method and a segment tree for parameters; matching a literal path is an array lookup, not a chained regex.
- Route definitions come from modules at compile time; the compiled table is invalidated on deploy or extension activation, not per request.
- Method mismatch is resolved from the same table (`405` with `Allow`), without re-matching.
- Trailing-slash and case normalisation happen before matching, in one pass, not by retrying routes.
- Parameterised routes are validated against typed constraints compiled with the route, so the controller receives scalars, not raw strings.

## 4. Templates

- Templates compile to plain PHP once (opcache-cached), not parsed per request. A template render is an `include` plus output buffering.
- Escaping is decided at compile time from context (HTML text, attribute, URL, JSON), so runtime does not guess.
- Partials are functions/closures in the same compiled file where possible; a fragment that is cacheable is a named fragment with its own key.
- Output streams: the layout can flush the head before the body is fully built, so time-to-first-byte does not wait on the slowest query.
- No template inheritance chains deeper than two levels; no runtime partial discovery by file name convention.

## 5. Data access and projections

- PDO with prepared statements; no ORM in the hot path. Entities are not hydrated for read-only pages: queries return **projections** — arrays or readonly DTOs with exactly the fields the page needs.
- One query per collection plus batched lookups for relations; the query count per page is asserted in tests.
- Keyset pagination on indexed columns, never `OFFSET` deep pages.
- Reads that feed a page are executed in a fixed order and can be combined into a single round trip where the engine allows it.
- The write path may use richer objects; the read path is optimised for the page it serves.

## 6. The projection layer (the cross-cutting recommendation)

SEO, localization and MCP each added a representation of the same content: HTML, Markdown, JSON-LD, Atom/JSON entries, sitemap entries, `llms.txt` links. If each representation queries the database independently, a single publish fans out into six queries and six cache entries with six invalidation paths.

Instead, introduce one **read model per resource**: a cached, versioned projection assembled from the content service, containing everything the representations need (title, slug, locale, body blocks, author, dates, revision hash, visibility, topic, counts). Then:

- HTML, Markdown, JSON-LD, feeds and sitemaps render from the same cached projection — one query set, one invalidation event.
- The projection cache key includes `resource + locale + revision hash + visibility`; a publish changes the revision hash and therefore the key, so stale entries age out without a flush.
- Projections are small and serialisable (arrays), so they suit file cache, APCu or Redis without changes.
- Build them lazily on first read and pre-warm them on publish for the resources that changed.

This is the single highest-leverage change to the current plan, because it turns the SEO and localization work from "more work per request" into "more formats per cached unit".

## 7. Cache layers

| Layer | What | Store | Key | Invalidation |
| --- | --- | --- | --- | --- |
| L0 HTTP/CDN | Full response | Proxy/headers | URL + `Vary` | `Cache-Control`, ETag, `clear-site-data` not used |
| L1 Page | Rendered HTML per locale | File/APCu | canonical URL + locale + theme + visibility | Publish tag, TTL |
| L2 Fragment | Hot lists, nav, counters | File/APCu | fragment name + params | Tag on the underlying resource |
| L3 Data/projection | Read models, catalogues | File → APCu → Redis | resource/locale/domain + version | Revision hash, catalogue version |
| L4 Compiled artifacts | Container, routes, templates, classmap | Files (opcache) | deploy version | Deploy/install |
| L5 Negative | 404/410, empty results | File/APCu | normalised URL + method | Short TTL |

Rules:

- Private, authenticated responses are never stored in a shared cache (already in [PERFORMANCE](PERFORMANCE.md)).
- Keys include every dimension that changes output — locale, theme, visibility, representation — or the cache lies.
- TTL is a safety net, not the primary invalidation; versioned keys and tags do the real work.
- TTLs get jitter, so a corpus of entries does not expire in the same second.

## 8. Invalidation

One publish must invalidate: the page, its fragments, its projection, its sitemap shard, the feed, the `llms.txt` entry, its Markdown form, its JSON-LD cache if warmed, and its social card if regenerated.

- A single **invalidation bus** receives `(resourceType, resourceId, changedFields, locales)` and maps it to tag and key invalidations through a registry, instead of each subsystem subscribing ad hoc.
- Tags are coarse (`question:42`, `topic:7`, `locale:de`, `feed:site`, `sitemap:questions`) and stored as an index of tag → keys.
- Artifact regeneration is queued: the publish request marks artifacts stale; the worker rebuilds them. A stale artifact is served only until its bounded window expires.
- Publish is two-phase: write and validate, then invalidate and warm. A failed warm leaves the old artifact, never a half-written one (atomic file writes, `CACHE-002`).

## 9. Concurrency and stampede

- A cold key is computed once: single-flight lock, waiters serve stale if allowed, then re-check. The existing `CACHE-004` covers the mechanism; the projection layer must use it.
- Expensive artifacts (sitemap, `llms.txt`, OG images) are built by a job, not by the first visitor.
- Lock TTL is short and jittered; a crashed worker cannot leave a permanent lock.
- Writes to the cache are atomic (`rename`), so a reader never sees a half-written file.

## 10. OPcache, preloading and realpath

Production settings, with the honest caveats:

| Setting | Recommendation | Reality |
| --- | --- | --- |
| `opcache.enable` | On | Standard on shared hosts |
| `opcache.memory_consumption` | 128–256 MB | `10000` files default is often too small for a corpus of templates |
| `opcache.max_accelerated_files` | ≥ 20000 | Raise before blaming the framework |
| `opcache.validate_timestamps` | `0` in production | Requires `opcache_reset` or FPM restart on deploy; keep `1` in dev |
| `opcache.interned_strings_buffer` | 16–32 MB | Helps large catalogues |
| `opcache.preload` | Optional, FPM only | Requires php.ini (not pool config), a persistent process, and is unsupported on Windows; usually unavailable on shared hosting |
| `opcache.jit` | Leave `disable` unless measured | Disabled by default since PHP 8.4 (RFC accepted; local 8.5.12 confirms `opcache.jit=disable` with a 64 MB buffer); short request lives rarely benefit |
| `realpath_cache_size` | 4096–8192 K | `4096K` default is adequate for a single app tree |
| `realpath_cache_ttl` | 120–600 s | Raise only with immutable deploys |

Preloading is a dedicated-host optimisation, not a baseline. The framework must be fast with stock shared-hosting settings, and measurably faster with preloading — never dependent on it.

Long-running runtimes (RoadRunner, FrankenPHP worker mode, Swoole) are out of scope: they contradict the shared-hosting constraint and change the lifecycle (state leaks, memory growth). Revisit only with an ADR.

## 11. Output and bytes

- Gzip/Brotli at the server; the application never compresses HTML itself.
- `ETag` and `Last-Modified` on cacheable pages, with `304` short-circuiting the body.
- Static assets are content-hashed and served `immutable, max-age=31536000`; HTML is `no-cache` when dynamic.
- The head budget: canonical, `hreflang`, JSON-LD, Open Graph, feeds and Markdown alternates add bytes — the 100 KB HTML budget in [PERFORMANCE](PERFORMANCE.md) includes them, and they are server-rendered rather than hydrated.
- No inline scripts beyond a tiny bootstrap; no third-party requests.

## 12. Extensibility without paying for it

- Extension manifests declare routes, services, hooks, templates, locales and admin navigation. The installer compiles them into the same artifacts as core; runtime does no discovery.
- Hooks are PSR-14 listeners with declared priority and a stable payload; a listener that is not registered locally is not instantiated.
- Extensions may decorate a core service (wrap the contract), never monkey-patch or edit core files ([EXTENSIONS](EXTENSIONS.md)).
- A **cost budget** applies to extensions: declared listeners must be lazy, must not query per render, and are subject to the same query-count and render-time gates. An extension that blows the budget fails CI, not production.
- Capability declarations are informational today; anything that changes cost (a listener on `kernel.response`, a route) is visible in the compiled artifact and in diagnostics.

## 13. Measurement

- **Harness**: a CLI command boots the framework and runs fixed workloads against fixed fixtures (empty, 1k, 10k, 100k documents; cold and warm cache; one and 10 locales).
- **Metrics per workload**: wall time p50/p95/p99, CPU time, peak memory, query count, bytes, and stage timings (boot, route, container, DB, render).
- **Isolation**: document PHP version, opcache settings, database, hardware, warm-up runs and repeats; no laptop numbers presented as host numbers.
- **Baseline**: results are committed as JSON next to the code; a regression beyond tolerance (for example +10% p95 or +1 query) fails CI.
- **Production sampling**: a `Server-Timing` header and a sampled slow-request log, so real host behaviour is visible without a profiler.
- **Profiling**: XHProf/Tideways/SPX when a budget is missed, not as a default dependency.

## 14. Anti-patterns banned by this design

- Reflecting over classes or attributes per request; scanning directories for routes, templates or extensions.
- Hydrating full entities for read-only pages; `SELECT *` on a page path; `OFFSET` pagination on deep pages.
- Waking a service, querying or rendering a fragment inside a cached page.
- `flush()`-style cache clearing; cache keys that omit locale, theme, visibility or representation.
- Storing rendered output for authenticated users in a shared cache.
- Per-request YAML/JSON parsing, `.env` reading, or config merging.
- Template parsing at render time; string-built HTML in controllers.
- Extension code that runs on every request merely because it is installed.

## Recommendations against the current plan

| Gap in the current plan | Recommendation | Task |
| --- | --- | --- |
| `CORE-004` says "dependency container" with no compilation requirement | Compile the container; assert no reflection in the hot path | PERF-002 |
| `CORE-005` router, `CORE-008` templates, no artifact strategy | Compile routes and templates at deploy; invalidate on deploy/install | PERF-002 |
| `CORE-001` bootstrap with no OPcache/preload position | Document and test a settings matrix; preload optional | PERF-003 |
| SEO/I18N added five representations and many artifacts | One projection per resource, all representations render from it | PERF-004 |
| `CACHE-005` tags exist but no cross-artifact story | Invalidation bus, tag registry, artifact registry, two-phase publish | PERF-005 |
| `PERFORMANCE.md` has user-facing budgets only | Add stage budgets, query/memory/byte ceilings, and a CI gate | PERF-006 |
| `DB-005` profiles but nothing enforces | Query budget + N+1 detection as a test gate | PERF-007 |
| `EXTENSIONS.md` has no cost model | Compile-time extension artifacts and a cost budget | PERF-008 |
| `CACHE-006`/`CACHE-007` cover page cache and benchmarks | Add conditional requests, fragment cache policy, stale-while-revalidate | PERF-009 |

## Shared hosting versus dedicated

| Capability | Shared hosting (baseline) | Dedicated/VPS (optimised) |
| --- | --- | --- |
| OPcache | Usually on, timestamps validated | On, `validate_timestamps=0` |
| Classmap authoritative | Yes | Yes |
| File cache | Yes (must work) | Yes or APCu/Redis |
| APCu | Often absent | Yes |
| Preloading | Usually unavailable | Yes, FPM |
| JIT | No | Only if measured |
| Compression | Server-managed, may be absent | Yes |
| CDN | Optional | Yes |

Everything in the "fast path" must work in the left column; the right column is upside, not a requirement.

## References

- JIT disabled by default since PHP 8.4 — https://wiki.php.net/rfc/jit_config_defaults
- OPcache preloading (persistent processes, php.ini scope, no Windows) — https://www.php.net/manual/en/opcache.preloading.php
- Composer autoloader optimisation (classmap authoritative, APCu) — https://getcomposer.org/doc/articles/autoloader-optimization.md
- OPcache runtime configuration — https://www.php.net/manual/en/opcache.configuration.php
- HTTP caching semantics — RFC 9111, https://www.rfc-editor.org/rfc/rfc9111
- Existing budgets — [PERFORMANCE](PERFORMANCE.md); cache tasks — `CACHE-001`…`CACHE-007`
