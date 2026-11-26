# Quality gates
PHP 8.5 lint, PHPUnit unit/integration, PHPStan, coding style, Composer audit, secret scanning, browser tests (Playwright), axe and manual accessibility, Lighthouse CI, load tests, architecture boundary checks, OpenAPI and MCP contract tests, OWASP ZAP in authorized test environment. Test both database engines and shared-host install/upgrade/recovery. Release requires passing tests, no unresolved high/critical security findings, documented performance evidence, accessibility validation, signed artifact, SBOM, tested backups and maintainer approval.

## What CI runs now

`Foundation checks` validates the backlog dependency graph, lints any PHP present and validates the Composer manifest. `Security checks` runs the secret scan (gitleaks), workflow lint (actionlint), the repository policy job (commit-pinned actions, no committed environment files), Python static analysis (bandit, medium and above) and, on pull requests, dependency review once a dependency lockfile exists. None of these are required status checks until branch protection is configured per [GitHub setup](GITHUB_SETUP.md); without that, a red gate is advisory.

## Security evidence

Every control in [security requirements](SECURITY_REQUIREMENTS.md) needs a negative test that fails closed — a test that only proves the happy path does not satisfy SEC-J-01. Security tests belong beside the behaviour they guard, name the control ID they cover, and are listed in a mapping table that the release checklist checks. A control without a negative test is unimplemented regardless of the code present.

## SEO gates

CI validates sitemaps against the sitemaps.org XSD and feeds against RFC 4287; JSON-LD parses and its required properties match visible content; Open Graph and Twitter tags are asserted per page type; Markdown snapshots are deterministic with absolute links and no raw HTML; `llms.txt` format and links are checked; canonical and `hreflang` reciprocity plus `noindex` placement are asserted. Leakage tests prove drafts, private questions and permission-gated content never appear in any artifact. See [SEO](SEO.md) and SEO-002 through SEO-009.


## Localization gates

Contract tests cover `Translator`, `LocaleResolver` and `CatalogueLoader`; per-locale unit tests cover plural categories, selects, number and date patterns and the fallback chain. Negative tests cover unknown and disabled locales, malformed and oversized `Accept-Language`, CRLF injection, missing keys, malformed ICU patterns and bidi controls in input. CI renders every default message under pseudo-localization at 40% expansion, enforces 100% default-locale completeness, and runs RTL snapshots plus hreflang reciprocity. See [localization](I18N.md) and I18N-002 through I18N-011.

The starter validates planning files and PHP syntax only. Implementation teams add the real suites and make the checks required; a release with a waived control records the reason, owner and expiry in the release checklist.
