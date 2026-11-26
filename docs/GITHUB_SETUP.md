# GitHub setup
1. Confirm license, repository owner, project name and governance. Create an empty repository (no generated README).
2. Extract ZIP, enter QuestionAnswer, `git init -b main`, `git add .`, `git commit -m "Initialize QuestionAnswer project"`, `git remote add origin https://github.com/OWNER/QuestionAnswer.git`, `git push -u origin main`.
3. Configure security settings before the first external contributor:
   - Enable private vulnerability reporting (Settings → Security → Advisories) and confirm the advisory link in [SECURITY.md](../SECURITY.md) resolves.
   - Enable secret scanning with push protection, dependency alerts and Dependabot security updates. Enable the dependency graph — dependency review cannot run without it. Leave the Dependabot version-update config (`.github/dependabot.yml`) enabled — it is the only mechanism that may move a pinned action SHA.
   - Protect `main`: pull request required, independent approval, review from Code Owners, no force push, no branch deletion, no self-merge. Make `Foundation checks / validate` and every `Security checks` job required status checks.
   - Restrict release creation, tags and signing keys to a protected environment with required reviewers.
   - Set workflow permissions to read-only by default. Every action is pinned to a full commit SHA; the `policy` job fails the build on a floating tag, so never merge one.
   - Enable code scanning (CodeQL) when the first PHP or Python application code lands — there is nothing to analyse in the planning starter.
   - Grant CODEOWNERS review enforcement only after replacing the placeholder owner in `.github/CODEOWNERS`.
4. Authenticate GitHub CLI locally. `python3 tools/import_issues.py --repo OWNER/QuestionAnswer --milestone M0` previews; add `--apply` to create. Remove milestone filter for entire backlog. Script skips existing IDs found among up to 1000 issues; run sequentially, not concurrently. It does not create GitHub milestone objects, labels, issue dependencies or projects; dependency IDs and milestone names are in issue bodies. Configure these manually or via verified integration. Use a maintainer token scoped to issues on this repository, never a production credential.
5. **Connect to Colnera:**
   - Install the Colnera GitHub App on the repository (it observes pull requests, CI checks and basic repository details) and link the repository to your Colnera project.
   - Ensure required CI workflows (`Foundation checks / validate` and `Security checks`) run on every pull request so Colnera reviewers see clear pass/fail signals before accepting submissions.
   - Use the GitHub Spec Kit folders under [`specs/`](../specs/001-m0-governance-and-contracts/tasks.md) for automatic milestone and draft-task import once Colnera Feature 065 ships (or create starter tasks directly on the Colnera board from those files). See [COLNERA.md](COLNERA.md).
   - Keep [AGENTS.md](../AGENTS.md) and [CLAUDE.md](../CLAUDE.md) up to date with local test commands — contributor agents read them on their own machines while coordinating through Colnera's MCP server.
6. Refine task-specific acceptance tests before assignment. M0 specifications first, then framework. The CI starter covers backlog validation, PHP lint and the security gates; it is not a full quality pipeline and must be expanded in GOV-006. No repository or remote changes were made by this package.
