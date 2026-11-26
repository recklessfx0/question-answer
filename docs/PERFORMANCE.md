# Performance budgets
Proposed reference targets: Lighthouse 100 performance/accessibility/best practices/SEO for default public feed, detail, search and category pages. Not guaranteed across hosts, content or extensions. LCP ≤1.5s, INP ≤100ms (field measurements when available), CLS ≤0.02, TBT ≤50ms; compressed initial JS ≤30KB, CSS ≤20KB, default text page ≤100KB, no mandatory third-party requests. Cached PHP processing ≤20ms, uncached ≤150ms on a documented reference environment. Record PHP/database versions, dataset, hardware, network, cache state and multiple runs. Measure regression against baseline; no fabricated scores. Private responses must never enter shared caches. Use lazy images, explicit dimensions, asset hashes, system fonts, minimal JS and query budgets. Test actual shared hosting separately.

## Stage budgets

The total is a contract; the stages are how it is met and how a regression is located.

| Stage | Cached hit | Uncached text page |
| --- | --- | --- |
| HTTP/page cache lookup | ≤ 1 ms | ≤ 1 ms |
| Boot + autoload + config | 0 ms | ≤ 2 ms |
| Routing | 0 ms | ≤ 0.3 ms |
| Container resolution | 0 ms | ≤ 0.5 ms |
| Database | 0 ms | ≤ 8 ms, ≤ 8 queries |
| HTML render | 0 ms | ≤ 5 ms |
| Serialize + headers | ≤ 0.5 ms | ≤ 1 ms |
| **Total CPU** | **≤ 1.5 ms** | **≤ 20 ms** |

Memory ≤ 8 MB per request and zero queries on a cached path. Targets apply to the documented reference environment and are reported as p50/p95/p99, never as a single average. See [framework performance](FRAMEWORK_PERFORMANCE.md) for the mechanisms and [ADR 0003](adr/0003-performance-architecture.md) for the decisions.

## Measurement

The benchmark harness (`PERF-006`) runs fixed workloads against generated fixtures — empty, 1k, 10k and 100k documents, cold and warm cache, one and ten locales — and records wall time percentiles, CPU time, peak memory, query count and response bytes. Baselines are committed as JSON; a regression beyond tolerance (for example +10% p95 or +1 query) fails CI. A result is valid only with its environment: PHP version, OPcache settings, database and version, hardware, warm-up runs and repeat count.

## Shared hosting

The baseline must pass with stock shared-hosting settings: OPcache enabled, no APCu, no Redis, no preloading, no JIT, no worker process. Those are uplifts detected at install and reported as capabilities, never requirements. Preloading requires a persistent process and php.ini access and is unsupported on Windows; JIT is disabled by default since PHP 8.4 and is not assumed.
