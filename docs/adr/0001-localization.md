# ADR: Localization and translation model
Status: proposed

## Context

The charter requires multilingual content and the roadmap puts localization in v0.1 (`CORE-012`), but the architecture only names "translations" as an entity and a `languages` extension type. Without a model, three independent decisions get made implicitly by whoever writes the first controller: how UI strings are stored, how a translated question relates to its source, and how a locale enters a URL. Each is expensive to reverse after content exists, and each has a security edge — locale values reach file paths and cache keys, and translated strings render in an administrator's session.

Constraints that shape the decision: PHP 8.5 on ordinary shared hosting, no mandatory Redis, Node or worker; file cache default; page cache keyed by URL; extensions are trusted executable code except where declared otherwise; AI output is untrusted and cannot authorize anything.

## Decision

**D1 — Locale identity.** A locale is a BCP 47 tag validated against a subset pattern and an installed allow-list before any use in a path, cache key or header. Tags are stored normalized; comparison is case-insensitive.

**D2 — UI messages.** Catalogues are PHP files returning `array<string, string>` of ICU MessageFormat patterns, addressed by stable dotted IDs, grouped into lazy-loaded domains. English text is never the key.

**D3 — Precedence and fallback.** Layers resolve core → language pack → theme → site override. Locale fallback is bounded to exact tag, then language-only, then the default locale. A missing default-locale key is a build failure.

**D4 — URL strategy.** The default locale is unprefixed; every other locale is path-prefixed (`/de/…`). Each locale page carries its own canonical and an `hreflang` set including `x-default`. Public pages never redirect on `Accept-Language`; the header only drives a suggestion. `Vary: Accept-Language` is never sent from cacheable HTML.

**D5 — Content translations.** A stable translation group holds one revisioned, editorial record per locale. Translated fields are text and slugs; IDs, structure, authorship, votes and permissions stay on the group. A per-locale record stores its source locale and the source revision hash, which drives staleness.

**D6 — Translation workflow.** Status is `missing → machine_draft → draft → in_review → published`, with `stale` set when the source revision hash diverges. Machine output is always `machine_draft` with provider and timestamp, disclosed to readers. Publication requires a reviewer who is not the author, and every transition is audited.

**D7 — Negotiation.** Explicit locale, then user preference, then a bounded `Accept-Language` parse (≤20 ranges, clamped `q`, wildcards ignored), then the default. Write endpoints require an explicit locale; a missing one is a validation error.

**D8 — Language packs.** The existing `languages` extension type is data only: locale metadata and catalogues, no PHP, no entrypoint, no hooks. Packs are signed, versioned, precedence-ordered and removable without orphan keys.

**D9 — Exchange format.** Content translations import and export as XLIFF 2.0; UI catalogues as PHP arrays. Both are validated before use, reject bidi control characters and HTML, and export deterministically.

**D10 — Overrides storage.** Site overrides of UI messages live in the database, are editable only by an administrator, are exported to a deployable file for versioning, and invalidate the catalogue cache per locale on write.

## Alternatives

- **Gettext/PO + compiled `.mo`.** Mature tooling, but the compiled binary format must be regenerated on the host, parsing at runtime is not acceptable on shared hosting, and plural rules live outside the format. Rejected for core; PO import can still be added as an importer.
- **YAML or JSON catalogues.** Human-friendly, but adds a parser or an extension to every request for no benefit over an OPcache-backed PHP array, and complicates the "no mandatory dependency" rule. Rejected.
- **Strings in the database only.** Simple to edit, but every page render then needs a query or a warm cache, and a cold cache costs more than a file read. Rejected; the database holds only overrides.
- **Always-prefixed URLs, including the default locale.** Cleaner symmetry and simpler cache keys, but breaks every existing link and permalink when a site switches default locale. Rejected; the default stays unprefixed and the trade-off is documented.
- **Subdomain per locale.** Strong isolation, but needs wildcard DNS and TLS on shared hosting. Rejected for the baseline.
- **Header-only negotiation.** No locale in the URL, but makes one URL serve many variants, which poisons caches and breaks `hreflang`. Rejected.
- **Translate content in place** (overwrite the field per locale) instead of a translation group. Simple schema, but destroys the source, breaks revisions and makes review impossible. Rejected.

## Security and performance implications

- The locale is attacker-influenced input that reaches file paths and cache keys; D1's allow-list check is the control (SEC-C-07), and the contract refuses an unvalidated tag rather than sanitizing it late.
- Catalogue values are data: escaped at output, never evaluated, never rendered as HTML. A language pack is checked as data, not as code.
- Bidi control characters are stripped from input, imports and translated content, preventing visual impersonation in editorial and admin surfaces.
- `Accept-Language` parsing is bounded and control-character free, closing header injection and parser-exhaustion paths.
- Machine translation is a data-egress path and is opt-in with explicit consent for private content (SEC-D-02).
- Performance: catalogues are one OPcache-friendly file per locale and domain, lazily loaded, cached by `locale + domain + version`; the request path never scans a directory. Layout must survive 40% string expansion. Page cache invalidation is per locale, not global.

## Shared hosting implications

- No new mandatory dependency: `intl` is used when present, and its absence is detected at install with a documented reduced fallback rather than a fatal error at render time.
- Catalogue files are plain PHP, so a host with OPcache but no writable temp directory still works.
- Writing overrides needs one database write per changed key, not a file write, so a read-only code tree remains valid.
- Creating locale directories is not required at runtime; packs install through the existing extension lifecycle and its permission model.

## Compatibility and migration

- Existing content is backfilled into one translation group per node with its current language as the source locale; no text is rewritten.
- New tables are additive; the migration is reversible by dropping them without touching content rows.
- APIs gain optional `locale` parameters; clients that omit them keep current behaviour (default locale).
- The default locale is a setting, fixed at install and changeable with an audited migration that rewrites slugs and invalidates caches — documented before the first release.
- ADR 0001 must be approved together with the contract changes before implementation starts; `CORE-012` and the `I18N-*` tasks implement it.

## Consequences

Positive: one model for UI and content, reversible decisions recorded, translation work parallelizable by locale, and cache behaviour predictable because the locale is in the URL. Negative: two storage mechanisms (files for core strings, database for overrides and translations), XLIFF tooling to maintain, and per-locale cache invalidation logic to get right. Neutral: `intl` becomes strongly recommended and is listed in the hosting requirements.

## Approval

Proposed by the localization workstream. Requires an independent maintainer approval and the framework-contract approval (GOV-011) before any implementation task starts. Decisions D2, D4 and D9 are the ones most expensive to reverse; they are the review focus. Approval is recorded by changing `Status:` to `accepted` with the approving maintainer and date.
