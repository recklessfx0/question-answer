# Architecture

Request → bootstrap/kernel → middleware → router → controller → application service → repository/cache → response. Web, REST and MCP reuse application services and authorization. Modules may depend on approved contracts, not another module's internals. Use PSR-4, PSR-3, PSR-11, PSR-14, PSR-16 and evaluate PSR-7/15 interoperability. Keep framework separate from CMS. Use PDO prepared statements, explicit transactions, typed DTOs, escaping by context, immutable configuration, event-driven cache invalidation. No global service locator in domain code.

## Layout
`app/Core`, `app/Contracts`, `app/Modules`, `public`, `resources`, `config`, `database/migrations`, `storage`, `extensions`, `themes`, `tests`. These are planned directories, not runtime implementations. Only public files should be web-accessible. Shared-host fallback must prove private paths inaccessible before installation. Release ZIP includes production dependencies; development tools stay off production.

## Contracts before parallel coding
Approve interfaces for HTTP, routing, configuration, database/migrations, authorization, events, cache, logging, rendering, jobs, extension lifecycle and content service. Publish contract tests and ADRs. Changes require architecture review and affected owners. Use versioned public contracts; internal classes are not extension APIs.

## Framework architecture and boundaries
Four unidirectional layers separate the framework from the CMS: `app/Contracts` (zero internal dependencies), `app/Core` (depends only on `app/Contracts`), `app/Modules/*` (depend only on `app/Contracts`, never on `app/Core` internals or other modules), and `extensions/*` (depend only on `app/Contracts`). Discovery, configuration, container wiring, routing, events and templates compile at build/deploy/install time into `storage/compiled/`, while requests run a pre-boot L1 fast path and a deterministic compiled middleware pipeline over a shared application spine for Web, REST, MCP and CLI. See [framework architecture](FRAMEWORK_ARCHITECTURE.md) and [ADR 0004](adr/0004-framework-architecture.md).

## Data
Content nodes with type, parent, root, author, status, visibility, timestamps and revision pointer; specialized questions/answers; revisions, sources, translations, tags, collections, votes, reports, users, roles, permissions, sessions, tokens, settings, extensions, jobs and audit events. Enforce parent/type rules, no cycles, bounded traversal, indexes on root/parent and status, transactions for accepted answers and vote uniqueness. Separate clarification from comments. Deletion and moderation must preserve privacy and thread integrity. Test both database engines; avoid vendor-specific SQL in core unless abstracted.

## Localization
The UI ships English; every other locale is installed, switched and removed at runtime. UI messages are ICU patterns in PHP catalogues addressed by stable IDs and resolved through a fixed layer precedence. Content translations are per-locale records grouped by a stable translation group, each with its own revisions and editorial status. A locale is validated against an installed allow-list before it reaches a path, cache key or header. The default locale is unprefixed and other locales are path-prefixed so a cached response never varies by request header. Language packs are data-only extensions. See [localization](I18N.md) and [ADR 0001](adr/0001-localization.md).

## SEO and agent representations
Public pages are server-rendered from a structured document model that also produces Markdown, JSON-LD, Atom/JSON feeds and XML sitemaps; no representation is derived from rendered HTML. Every indexable page carries a self-referencing canonical and the locale `hreflang` set. Sitemap children, feeds, `llms.txt` and social cards are cached artifacts invalidated on publish, and generation is streamed and bounded so a crawler cannot force a corpus build inside a request. Only published public content enters any artifact. See [SEO](SEO.md) and [ADR 0002](adr/0002-seo-and-agent-representations.md).

## Performance model
Requests are served by compiled artifacts: classmap-authoritative autoloading, a compiled container, a static route table and compiled templates, with no reflection or discovery on the request path. A cached response returns before boot; an uncached page renders from a read-model projection shared by HTML, Markdown, JSON-LD, feeds and sitemaps. Cache layers, keys and invalidation are defined in [framework performance](FRAMEWORK_PERFORMANCE.md) and [ADR 0003](adr/0003-performance-architecture.md); budgets are in [performance](PERFORMANCE.md).

## Operations
File cache default, optional APCu/Redis; HTTP caching for public pages only; key by language/theme/visibility where relevant; atomic writes, TTL, invalidation and stampede control. Cron optional with bounded opportunistic jobs; never run unbounded work in HTTP. Structured redacted logs and request IDs. AI provider gateway optional; MCP protocol version and transport must be verified against official spec at implementation time.
