# SEO and agent representations

Search-engine optimisation is a property of the core rendering pipeline, not a plugin, and the same pipeline must emit machine-readable forms for agents: Markdown pages, `llms.txt`, JSON-LD, Atom/JSON feeds and XML sitemaps. Provisional until [ADR 0002](adr/0002-seo-and-agent-representations.md) is approved; tracked as the `seo` workstream (`SEO-*`). Requirements: SEC-C-08, SEC-D-04, SEC-C-07; localization rules in [I18N](I18N.md); budgets in [performance](PERFORMANCE.md).

## Principles

1. **One model, many representations.** Content is a structured document (blocks plus metadata), rendered to HTML, Markdown, JSON-LD, feed entries and sitemap entries from the same source. Nothing is produced by scraping HTML back into text at request time.
2. **Never lie to a crawler.** Structured data describes what is visible, `lastmod` is a real change time, no hidden text, no cloaking, no machine-only facts that a reader cannot see.
3. **Public means public.** Sitemaps, feeds, JSON-LD and Markdown contain only published, publicly visible content. Drafts, private questions, moderation notes and personal data never appear.
4. **One canonical URL per resource per locale.** Everything else — filters, tracking parameters, `.md` forms, paginated views — either canonicalizes to it, is `noindex`, or both.
5. **Cheap by default.** Generated artifacts are cached and invalidated on publish; the request path never scans the corpus or builds an unbounded document in memory.

## Rendering pipeline

```
content service (authorization + visibility)
        └── document model (blocks, metadata, revisions)
              ├── HTML      → serving, canonical, hreflang, structured data, social cards
              ├── Markdown  → .md URLs and Accept negotiation for agents
              ├── JSON-LD   → schema.org graph
              ├── Atom/JSON → feeds
              └── XML       → sitemap entries
```

The document model is the authority. A block is text, heading, list, code, quote, table, image or a nested clarification/answer reference. Rendering is a pure function of the model plus its resolved locale and visibility, which is what makes the same document safe to emit in five formats without drift.

HTML → Markdown conversion at request time is prohibited: it loses structure, breaks code fences and tables, and turns every representation bug into two bugs.

## URLs and canonicalization

- HTML URLs are lowercase, slug-based and stable: `/{locale}/questions/{slug}`, `/{locale}/topics/{slug}`, `/users/{handle}`. The default locale is unprefixed; see [I18N](I18N.md).
- A published slug is immutable. Renaming creates a permanent `301` from the old path; redirect maps are stored, not inferred.
- Every indexable page carries a **self-referencing canonical**. Paginated pages canonicalize to themselves, never to page 1.
- Tracking and filter parameters are handled by canonicalization, never by blocking the crawler: `?utm_*`, `?sort=`, `?page=` variants either canonicalize or are `noindex`.
- Content lives at parameters-free URLs; filters and sorts are for humans and carry `noindex, follow`.
- Trailing slash and case normalisation happen with a single `301`, never a chain. Redirect chains are capped at one hop.
- `404` for absent; `410` for permanently removed content; never a soft-404 that returns `200` with "not found" text.

## Crawl control

### robots.txt

Generated, not hand-written, at `/robots.txt` (RFC 9309):

```
User-agent: *
Disallow: /admin
Disallow: /api
Disallow: /storage
Allow: /

Sitemap: https://example.com/sitemap.xml
```

- Admin, API, installer, storage and session routes are disallowed; the public corpus and feeds are allowed.
- `robots.txt` is a crawl hint, not access control — every disallowed path still enforces authentication and authorization (SEC-B-01).
- AI crawlers are an explicit, configurable policy rather than an accident. Three classes:

| Class | Examples | Default |
| --- | --- | --- |
| Search indexing | Googlebot, Bingbot | Allowed |
| User-triggered fetch | ChatGPT-User, PerplexityBot | Allowed |
| Model training | GPTBot, ClaudeBot, CCBot | Configurable, documented in admin |

Admins can change the training class without editing files; the generated file states the current policy and a change is audited.

### Page-level controls

- Default: `index, follow` plus `max-image-preview:large`, `max-snippet:-1`.
- `noindex` on: admin, auth, search-result pages, previews, drafts, print views, paginated filter views, and the Markdown `.md` forms.
- `X-Robots-Tag` for non-HTML responses (Markdown, JSON errors) where a meta tag cannot exist.
- `noarchive` is not used; content removal is handled by deletion plus `410`.

## Sitemaps

`sitemaps.org` protocol, index plus typed children:

```
/sitemap.xml                  → sitemap index
/sitemaps/questions-1.xml.gz
/sitemaps/topics-1.xml.gz
/sitemaps/users-1.xml.gz     (optional, off by default)
/sitemaps/pages-1.xml.gz
```

- Limits honoured: 50,000 URLs and 50 MB uncompressed per file, 50,000 sitemaps per index, UTF-8, entity-escaped, absolute URLs, single host.
- `lastmod` is the content revision timestamp, never the generation time. `changefreq` and `priority` are omitted: crawlers treat them as hints at best and emitting them invites stale guesses.
- Contents: only canonical, indexable, published URLs in the current locale set. No redirects, no `noindex` targets, no `404`s, no private content.
- Generation streams from a keyset-paginated query into an `XMLWriter`, so memory stays bounded regardless of corpus size. Each child is cached and invalidated on publish, not rebuilt per request.
- The index `lastmod` is the newest child `lastmod`; unchanged children are not regenerated.
- `robots.txt` references the index only.
- Large instances may shard per locale and per type; the sharding rule is deterministic so URLs stay stable.

## Feeds

Atom 1.0 (RFC 4287) is the primary feed format, with JSON Feed 1.1 as the agent-friendly equivalent. RSS 2.0 is not maintained: it is a second schema with no capability Atom lacks, and the sitemaps protocol accepts Atom directly.

| Feed | URL |
| --- | --- |
| Site-wide | `/feed/atom.xml`, `/feed/feed.json` |
| Per locale | `/{locale}/feed/atom.xml` |
| Per topic | `/{locale}/topics/{slug}/feed/atom.xml` |
| Per question | `/{locale}/questions/{slug}/feed/atom.xml` |

Entry requirements: unique `<id>` (tag URI or absolute URL), `<title>`, `<updated>` and `<published>` in RFC 3339, an `<author>`, a `<link rel="alternate">` to the HTML page, and a `<link rel="alternate" type="text/markdown">` to the Markdown form. Entry content is the escaped HTML rendering; the Markdown link is the agent path.

Feeds are capped (50 entries), paginated by RFC 5005 `next` links where archives are enabled, served with `ETag` and `Last-Modified` so conditional requests return `304`, and discovered with `<link rel="alternate" type="application/atom+xml">` plus an HTTP `Link` header.

Feeds contain only published public content, and a moderation removal removes the entry on the next build.

## Structured data (JSON-LD)

One `<script type="application/ld+json">` graph per page, with stable `@id` values:

| Page | Types |
| --- | --- |
| Home | `WebSite` + `SearchAction`, `Organization` |
| Question | `QAPage` → `Question` with `acceptedAnswer` / `suggestedAnswer`, `Answer`, `author`, `dateCreated`, `dateModified`, `upvoteCount`, nested `Comment`, `inLanguage`, `isPartOf` |
| Topic / feed | `CollectionPage` + `ItemList` with `url` and `position` |
| Any deep page | `BreadcrumbList` matching the visible breadcrumb |
| Author profile | `Person` with `name`, `url`, `description` |
| Article-style pages (later) | `Article` + `author` + `datePublished` |

Rules:

- Markup must match visible content. No `aggregateRating` unless real ratings exist, no invented `dateModified`, no `FAQPage` used as a rich-result play.
- `QAPage` is the correct type for genuine user-generated question-and-answer pages. `FAQPage` remains available as metadata but Google restricted FAQ rich results to authoritative government and health sites in 2023 and has since retired them for most or all sites — treat it as hygiene, never as a strategy, and re-check Search Central before relying on it.
- IDs are stable URIs (`https://host/questions/42#question`), so the question page and its feed entry reference the same entity.
- No HTML inside properties; text is plain; entities are not pre-escaped.
- Localized pages emit `inLanguage` and localized `name`/`headline` from the translation record; the graph is per locale, not a multi-language blob.

## Social cards

Required by the Open Graph protocol: `og:title`, `og:type`, `og:url`, `og:image`. Recommended and emitted: `og:description`, `og:site_name`, `og:locale` plus `og:locale:alternate` per published translation, `og:image:width`, `og:image:height`, `og:image:alt`, `og:image:type`.

Question pages add the `article` namespace: `article:published_time`, `article:modified_time`, `article:author`, `article:section` (topic), `article:tag`.

X/Twitter: `twitter:card=summary_large_image`, `twitter:title`, `twitter:description`, `twitter:image`, `twitter:image:alt`, `twitter:site`.

Images are generated per question at 1200×630, PNG or JPEG, under 8 MB, cached by content revision (`/og/questions/{id}/{revision}.png`) and served `immutable`. The card contains the question title, site mark and topic — no avatars, no user-supplied remote images, no personal data. A site-wide default card is used when generation is disabled. Alt text is the question title, truncated to a sensible length.

## Markdown for agents

Every published resource has a Markdown representation, following the `/llms.txt` proposal (v2):

- URL: the canonical HTML URL with the extension replaced — `/{locale}/questions/{slug}.md`. A resource without an extension uses `index.md`.
- Content type `text/markdown; charset=utf-8` (RFC 7763), `Content-Language`, `ETag`.
- Discovered by `<link rel="alternate" type="text/markdown" href="…">` and the equivalent `Link` header, plus `rel="describedby"` pointing at the covering `llms.txt`.
- Also available by negotiation on the HTML URL with `Accept: text/markdown`; those routes send `Vary: Accept` and cache per representation.
- The `.md` form is `noindex` (with `X-Robots-Tag`) and links `rel="canonical"` to the HTML page: it exists for agents, not for search results.

Structure of a Markdown page:

```markdown
---
title: How do I rotate database credentials?
url: https://example.com/questions/rotate-db-credentials
locale: en
updated: 2026-11-20T09:12:00Z
author: Ada Example
license: CC-BY-4.0
status: published
---

# How do I rotate database credentials?

> Asked by Ada Example · updated 2026-11-20 · 3 answers

## Accepted answer

…body blocks…

## Answer 2

…body blocks…

## Clarifications

> **Clarification by Bo** — …body…
```

- Answer text is rendered from blocks: headings, lists, fenced code with language, tables, block quotes, links with absolute URLs.
- Citations render as Markdown links to their sources with a `## Sources` list at the end, matching the visible page.
- Raw HTML in user content is never passed through: it is escaped or dropped, links are restricted to `http`, `https` and `mailto`, and bidi control characters are stripped (SEC-C-07).
- Markdown is deterministic for identical content and revision, so an agent can cache by `ETag`.

`/llms.txt` at the root is a curated, bounded index — site summary, top topics, feeds, sitemap, API and MCP endpoints, and links to Markdown forms. It follows the v2 format (H1, blockquote summary, sections of link lists, optional section), is regenerated on publish, and stays small enough to fit in a context window. A large instance may also publish `/topics/llms.txt` for a subtree, which takes precedence for URLs under it.

## Pagination, archives and discovery

- Paginated listings are indexable and self-canonical, with real `<a href>` links. `rel="next"`/`rel="prev"` are not relied on for indexing (Google dropped them in 2019) but remain valid HTML link relations for other consumers.
- "Load more" is progressive enhancement over a real linked page.
- Related questions, breadcrumbs and topic lists are server-rendered links — internal linking is content, not JavaScript.
- Every page exposes `<link rel="canonical">`, the `hreflang` set from [I18N](I18N.md), feed alternates, the Markdown alternate, and `rel="describedby"` for `llms.txt`.

## Performance

SEO is largely page experience: the budgets in [PERFORMANCE](PERFORMANCE.md) apply (LCP ≤1.5 s, INP ≤100 ms, CLS ≤0.02, no JS-only content, no mandatory third-party scripts). Structured data, social tags and alternates add bytes to the head — the HTML budget accounts for them, and they are rendered server-side rather than injected after hydration.

## Generation, caching and freshness

- Sitemap children, feeds, `llms.txt` and card images are cached artifacts keyed by corpus or revision and invalidated per locale on publish (see [ARCHITECTURE](ARCHITECTURE.md) cache rules).
- Publishing enqueues regeneration; the publish request itself never builds an unbounded artifact.
- IndexNow notifications are queued, batched (up to 10,000 URLs per POST) and rate-limited, with the key file served at `/{key}.txt` from the root. Failed notifications are retried and logged, never block publishing (SEO-008).
- Stale artifacts are acceptable for a bounded window (documented, e.g. 60 s) and never serve private content while stale.

## Administration

Site name, logo, default social image, organization profile for JSON-LD, crawler policy per class, IndexNow toggle and key, sitemap status and last generation, per-page overrides for title and description with length validation, canonical override with a warning, and a validation panel showing JSON-LD, feed and sitemap health. Verification tokens for Search Console and Bing are stored as settings, not theme files.

## Security

- **JSON-LD is an injection sink.** Data is encoded with `JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT`, and `</script` can never appear unescaped. No `JSON_UNESCAPED_SLASHES` on user-derived strings.
- **XML output** is built with `XMLWriter` or escaped for XML text and attributes; no DTD processing, no external entities anywhere in the pipeline.
- **Markdown** drops raw HTML and restricts link schemes; it is generated from the model, never converted from user HTML.
- **Non-public leakage** is the highest-risk failure: every artifact is built from the same authorization-filtered query as the HTML, and tests assert that drafts, private questions and moderated content never appear in a sitemap, feed, JSON-LD graph, Markdown response or `llms.txt` (SEC-D-04).
- **No cloaking**: the HTML served to a crawler is byte-identical to what a reader receives for the same URL and locale.
- **Bounded generation**: sitemap and feed endpoints have time and memory limits, cache-first behaviour and rate limits, so a crawler cannot force a full corpus build per request.

## Testing and validation

| Gate | Check |
| --- | --- |
| Sitemap | Validates against the sitemaps.org XSD; respects 50,000/50 MB; every URL returns `200` and is indexable; `lastmod` equals the revision time; no private URL |
| Feeds | Atom validated against RFC 4287 (feedvalidator or equivalent); unique IDs; `updated` present; markdown alternate link resolves |
| JSON-LD | Parses as JSON; `QAPage`/`Question`/`Answer` required properties present; `@id` unique; values equal visible content; no HTML |
| Social cards | Required `og:*` present; image 1200×630 and under 8 MB; `og:url` equals canonical; `og:locale` matches the page |
| Markdown | Snapshot matches the model; links absolute; no raw HTML; `.md` responses carry `noindex` and canonical link; deterministic across runs |
| `llms.txt` | Format follows v2; every link resolves; size bounded |
| Crawl | `robots.txt` parses and references the sitemap; `noindex` never on a canonical page; canonical and `hreflang` reciprocal |
| Leakage | Draft, private and permission-gated content absent from every artifact |
| Leak-free rendering | No step emits unbounded output; generation time bounded on a 100k-document fixture |

## Work items

| Task | Scope |
| --- | --- |
| SEO-001 | Approve this design and ADR 0002 with the contracts |
| SEO-002 | Canonical, robots and indexation controls |
| SEO-003 | JSON-LD graph for questions, answers and breadcrumbs |
| SEO-004 | Open Graph and generated social cards |
| SEO-005 | Sitemap index and streamed child sitemaps |
| SEO-006 | Atom and JSON feeds with discovery |
| SEO-007 | Markdown representations and `llms.txt` |
| SEO-008 | IndexNow publication notifications |
| SEO-009 | SEO regression and structured-data validation suite |

## Open decisions

Whether Markdown is served only at `.md` URLs or also by negotiation (both are specified here; the ADR picks the default), whether RSS 2.0 is ever emitted for compatibility, whether topic archives get RFC 5005 archived feeds, whether `llms.txt` is per-locale or root-only, and whether the organization graph is required or optional at install. All are recorded in [ADR 0002](adr/0002-seo-and-agent-representations.md).

## References

- Sitemaps protocol — https://www.sitemaps.org/protocol.html
- schema.org `QAPage` — https://schema.org/QAPage
- Open Graph protocol — https://ogp.me/
- `/llms.txt` v2 — https://llmstxt.org/
- IndexNow documentation — https://www.indexnow.org/documentation
- Atom Syndication Format — RFC 4287, https://www.rfc-editor.org/rfc/rfc4287
- Robots Exclusion Protocol — RFC 9309, https://www.rfc-editor.org/rfc/rfc9309
- `text/markdown` media type — RFC 7763, https://www.rfc-editor.org/rfc/rfc7763
- HTTP semantics and content negotiation — RFC 9110
- Google structured data general guidelines — https://developers.google.com/search/docs/appearance/structured-data/sd-policies
- Google Q&A page structured data — https://developers.google.com/search/docs/appearance/structured-data/qapage
- Google guidance on optimizing for generative AI — https://developers.google.com/search/docs/fundamentals/ai-optimization-guide
- Google FAQ rich results (status subject to change) — https://developers.google.com/search/docs/appearance/structured-data/faqpage
