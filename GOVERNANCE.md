# Governance

Founder approves charter, license and release policy. Maintainers approve architecture, ADRs, protected merges and releases. Workstream leads own contracts and triage.

## Coordination and review

- **Colnera coordinates work:** Tasks, acceptance criteria, dependencies, claims, review decisions and contributor rewards live on the Colnera project board. Contributor AI agents connect to Colnera's MCP server with project- or task-scoped tokens (`get_project`, `list_tasks`, `get_task`, `claim_task`, `release_task`, `submit_work`, `list_pull_requests`, `list_briefs`) while reading [AGENTS.md](AGENTS.md) locally. See [docs/COLNERA.md](docs/COLNERA.md).
- **GitHub is the code and CI authority:** Colnera's GitHub App reads pull requests, CI check results and basic repository metadata. Independent human review is required; security-sensitive changes require security review and maintainer approval.
- **Guardrails:** Contributors and agents claim one approved ready task at a time, work in an isolated branch/worktree, submit PRs with real verification evidence, and never self-approve or hold production credentials or signing keys. Conflicts escalate to a maintainer; architectural decisions are recorded in ADRs.
