# Security policy

QuestionAnswer is pre-alpha planning software. No supported production release exists and no CMS runtime is implemented. In scope today are repository content and configuration, GitHub Actions workflows and their permissions, and maintainer tooling in `tools/`. Never disclose a suspected vulnerability in a public issue, pull request, discussion or backlog entry.

## Supported versions

No release line is supported yet. Security fixes land on `main` only. Forks, modified copies and private deployments are not covered — reproduce against current `main` before reporting. Support begins with the first published release, at which point this section names the maintained line and its fix window.

## Reporting a vulnerability

Use GitHub private vulnerability reporting: repository **Security and quality** tab → **Advisories** → **Report a vulnerability**. That is the only reporting channel. If the form is unavailable, open a public issue that asks for a private channel and contains no technical detail.

Include:

- Affected file, workflow or component, plus the commit hash.
- Impact: the capability an attacker gains that they did not already have.
- Reproduction steps, or for a static finding the exact input-to-sink chain with file and line at every step.
- Environment where relevant (PHP version, database, web server, host type).
- Suggested remediation, if you have one.

Provide redacted logs. Never send credentials, tokens, personal data or unredacted third-party content.

## Scope

Repository content and Git configuration, `.github/` workflows, templates and permissions, `tools/` scripts and their use of the GitHub CLI, and — once implementation starts — the runtime classes enumerated in [security requirements](docs/SECURITY_REQUIREMENTS.md): authentication and session management, resource authorization, injection and contextual escaping, upload and file handling, SSRF, cryptographic misuse, installer and updater integrity, extension loading, MCP and AI tool authorization, secret exposure, and log redaction.

Threat boundaries and abuse cases are maintained in the [threat model](docs/THREAT_MODEL.md); the deployment baseline is [hardening](docs/HARDENING.md).

## Out of scope

- Missing headers, TLS settings or hardening options with no demonstrated impact.
- Theoretical findings without a reachable path, a test or a proof of consequence.
- Denial of service, spam, resource exhaustion or rate-limit grinding.
- Social engineering of maintainers or contributors.
- Vulnerabilities in third-party dependencies that do not affect this project — report them upstream and link the advisory.
- Documentation wording, typos, design disagreements or proposed features.
- Findings that require privileges the attacker would already need to be an administrator or host operator.
- Reports that this project, once it exists, could be deployed insecurely — operator misconfiguration is covered by [hardening](docs/HARDENING.md), not by a vulnerability.

Declined reports may be converted into public hardening tasks, with credit.

## Safe harbor

We will not pursue legal action for good-faith research that follows this policy, stays within scope, avoids privacy violations and data destruction, and gives us reasonable time to fix before disclosure. This does not exempt you from applicable law.

## What to expect

Maintenance is best effort. We aim to acknowledge within seven days and to give an initial assessment within fourteen. No fix deadline is promised.

## If a report is accepted

We open a private GitHub security advisory, develop the fix off the public branch when the report is sensitive, credit the reporter if desired, request a CVE where warranted, and publish an advisory naming the fixed commit. Default disclosure is 90 days after a fix is available, or earlier by mutual agreement; sooner if the vulnerability is exploited in the wild.

## If a report is declined

We explain why: not a vulnerability, out of scope, unreproducible, duplicate, or dependent on already-compromised privileges. The reasoning is recorded on the report, not on a public issue.

## Disclosure

Do not publish exploit details or a public proof of concept before a fix is available or the coordinated window ends. Do not test systems you do not own. This project does not conduct offensive testing of any kind.
