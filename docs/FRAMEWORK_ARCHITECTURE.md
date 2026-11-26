# Framework architecture

How the QuestionAnswer core framework is structured, layered, compiled and kept separate from CMS domain modules: dependency boundaries, build-time versus request-time lifecycles, the pre-boot cache fast path, the deterministic middleware pipeline, the multi-surface application spine, core subsystems, and the public contract map. Provisional until [ADR 0004](adr/0004-framework-architecture.md) is approved; tracked under `GOV-004`, `GOV-011` and `CORE-001` through `CORE-013`. Security gates are in [security requirements](SECURITY_REQUIREMENTS.md) and [threat model](THREAT_MODEL.md); performance mechanisms are in [framework performance](FRAMEWORK_PERFORMANCE.md); localization and SEO models are in [I18N](I18N.md) and [SEO](SEO.md).

## Architectural style

QuestionAnswer is a **compiled modular monolith** built on PHP 8.5 for ordinary shared hosting:

- **Request-per-process baseline:** no mandatory persistent worker, Redis, APCu, Node, Docker or SSH on production.
- **Strict framework/CMS separation:** `app/Core` is a reusable, domain-agnostic framework; Q&A domain concepts live exclusively in `app/Modules`.
- **Two explicit lifecycles:** discovery, wiring and template compilation happen at **build/deploy/install time**; the **request path** performs zero reflection and zero directory scanning.
- **One application spine for every surface:** Web HTML, Markdown (`.md`), JSON-LD, feeds, sitemaps, REST `/api/v1`, MCP and CLI all invoke the same application services, authorization checks and read-model projections.

## Dependency layers

Code is divided into four layers with unidirectional dependencies enforced in CI:

```
public/index.php, bin/qa  (Thin entry adapters)
         │
         ├──► app/Core         (Framework runtime — depends ONLY on app/Contracts)
         │         │
         ▼         ▼
    app/Modules ──► app/Contracts  (Public interfaces & immutable value types — zero internal deps)
         ▲
    extensions/ ──► app/Contracts  (Third-party code — depends ONLY on app/Contracts)
```

### Layer rules

| Layer | May depend on | Must never depend on | Stability |
| --- | --- | --- | --- |
| `app/Contracts` | PHP 8.5 stdlib, approved PSR interfaces | `app/Core`, `app/Modules`, `extensions/` | Semver public API |
| `app/Core` | `app/Contracts`, audited minimal vendor primitives | `app/Modules`, `extensions/` | `@internal` |
| `app/Modules/*` | `app/Contracts` | `app/Core` concrete classes, other `app/Modules/*` internals | `@internal` |
| `extensions/*` | `app/Contracts` | `app/Core`, `app/Modules` internals | External |

Cross-module communication happens strictly through published interfaces in `app/Contracts`, read-model projections, or PSR-14 domain events — never by importing another module's repositories, controllers or models.

## Dual-phase lifecycle

### 1. Build / deploy / install phase (privileged)

Runs during CI release packaging, browser installation, core updates and extension activation/deactivation. `ArtifactCompiler` reads core module definitions, active extension manifests, configuration files and templates, validates invariants (missing services, container cycles, route conflicts, unknown template helpers), and atomically writes compiled PHP artifacts into `storage/compiled/`:

- `storage/compiled/config.php` — immutable merged configuration array (no `.env` parsing on requests).
- `storage/compiled/container.php` — static closure factories with explicit constructor wiring (no reflection).
- `storage/compiled/routes.php` — method-indexed static route map plus radix/segment tree with typed parameter constraints.
- `storage/compiled/events.php` — event-to-lazy-listener map ordered by priority.
- `storage/compiled/modules.php` — active core modules, extension slots and admin navigation entries.
- `storage/compiled/templates/*.php` — pre-compiled, context-escaped PHP templates.

Artifacts are written to a staging directory and swapped atomically so concurrent requests never observe a torn build. Release ZIPs include baseline compiled artifacts and a `--classmap-authoritative` autoloader so shared hosts without Composer or CLI work out of the box.

### 2. Request phase (hot path)

1. **Pre-boot L1 fast path (`public/index.php`):** For anonymous `GET`/`HEAD` requests (no session cookie present, normalized safe path), PHP checks the L1 page cache (file or APCu) before booting the container. On a hit, it streams the cached payload with security headers and `ETag`/`304` handling and exits (`≤ 1.5 ms` CPU, zero DB queries).
2. **Kernel boot:** On a cache miss, `Kernel` loads `storage/compiled/*.php` (served from OPcache) and constructs the lazy `CompiledContainer`.
3. **Middleware pipeline:** Request passes through the compiled, ordered middleware stack for its route group.
4. **Controller & application service:** Controller validates input via `Validator`, calls an application service (which enforces object-level authorization), reads from a cached projection or executes a transactional write, and returns a `Response`.
5. **Response emission:** Security and cache headers are finalized, body streams to the client, and bounded opportunistic post-response work (if configured and permitted) may run after flush.

## Deterministic middleware pipeline

Each route group (`web`, `public-artifact`, `api`, `mcp`, `admin`, `installer`) receives a compiled, explicit middleware stack in fixed outer-to-inner order:

| Order | Middleware | Responsibility | Controls |
| --- | --- | --- | --- |
| 1 | `RequestIdAndTimingMiddleware` | Correlation ID and `Server-Timing` / profiler spans | `LOG-001`, `PERF-006` |
| 2 | `ErrorHandlerMiddleware` | Catches `Throwable`, redacts logs, renders generic HTML or RFC 9457 JSON | `CORE-009`, `SEC-D-01` |
| 3 | `SecurityHeadersMiddleware` | HSTS (HTTPS only), nonce CSP without `unsafe-inline` scripts, `nosniff`, `Referrer-Policy`, strips `X-Powered-By` | `SEC-C-03`, `SEC-I-01` |
| 4 | `RequestLimitsMiddleware` | Rejects oversized headers, URIs, bodies and uploads before parsing | `SEC-C-06` |
| 5 | `PathNormalizationMiddleware` | Single-hop `301` slash/case normalization; rejects traversal and control bytes | `SEO-002`, `SEC-C-06` |
| 6 | `LocaleMiddleware` | Validates locale against allow-list (`404` on unknown prefix), binds locale context | `I18N-002`, `SEC-C-07` |
| 7 | `RoutingMiddleware` | Matches compiled route table; resolves `405` + `Allow` or `404` in one pass | `CORE-005`, `PERF-002` |
| 8 | `RateLimitMiddleware` | Enforces per-IP and per-actor limits on auth, API and artifact routes | `SEC-A-04`, `SEC-005` |
| 9 | `AuthenticationMiddleware` | Resolves `Actor` (session cookie for web/admin, scoped token for API/MCP, anonymous otherwise) | `SEC-A-02`, `SEC-E-04` |
| 10 | `CsrfMiddleware` | Validates CSRF token on state-changing session/cookie-backed requests | `SEC-A-06` |
| 11 | `CacheControlMiddleware` | Enforces `no-store` + `Vary: Cookie` on authenticated output; handles `ETag`/`304` and public L1 storage | `PERF-009`, `SEC-D-04` |
| 12 | `ControllerDispatch` | Invokes controller with typed route parameters and `RequestContext` | `CORE-004`, `CORE-007` |

## Multi-surface application spine

Web controllers, REST `/api/v1` controllers, MCP tool/resource handlers and CLI commands are **thin delivery adapters**. They never contain business rules, SQL or direct authorization bypasses:

- **Commands (writes):** Parse transport payload → validate via `Validator` → invoke Application Service → check `Authorizer::authorize($actor, $ability, $resource)` → execute repository mutations inside `TransactionManager::run()` → record audit event → emit `ResourceChange` to `InvalidationBus`.
- **Queries (reads):** Parse parameters and locale → invoke Query/Read Service → apply identical visibility/authorization filter → load cached **Read-Model Projection** (shared across HTML, Markdown, JSON-LD, feeds and sitemaps) → serialize for the target surface.

## Directory layout

```text
public/
  index.php                    # Front controller + pre-boot L1 fast path
  assets/                      # Content-hashed static CSS/JS/SVG

app/
  Contracts/                   # Public interfaces & immutable DTOs (zero Core imports)
    Auth/                      # Actor, Authorizer, Policy, SessionStore
    Cache/                     # CacheStore, TaggedCache, StampedeLock, InvalidationBus
    Config/                    # ConfigRepository
    Container/                 # ContainerBuilder, ServiceDefinition (PSR-11 runtime)
    Content/                   # DocumentModel, ContentBlock, ReadProjection, ContentService
    Database/                  # Connection, TransactionManager, Migration, QueryProfiler
    Events/                    # DomainEvent (PSR-14 dispatcher)
    Extension/                 # ModuleInterface, ExtensionManifest, ArtifactContributor
    Http/                      # Request, Response, Middleware, Router, UrlGenerator, OutboundHttpClient
    Jobs/                      # Job, JobDispatcher, JobHandler
    Localization/              # CatalogueLoader, LocaleResolver, Translator
    Logging/                   # Redactor (PSR-3 logger)
    Mail/                      # EmailMessage, MailTransport, Mailer
    Rendering/                 # TemplateEngine, Escaper, SafeHtml
    Security/                  # SecretBox, TotpVerifier, PasskeyVerifier, CsrfTokenManager, PasswordHasher, RateLimiter, AuditLogger
    Seo/                       # MarkdownRepresentation, SitemapSource, StructuredData
    Validation/                # Validator, ValidationResult, Rule

  Core/                        # Domain-agnostic framework runtime
    Bootstrap/                 # Kernel, Application, EnvironmentCapabilities
    Compiler/                  # ArtifactCompiler, Config/Container/Route/Template/Event compilers
    Config/                    # ImmutableConfig
    Container/                 # CompiledContainer
    Http/                      # Request/Response, StaticRouter, MiddlewareRunner, SafeOutboundClient
    Validation/                # Schema validator, bidi stripper, UTF-8 normalizer
    Template/                  # Compiled template runner, ContextEscaper, SlotRegistry
    Error/                     # ExceptionRenderer (HTML + RFC 9457), RedactedErrorReporter
    Database/                  # PdoConnection, TransactionManager, MigrationRunner, QueryBudgetProfiler
    Cache/                     # AtomicFileCache, ApcuCache, StampedeGuard, TagIndex, PageCache, InvalidationBus
    Events/                    # CompiledEventDispatcher
    Logging/                   # JsonFileLogger, SecretRedactor, LogRotator
    Mail/                      # QueuedMailer, SmtpTransport, DkimSigner, LogTransport, NullTransport
    Security/                  # SodiumSecretBox, Rfc6238Totp, WebAuthnPasskey, PasswordHasher, SessionManager, RbacAuthorizer, CsrfGuard, UploadGuard
    Localization/              # DefaultLocaleResolver, PhpCatalogueLoader, IcuTranslator
    Jobs/                      # DbJobQueue, OpportunisticRunner
    Module/                    # ModuleRegistry

  Modules/                     # CMS domain & delivery modules (depend ONLY on app/Contracts)
    Users/ Questions/ Answers/ Clarifications/ Comments/ Taxonomy/
    Search/ Seo/ Web/ Admin/ Api/ Mcp/ Ai/ Extensions/ Installer/
```

## Core subsystem invariants

1. **Bootstrap and configuration (`CORE-001`, `CORE-002`):** `ImmutableConfig` is read-only after boot. No runtime `.env` reads or `class_exists()` probing in hot paths. `EnvironmentCapabilities` detects OPcache, APCu, `intl`, GD/Imagick and `open_basedir` at install/diagnostics time.
2. **Container (`CORE-004`, `PERF-002`):** Compiled closure array implementing PSR-11. Lazy instantiation, shared instances per request, zero runtime reflection, and no global service locator in domain code.
3. **Router and URL generation (`CORE-005`):** Static lookup + typed segment tree plus reverse `UrlGenerator` so canonical URLs, `hreflang`, feeds, sitemaps and `.md` alternates share one route source of truth.
4. **Contextual templates (`CORE-008`, `SEC-C-02`, `SEC-C-08`):** Explicit sink escaping (`html`, `attr`, `url`, `jsonLd`). Raw HTML requires a typed `SafeHtml` value produced only by audited sanitizers/renderers. Named template slots support extension UI without core edits.
5. **Database and read projections (`DB-001`–`DB-005`, `PERF-004`, `PERF-007`):** PDO with `ATTR_EMULATE_PREPARES => false`, explicit transactions, keyset pagination, no ORM on read paths, and per-route query budgets enforced in tests.
6. **Cache and invalidation (`CACHE-001`–`CACHE-007`, `PERF-005`):** Atomic file writes (`tmp` + `rename`), honest keys (`locale + theme + visibility + representation + formatVersion`), single-flight stampede locks, and synchronous immediate invalidation whenever content transitions from public to non-public (`SEC-D-04`).
7. **Security primitives (`SEC-001`–`SEC-009`, [AUTH](AUTH.md)):**
   - `Authorizer` always evaluates object-level permissions; API/MCP token scopes only intersect/constrain permissions (`SEC-B-02`).
   - `SessionManager` enforces an explicit three-state session machine (`anonymous` → `pending_mfa` → `authenticated`) plus bounded step-up ("sudo") re-authentication for destructive actions (`SEC-A-02`, `SEC-A-05`).
   - `TotpVerifier` and `PasskeyVerifier` provide RFC 6238 TOTP (with replay floor) and WebAuthn/FIDO2 passkeys (`ES256`/`EdDSA`/`RS256`, origin/RP ID checks, `sign_count` clone detection) (`SEC-A-05`, `SEC-A-07`).
   - `SecretBox` provides versioned AEAD encryption (`sodium_crypto_aead_xchacha20poly1305_ietf_*`) with mandatory AAD for TOTP seeds, SMTP passwords, DKIM keys, AI provider keys and webhook secrets (`SEC-D-05`).
   - `OutboundHttpClient` is the sole sanctioned egress client: enforces scheme allow-list, resolves DNS, blocks loopback/private/link-local/CGNAT/metadata IPv4/IPv6 ranges at dial time and on every redirect, and bounds time and bytes (`SEC-C-05`).
   - `SecretRedactor` runs inside the logger pipeline so credentials, sessions and prompt secrets are redacted centrally (`SEC-D-01`).
8. **Transactional mail (`MAIL`, [ADR 0006](adr/0006-mail-and-smtp.md)):** `Mailer` enqueues validated `EmailMessage` instances (`CR`/`LF` rejected at construction) into the DB job queue so HTTP requests never block on SMTP; `SmtpTransport` enforces implicit TLS (`465`) or mandatory `STARTTLS` (`587`) with strict certificate verification, SSRF host checks, encrypted credentials via `SecretBox`, optional DKIM signing, and localized multipart templates in the recipient's locale (`SEC-C-09`).
9. **Jobs (`CORE-013`):** Database-backed `jobs` table executed by optional cron (`bin/qa jobs:run`) or bounded, single-flight opportunistic post-response batches on shared hosts without cron.
10. **Modules and extensions (`CORE-011`, `EXT-001`–`EXT-007`, `PERF-008`):** Core modules and extensions register through the same declarative `ModuleInterface`/manifest. A `config/safe_mode.lock` flag boots core-only compiled artifacts for recovery if an extension fails.

## Public contracts map (`GOV-011`)

Before parallel implementation begins in `v0.1`, public contracts live under `app/Contracts/` and carry contract test suites:

| Package | Primary interfaces | Unblocked workstreams |
| --- | --- | --- |
| `Contracts/Config` | `ConfigRepository` | `CORE-002`, `DB-001`, `CACHE-001`, `LOG-001` |
| `Contracts/Http` | `Request`, `Response`, `Middleware`, `Router`, `UrlGenerator`, `OutboundHttpClient` | `CORE-003`, `CORE-005`, `CORE-006`, `SEO-002`, `API-*` |
| `Contracts/Auth` | `Actor`, `Authorizer`, `Policy`, `SessionStore` | `SEC-001`–`SEC-004`, `QA-001`, `CACHE-006`, `API-*`, `MCP-*` |
| `Contracts/Security` | `SecretBox`, `TotpVerifier`, `PasskeyVerifier` *(drafted)*; `CsrfTokenManager`, `RateLimiter`, `AuditLogger` | `SEC-001`–`SEC-009`, `MAIL`, `AI-001`, `API-005` |
| `Contracts/Mail` | `EmailMessage`, `MailTransport`, `Mailer` *(drafted)* | `SEC-001`, `SEC-007`, `QA-010`, `ADMIN-004` |
| `Contracts/Database` | `Connection`, `TransactionManager`, `Migration`, `QueryProfiler` | `DB-001`–`DB-005`, `PERF-007` |
| `Contracts/Cache` | `CacheStore`, `TaggedCache`, `StampedeLock`, `InvalidationBus` | `CACHE-001`–`CACHE-007`, `PERF-004`, `PERF-005` |
| `Contracts/Rendering` | `TemplateEngine`, `Escaper`, `SafeHtml` | `CORE-008`, `UI-*`, `EXT-004` |
| `Contracts/Content` | `DocumentModel`, `ContentBlock`, `ReadProjection`, `ContentService` | `QA-001`, `SEO-003`–`SEO-007`, `I18N-004`, `PERF-004` |
| `Contracts/Jobs` | `Job`, `JobDispatcher`, `JobHandler` | `CORE-013`, `SEO-005`, `SEO-008`, `UPD-*` |
| `Contracts/Extension` | `ModuleInterface`, `ExtensionManifest`, `ArtifactContributor` | `CORE-011`, `EXT-001`–`EXT-007`, `PERF-008` |
| `Contracts/Localization` | `CatalogueLoader`, `LocaleResolver`, `Translator` *(drafted)* | `CORE-012`, `I18N-*` |
| `Contracts/Seo` | `MarkdownRepresentation`, `SitemapSource`, `StructuredData` *(drafted)* | `SEO-*` |

## Testing and architectural gates

- **Layer boundary checks:** Static architecture tests (e.g., Deptrac or PHPStan rules) fail CI if `app/Contracts` imports anything internal, `app/Core` imports `app/Modules`, or any module imports another module's internals.
- **Contract tests:** Every interface in `app/Contracts/` ships a reusable test suite that both core implementations and extension adapters pass.
- **Compiler determinism tests:** Compiling identical inputs twice produces byte-identical artifacts; cyclic container definitions, duplicate routes and unknown service references fail compilation with explicit errors.
- **Security negative tests (`SEC-J-01`):** Every core security primitive (authz, CSRF, headers, contextual escaping, SSRF guard, log redaction, upload guard, visibility invalidation) carries fail-closed negative tests.

## Open decisions

Recorded in [ADR 0004](adr/0004-framework-architecture.md) for maintainer sign-off under `GOV-004` and `GOV-011`:

1. **HTTP message contract:** Minimal PSR-7/PSR-15 directly in `app/Contracts/Http` versus lean PHP 8.5 `readonly` QuestionAnswer-native `Request`/`Response` with an optional PSR-7 bridge.
2. **Template dialect:** Restricted PHP templates with compile/static checks versus a lightweight custom template compiler with context-aware auto-escaping.
3. **Document block storage:** Validated JSON block schema plus plain-text search column versus strict CommonMark parsed to cached blocks on write.
4. **Development compilation mode:** Always execute compiled artifacts (with auto-recompile in dev) versus an uncompiled dynamic container in dev.
5. **Runtime dependency floor:** PSR interface packages + PHP 8.5 built-ins only (`PDO`, `sodium`, `password_hash`, `intl`, `XMLWriter`) versus specific audited runtime libraries (e.g., CommonMark or HTML sanitizer).
