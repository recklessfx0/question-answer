# Architecture

Request → bootstrap/kernel → middleware → router → controller → application service → repository/cache → response. Web, REST and MCP reuse application services and authorization. Modules may depend on approved contracts, not another module's internals. Use PSR-4, PSR-3, PSR-11, PSR-14, PSR-16 and evaluate PSR-7/15 interoperability. Keep framework separate from CMS. Use PDO prepared statements, explicit transactions, typed DTOs, escaping by context, immutable configuration, event-driven cache invalidation. No global service locator in domain code.

## Layout
`app/Core`, `app/Contracts`, `app/Modules`, `public`, `resources`, `config`, `database/migrations`, `storage`, `extensions`, `themes`, `tests`. These are planned directories, not runtime implementations. Only public files should be web-accessible. Shared-host fallback must prove private paths inaccessible before installation. Release ZIP includes production dependencies; development tools stay off production.

## Contracts before parallel coding
Approve interfaces for HTTP, routing, configuration, database/migrations, authorization, events, cache, logging, rendering, jobs, extension lifecycle and content service. Publish contract tests and ADRs. Changes require architecture review and affected owners. Use versioned public contracts; internal classes are not extension APIs.

## Data
Content nodes with type, parent, root, author, status, visibility, timestamps and revision pointer; specialized questions/answers; revisions, sources, translations, tags, collections, votes, reports, users, roles, permissions, sessions, tokens, settings, extensions, jobs and audit events. Enforce parent/type rules, no cycles, bounded traversal, indexes on root/parent and status, transactions for accepted answers and vote uniqueness. Separate clarification from comments. Deletion and moderation must preserve privacy and thread integrity. Test both database engines; avoid vendor-specific SQL in core unless abstracted.

## Operations
File cache default, optional APCu/Redis; HTTP caching for public pages only; key by language/theme/visibility where relevant; atomic writes, TTL, invalidation and stampede control. Cron optional with bounded opportunistic jobs; never run unbounded work in HTTP. Structured redacted logs and request IDs. AI provider gateway optional; MCP protocol version and transport must be verified against official spec at implementation time.
