# ADR: Colnera and Spec Kit tooling
Status: proposed

## Context

The project is coordinated in Colnera: people and their AI agents pick up work from a board, an independent reviewer accepts it, and GitHub holds the code, pull requests and CI. Colnera imports milestones and tasks from GitHub Spec Kit folders, so the repository carries two representations of the same plan:

- `backlog/tasks.json` with `backlog/dependencies.json` and `backlog/tasks.csv` — the task ledger, validated by `tools/validate_backlog.py` and consumed by `tools/import_issues.py`.
- `specs/NNN-name/{spec.md,tasks.md}` — the Spec Kit export with `T###` checklist items, `Needs:` lines and accepted-path bullets, validated by `tools/validate_specs.py`.

That duplication already caused a real defect: six tasks (`SEC-010`, `SEC-011`, `MAIL-001`…`MAIL-004`) existed only in `specs/003`, so the ledger that feeds issue creation did not know them while Colnera would have imported them. The parity gate in `tools/validate_specs.py` was added to stop the recurrence, but nothing records why the two exist or which one wins.

Second, the Colnera agent connection is deliberately narrow: the MCP server exposes `get_project`, `list_tasks`, `get_task`, `claim_task`, `release_task`, `list_pull_requests`, `submit_work` and `list_briefs`. There is no create, update, settings or review tool. Agents read, claim and submit; people decide. That boundary is a product decision, not a limitation to work around.

## Decision

**D1 — The ledger is authoritative.** `backlog/tasks.json` defines the work: identifiers, milestone, workstream, priority, dependencies, allowed paths, acceptance and review. `specs/` is a committed projection in the import format Colnera reads.

**D2 — Parity is enforced, not assumed.** `tools/validate_specs.py` fails when a spec task does not reference exactly one backlog id, when an id is referenced twice, or when the two id sets differ in either direction. The error names the file to fix.

**D3 — Agent authority is limited to the product's boundary.** Agents connect with a project-scoped token that expires, and may read, claim, release and submit. Creating or reprioritising work, reviewing, accepting, allocating rewards and managing people stay with humans in the dashboard.

**D4 — Required checks must actually run.** A repository linked to a Colnera project may only be required on checks that execute on every pull request. A conditionally skipped job is not a required check: `submit_work` treats required checks as a gate, and a check that never reports blocks honest submissions.

**D5 — Tooling integrations get an ADR.** Any change to how Colnera, Spec Kit or the GitHub App participate in the workflow is recorded here or in a successor ADR, including token scopes and expiry.

## Alternatives

- **Make `specs/` the source of truth and derive the backlog.** Attractive because the export is what Colnera reads, but the ledger carries fields the export does not (workstream, priority, review roles, machine-checkable dependencies) and `import_issues.py` depends on it. The export would become the schema, which is the wrong direction for a generated artifact.
- **Drop `specs/` and let contributors create tasks in Colnera by hand.** Removes duplication entirely, but loses the 159-task plan, its dependency graph and the paste-ready acceptance criteria that make the board importable in one action.
- **Generate `specs/` in CI and commit it automatically.** Eliminates hand-drift permanently, but adds a bot commit path and a second place where CI writes to the repository. Deferred to `GOV-012`; parity validation is the interim guard.
- **Let agents create tasks over MCP.** Would speed seeding, but removes the human gate on scope, and the product explicitly denies it. Rejected.
- **Require every CI check regardless of whether it runs.** Simple rule, fails honest submissions, and is the bug the demo's #402 display issue produces in practice.

## Security and performance implications

- Tokens are scoped to one project, carry an expiry, and are revoked from the dashboard; they must never be committed, pasted into issues or stored in the repository. A leaked token can claim and submit work, and submissions are visible to reviewers.
- Task text, discussion posts and repository content are written by people and are untrusted input to an agent; they cannot authorise an action (SEC-E-01).
- Claiming is a lock with a time bound (`COLNERA_CLAIM_HOURS`, 48 hours by default); releasing returns work to the board instead of leaving it stale.
- The parity gate is a cheap static check (no network), so it belongs in the existing foundation job rather than a new workflow.

## Shared hosting implications

None on the runtime: Colnera, Spec Kit and the gate are development-time tooling. The production CMS never reaches them, and no credential from this flow ships in the release artifact.

## Compatibility and migration

- `backlog/tasks.json` keeps its schema; `specs/*/tasks.md` keeps its checklist format. Both remain importable by their existing consumers.
- Adding work means: add the task to the ledger with dependencies, regenerate `dependencies.json` and `tasks.csv`, then add the matching `T###` item to the right `specs/NNN/` folder. The validator is the check.
- Removing the Colnera integration later requires only deleting `specs/`, `docs/COLNERA.md` and this ADR; the ledger stays valid on its own.

## Consequences

Positive: one authoritative plan with a checked projection; a written boundary between agent and human authority; honest required checks; the drift defect becomes unrepeatable.

Negative: every new task is edited in two places until `GOV-012` automates the export; the ADR trail grows with each integration.

Neutral: Colnera remains the coordination surface, so contributors need dashboard access to see the board even though the repository holds the plan.

## Approval

Proposed as part of the Colnera end-to-end flow test. Requires an independent maintainer approval, recorded by changing `Status:` to `accepted` with the approver and date. Drafted from `docs/COLNERA.md`, `tools/validate_specs.py` and the Colnera MCP tool surface.
