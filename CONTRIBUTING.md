# Contributing

Read [PROJECT_CHARTER.md](docs/PROJECT_CHARTER.md), [ARCHITECTURE.md](docs/ARCHITECTURE.md), [FRAMEWORK_ARCHITECTURE.md](docs/FRAMEWORK_ARCHITECTURE.md), [AGENTS.md](AGENTS.md), and [COLNERA.md](docs/COLNERA.md).

## Workflow

1. **Claim a ready task in Colnera:** Inspect the project board (or via Colnera MCP: `get_project`, `list_tasks`, `get_task`, `claim_task`) and confirm every dependency (`Needs`) is complete.
2. **Branch cleanly:** Create `task/<id>-<slug>` from `main`. Stay strictly within the task's scope and allowed paths.
3. **Implement and verify locally:** Write positive and negative tests, update relevant docs/ADRs, and run local checks (`php -l`, `composer validate --strict`, `python3 tools/validate_backlog.py`, `python3 tools/validate_specs.py`, and test suites as they land).
4. **Submit via PR:** Open a GitHub pull request with real test commands/outputs and impact notes, wait for GitHub Actions CI checks to pass, then submit the PR in Colnera (`submit_work`). Reviewers verify CI status and acceptance criteria before approving.
5. **No self-merge:** Do not merge your own PR. Propose public contract or schema changes through ADRs first. Follow respectful conduct and disclose generated code provenance where required.

## Security rules

Never commit credentials, tokens, private keys, or real user data, and redact them from logs, fixtures, PR descriptions, and task text. Pin every third-party workflow action to a full commit SHA — the policy job fails otherwise. Keep workflow token permissions least-privilege. Every security control you add or touch needs a fail-closed negative test (`SEC-J-01`), and any change to a trust boundary, entry point, outbound path, or credential type updates [the threat model](docs/THREAT_MODEL.md) in the same pull request. New runtime dependencies require justification and review; do not add one to solve a problem a few lines of code can handle. Report vulnerabilities privately through [the security policy](SECURITY.md), never in a public issue or task board comment.
