# ADR: SEO and agent representations
Status: proposed

## Context

The charter promises a public Q&A site; the roadmap has no SEO workstream, and the only SEO statements in the repository are a Lighthouse score projection and a single "canonical URLs" clause in the design system. Meanwhile the product's audience is explicitly two-sided: people via search, and agents via MCP, REST and now direct page fetches. Both audiences need the same content in different shapes, and both are unforgiving about duplication: a search engine penalises a page that lies about itself, and an agent fails on a page it cannot parse.

Retrofitting this later is expensive in three specific ways. Machine-readable forms are usually bolted on as plugins that scrape rendered HTML, which loses structure (code fences, tables, nested answers) and drifts from the page. Structured data added after the fact tends to describe what marketing wants rather than what the page shows, which violates Google's general guidelines. And agent-facing copies become a second, unaudited query path that leaks drafts and private content.

Constraints: PHP 8.5 on shared hosting, server-rendered HTML with progressive enhancement, no mandatory JavaScript, localization already decided in [ADR 0001](0001-localization.md) with per-locale canonical and `hreflang`, and the security baseline in [SECURITY_REQUIREMENTS](../SECURITY_REQUIREMENTS.md).

## Decision

**D1 — One document model, many representations.** Content is stored as a structured document (blocks plus metadata). HTML, Markdown, JSON-LD, feed entries and sitemap entries are pure renderings of that model. No representation is produced by scraping another. This is the load-bearing decision: it makes structural fidelity in Markdown, accurate structured data and cheap feeds fall out of one implementation.

**D2 — SEO is core, not an extension.** Canonical, `robots.txt`, sitemaps, feeds, structured data, social cards and Markdown output ship in the core framework and are enabled by default. Extensions may extend metadata (a custom schema type, an extra feed) but cannot replace the pipeline.

**D3 — URL and canonical policy.** One canonical HTML URL per resource per locale; self-referencing canonical on every indexable page; paginated pages self-canonical; filters and tracking parameters canonicalize or are `noindex`; slugs immutable with a stored `301` map; `404`/`410` distinguished. Locale prefixes and `hreflang` follow ADR 0001.

**D4 — Sitemaps.** A sitemap index at `/sitemap.xml` with typed, sharded children (`questions`, `topics`, `pages`, optional `users`), gzip-compressed. `lastmod` is the content revision timestamp. `changefreq` and `priority` are omitted. Children stream from keyset-paginated queries into `XMLWriter`, are cached, and are invalidated per locale on publish. Reserved for the protocol: 50,000 URLs and 50 MB uncompressed per file, 50,000 sitemaps per index.

**D5 — Feeds.** Atom 1.0 (RFC 4287) is the primary format, with JSON Feed 1.1 as the agent-friendly equivalent. RSS 2.0 is not maintained. Feeds are capped, conditional-GET capable, discovered via `<link rel="alternate">` and a `Link` header, and each entry links both its HTML page and its Markdown form.

**D6 — Structured data.** One JSON-LD graph per page with stable `@id`s: `WebSite` + `SearchAction` and `Organization` on home; `QAPage` → `Question` → `Answer`/`Comment` on question pages; `BreadcrumbList` wherever breadcrumbs render; `CollectionPage` + `ItemList` for listings; `Person` for authors. Markup describes only visible content. `FAQPage` is not used as a rich-result strategy — Google restricted FAQ rich results to authoritative government and health sites in 2023 and has since retired them for most or all sites; the status is re-checked before any dependency on it.

**D7 — Social cards.** Open Graph required properties plus locale alternates and `article` timestamps for question pages; X/Twitter `summary_large_image`; per-question 1200×630 images generated from the title, site mark and topic, cached by revision and served immutable. No remote user images, no avatars in cards.

**D8 — Markdown and `llms.txt` for agents.** Every published resource has a Markdown form at the HTML URL with `.md` replacing the extension; content type `text/markdown`, discovered by `rel="alternate" type="text/markdown"` and a `Link` header. The `.md` form is `noindex` and links canonical to the HTML page, so it serves agents without competing in search. `/llms.txt` (v2 format) is a curated, bounded index regenerated on publish. Raw HTML is dropped, link schemes restricted, bidi controls stripped.

**D9 — Negotiation is opt-in per route.** `Accept: text/markdown` on HTML URLs is supported and sends `Vary: Accept` with a separate cache entry. The `.md` URL is the primary, cache-friendly path; negotiation is a convenience for agents that only know the HTML URL. A misconfigured `Vary` is a cache-poisoning risk, so the contract is explicit: only routes that can return Markdown send `Vary: Accept`.

**D10 — Crawl and freshness.** `robots.txt` is generated with admin/API/storage disallowed and the sitemap referenced; AI crawler policy is explicit and configurable across three classes (search indexing, user-triggered fetch, model training). Publishing enqueues sitemap/feed/`llms.txt` regeneration and IndexNow notifications (batched, up to 10,000 URLs, key file at the root), and never blocks the publish request.

## Alternatives

- **A plugin ecosystem for SEO (Yoast-style).** Familiar, but puts the most failure-prone output on the extension surface, allows a site to ship without basic markup, and cannot enforce the "matches visible content" rule across third-party code. Rejected as the core mechanism; extensions may add metadata, not replace it.
- **Convert HTML to Markdown on request.** Zero new storage, but lossy exactly where Q&A needs fidelity — nested answers, code blocks, tables, citations — and two code paths drift. Rejected by D1.
- **A separate "agent site" (`/llm/...`).** Clean separation, but doubles the routing and authorization surface and guarantees leakage bugs. Rejected; representations live on the same resources.
- **RSS 2.0 as well as Atom.** Wider legacy reader support, but a second schema to validate and keep consistent for marginal benefit. Rejected; JSON Feed covers the agent case better.
- **`FAQPage` markup for rich results.** Historically attractive, now largely obsolete; treating it as a strategy invites marking up content that is not a real FAQ. Rejected as a strategy, retained as optional metadata.
- **Client-side JSON-LD injection via JavaScript.** Avoids touching templates, but search engines may miss or delay it and it duplicates data already server-rendered. Rejected.
- **Serving Markdown at a separate path (`/raw/...`).** Simpler caching, but no standard discovery and no relation to the canonical URL. Rejected in favour of the `.md` extension convention.

## Security and performance implications

- JSON-LD is an injection sink: user text inside a `<script>` block must be encoded with hex flags so `</script>` cannot terminate it. An XML writer, not string concatenation, produces sitemaps and feeds.
- The highest-severity failure is leakage: a sitemap, feed, JSON-LD graph or Markdown page exposing a draft, a private question or another user's content. Every artifact is built from the same authorization-filtered query as the HTML, and tests assert absence (SEC-D-04).
- `.md` responses are `noindex` and canonical to HTML, so agents get content without creating duplicate search entries; `Vary: Accept` is limited to negotiating routes to avoid cache confusion.
- Generation is bounded and cached. A 100k-document corpus must not be built in a request: keyset pagination, streaming writers, time and memory limits, rate limiting on artifact endpoints.
- Social card images are generated server-side from title text; that is an image-generation path with its own bounds (size, fonts, cache, no remote input) and must not become an SSRF or memory amplifier.
- IndexNow key files and notifications are outbound requests; they follow the SSRF rules and never block publishing.

## Shared hosting implications

- Everything is files and database reads; there is no queue requirement. Regeneration runs from the optional cron or opportunistically after publish, bounded per run.
- Cached artifacts live in the file cache, so a host without Redis still serves sitemaps and feeds at static-file cost.
- Gzip is produced by the application for sitemaps and images are pre-generated, so no server module is required.
- Image generation needs a GD or Imagick build; when absent, the default site card is used and the admin reports the reduced capability rather than failing a publish.
- `llms.txt`, `robots.txt` and `.md` responses are ordinary routes, so no rewrite gymnastics beyond the existing front controller.

## Compatibility and migration

- New routes only: `/sitemap.xml`, `/sitemaps/*`, `/feed/*`, `/llms.txt`, `*.md`, `/robots.txt`, `/{key}.txt`. Existing content URLs are unchanged.
- Existing content becomes renderable in Markdown only when its body is stored as blocks; a migration converts legacy HTML bodies to blocks once, with a report of unconvertible constructs, and Markdown rendering is exact from then on.
- Structured data starts at `QAPage` for questions; adding `Article` and others later is additive.
- Turning Markdown exposure off is a setting: the routes and their links disappear, no content changes.
- Public feed URLs should be considered stable once published; changing them later requires redirects, so the shapes in D5 are decided before release.

## Consequences

Positive: search and agent output are consistent by construction, one implementation serves five formats, leakage is testable in one place, and the site is useful to answer engines without a separate product. Negative: the content model must be blocks before Markdown is exact, image generation adds a hosting dependency, and artifact caching needs careful invalidation. Neutral: more routes and more tests, offset by fewer plugin integrations.

## Approval

Proposed by the SEO workstream. Requires independent maintainer approval, recorded by changing `Status:` to `accepted` with the approving maintainer and date, together with the contracts in `app/Contracts/Seo/`. D1, D6 and D8 are the decisions most expensive to reverse and are the review focus. Until approved, no implementation task starts.
