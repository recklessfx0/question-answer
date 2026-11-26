"""Create GitHub issues from backlog. Dry-run unless --apply. Run once per repository, sequentially."""
import argparse, json, subprocess
from pathlib import Path
root = Path(__file__).resolve().parents[1]
p = argparse.ArgumentParser(description=__doc__)
p.add_argument('--repo', required=True, help='OWNER/REPO')
p.add_argument('--apply', action='store_true')
p.add_argument('--milestone', help='Only import matching milestone')
a = p.parse_args()
tasks = json.loads((root/'backlog/tasks.json').read_text())
if a.milestone: tasks = [t for t in tasks if t['milestone']==a.milestone]
def gh(*args):
    # nosec B603 - fixed binary and argument list, no shell; --repo is passed to gh, never interpolated.
    return subprocess.check_output(['gh', *args, '--repo', a.repo], text=True).strip()
existing = {}
if a.apply:
    for item in json.loads(gh('issue','list','--state','all','--limit','1000','--json','title,number')):
        existing[item['title'].split(' — ',1)[0]] = item['number']
for t in tasks:
    title = t['id']+' — '+t['title']
    deps = ', '.join(t['depends_on']) or 'None'
    body = f"Milestone: {t['milestone']}\nWorkstream: {t['workstream']}\nPriority: {t['priority']}\n\n## Dependencies\n{deps}\n\n## Allowed paths (proposed)\n" + ', '.join(t['allowed_paths']) + '\n\n## Acceptance\n' + t['acceptance'] + '\n\n## Review\n' + ', '.join(t['review']) + '\n\nClaim only after dependencies are complete. Refine scope and acceptance criteria before implementation.'
    if not a.apply: print(title, '|', deps); continue
    if t['id'] in existing: print('Skip existing',t['id']); continue
    print(gh('issue','create','--title',title,'--body',body))
