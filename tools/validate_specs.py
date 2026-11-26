"""Validate GitHub Spec Kit folders under specs/ for Colnera Feature 065 import.

Also cross-checks the export against backlog/tasks.json: the backlog is the task
ledger and specs/ is its Colnera projection, so a task may not exist in one and
not the other. Drift fails CI instead of being discovered at import time.
"""
from pathlib import Path
import json
import re
import sys

ROOT = Path(__file__).resolve().parents[1]
SPECS_DIR = ROOT / 'specs'
BACKLOG_FILE = ROOT / 'backlog' / 'tasks.json'

FOLDER_RE = re.compile(r'^\d{3}-[a-z0-9-]+$')
TASK_RE = re.compile(r'^- \[( |x)\] (T\d{3,4})\s+(.+)$')
NEEDS_RE = re.compile(r'^\s+-\s+\*\*Needs:\*\*\s+(.+?)\.?\s*$')
ID_RE = re.compile(r'^T\d{3,4}$')
BACKLOG_ID_RE = re.compile(r'\(`?([A-Z0-9]+-\d{3})`?\)')


def fail(message: str) -> None:
    print(f'ERROR: {message}', file=sys.stderr)
    raise SystemExit(1)


def load_backlog_ids() -> set[str]:
    if not BACKLOG_FILE.is_file():
        fail(f'{BACKLOG_FILE.relative_to(ROOT)} does not exist')
    try:
        tasks = json.loads(BACKLOG_FILE.read_text(encoding='utf-8'))
    except json.JSONDecodeError as error:
        fail(f'{BACKLOG_FILE.relative_to(ROOT)} is not valid JSON: {error}')
    ids = [task.get('id') for task in tasks]
    if any(not isinstance(task_id, str) or not task_id for task_id in ids):
        fail(f'{BACKLOG_FILE.relative_to(ROOT)} contains a task without a string id')
    duplicates = {task_id for task_id in ids if ids.count(task_id) > 1}
    if duplicates:
        fail(f'Duplicate backlog ids: {sorted(duplicates)}')
    return set(ids)


def main() -> None:
    if not SPECS_DIR.is_dir():
        fail('specs/ directory does not exist')

    milestones = sorted(p for p in SPECS_DIR.iterdir() if p.is_dir())
    if not milestones:
        fail('No milestone folders found under specs/')

    graph: dict[str, list[str]] = {}
    spec_refs: dict[str, list[str]] = {}
    unticked = 0
    ticked = 0

    for folder in milestones:
        if not FOLDER_RE.match(folder.name):
            fail(f'Invalid Spec Kit folder name: {folder.name}')

        spec_file = folder / 'spec.md'
        tasks_file = folder / 'tasks.md'
        if not spec_file.is_file():
            fail(f'Missing {spec_file.relative_to(ROOT)}')
        if not tasks_file.is_file():
            fail(f'Missing {tasks_file.relative_to(ROOT)}')

        current_id: str | None = None
        for lineno, raw_line in enumerate(tasks_file.read_text(encoding='utf-8').splitlines(), start=1):
            task_match = TASK_RE.match(raw_line)
            if task_match:
                state, task_id, rest = task_match.groups()
                if task_id in graph:
                    fail(f'Duplicate task ID {task_id} at {tasks_file.relative_to(ROOT)}:{lineno}')
                graph[task_id] = []
                spec_refs[task_id] = BACKLOG_ID_RE.findall(rest)
                current_id = task_id
                if state == 'x':
                    ticked += 1
                else:
                    unticked += 1
                continue

            needs_match = NEEDS_RE.match(raw_line)
            if needs_match:
                if current_id is None:
                    fail(f'Needs line without preceding task at {tasks_file.relative_to(ROOT)}:{lineno}')
                deps = [d.strip().rstrip('.') for d in needs_match.group(1).split(',') if d.strip()]
                if not deps:
                    fail(f'Empty Needs list for {current_id} at {tasks_file.relative_to(ROOT)}:{lineno}')
                for dep in deps:
                    if not ID_RE.match(dep):
                        fail(f'Invalid dependency token "{dep}" for {current_id} at {tasks_file.relative_to(ROOT)}:{lineno}')
                    graph[current_id].append(dep)
                continue

            if raw_line.startswith('- ['):
                fail(f'Malformed checklist item at {tasks_file.relative_to(ROOT)}:{lineno}: {raw_line}')

    if not graph:
        fail('No tasks found across specs/*/tasks.md')

    for task_id, refs in spec_refs.items():
        if len(refs) != 1:
            fail(
                f'{task_id} must reference exactly one backlog id in parentheses, found {len(refs)}: {refs}'
            )

    visiting: set[str] = set()
    done: set[str] = set()

    def visit(key: str) -> None:
        if key not in graph:
            fail(f'Unknown dependency {key}')
        if key in visiting:
            fail(f'Cycle detected at {key}')
        if key in done:
            return
        visiting.add(key)
        for dep in graph[key]:
            visit(dep)
        visiting.remove(key)
        done.add(key)

    for tid in graph:
        visit(tid)

    backlog_ids = load_backlog_ids()
    spec_backlog_ids = {refs[0] for refs in spec_refs.values()}
    duplicated_refs = sorted(
        backlog_id for backlog_id in spec_backlog_ids
        if [refs[0] for refs in spec_refs.values()].count(backlog_id) > 1
    )
    if duplicated_refs:
        fail(f'Backlog task referenced by more than one spec task: {duplicated_refs}')

    missing_from_specs = sorted(backlog_ids - spec_backlog_ids)
    missing_from_backlog = sorted(spec_backlog_ids - backlog_ids)
    if missing_from_specs or missing_from_backlog:
        details = []
        if missing_from_specs:
            details.append(
                'missing from specs/ (add the task to specs/*/tasks.md or remove it from backlog/tasks.json): '
                + ', '.join(missing_from_specs)
            )
        if missing_from_backlog:
            details.append(
                'missing from backlog/tasks.json (add the task to the ledger and its dependencies export): '
                + ', '.join(missing_from_backlog)
            )
        fail('Backlog and Spec Kit export disagree. ' + ' | '.join(details))

    print(
        f'Validated {len(graph)} Spec Kit tasks ({unticked} open, {ticked} completed) '
        f'across {len(milestones)} milestones; dependency graph is acyclic; '
        f'backlog and specs agree on {len(backlog_ids)} task ids.'
    )


if __name__ == '__main__':
    main()
