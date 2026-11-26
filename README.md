# QuestionAnswer

**Open-source knowledge infrastructure for humans and AI.**

QuestionAnswer is a planned self-hosted Q&A CMS with a lightweight PHP 8.5 framework, nested questions, answers and clarifications, mobile-first progressive enhancement, extensions, REST and MCP. It is designed for ordinary shared hosting.

> Status: pre-alpha planning repository. No CMS runtime is implemented yet. Do not deploy this repository as a CMS.

## Start contributing

Read [project charter](docs/PROJECT_CHARTER.md), [architecture](docs/ARCHITECTURE.md), [AGENTS.md](AGENTS.md), [contribution guide](CONTRIBUTING.md) and [roadmap](docs/ROADMAP.md). Claim an approved issue before coding. Colnera may coordinate contributors and agents; GitHub is the authoritative code and review record.

## Maintainer setup

Create an empty GitHub repository, push this directory, configure branch protection and review ownership, then create issues using `tools/import_issues.py --help`. See [GitHub setup](docs/GITHUB_SETUP.md). The issue importer is optional and requires Python 3 and authenticated GitHub CLI on a maintainer machine, not production.

## Goals

PHP 8.5; HTML/CSS/JS; no mandatory Node, Redis, Docker, SSH or worker on production; installable ZIP; browser installer and recoverable updater; optional AI; public read-only MCP before scoped mutations. Lighthouse 100 in four categories is an aspirational reference-environment target, not a guarantee.

## License

Apache-2.0 is the proposed project license; confirm before accepting contributions. See LICENSE.
