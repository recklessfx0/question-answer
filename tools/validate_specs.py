"""Validate GitHub Spec Kit folders under specs/ for Colnera Feature 065 import."""
from pathlib import Path
import re
import sys

ROOT = Path(__file__).resolve().parents[1]
SPECS_DIR = ROOT / 'specs'

FOLDER_RE = re.compile(r'^\d{3}-[a-z0-9-]+$')
TASK_RE = re.compile(r'^- \[( |x)\] (T\d{3,4})\s+(.+)$')
NEEDS_RE = re.compile(r'^\s+-\s+\*\*Needs:\*\*\s+(.+?)\.?\s*$')
ID_RE = re.compile(r'^T\d{3,4}$')


def fail(message: str) -> None:
    print(f'ERROR: {message}', file=sys.stderr)
    raise SystemExit(1)


def main() -> None:
    if not SPECS_DIR.is_dir():
        fail('specs/ directory does not exist')

    milestones = sorted(p for p in SPECS_DIR.iterdir() if p.is_dir())
    if not milestones:
        fail('No milestone folders found under specs/')

    graph: dict[str, list[str]] = {}
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
                state, task_id, _rest = task_match.groups()
                if task_id in graph:
                    fail(f'Duplicate task ID {task_id} at {tasks_file.relative_to(ROOT)}:{lineno}')
                graph[task_id] = []
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

    print(
        f'Validated {len(graph)} Spec Kit tasks ({unticked} open, {ticked} completed) '
        f'across {len(milestones)} milestones; dependency graph is acyclic.'
    )


if __name__ == '__main__':
    main()
