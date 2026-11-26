from pathlib import Path
import json
root = Path(__file__).resolve().parents[1]
tasks = json.loads((root / 'backlog/tasks.json').read_text())
graph = {t['id']: t['depends_on'] for t in tasks}
assert len(graph) == len(tasks), 'Duplicate task IDs'
assert graph == json.loads((root / 'backlog/dependencies.json').read_text()), 'Dependency export differs'
visiting, done = set(), set()
def visit(key):
    assert key in graph, f'Unknown dependency {key}'
    assert key not in visiting, f'Cycle at {key}'
    if key in done: return
    visiting.add(key)
    for dep in graph[key]: visit(dep)
    visiting.remove(key); done.add(key)
for key in graph: visit(key)
print(f'Validated {len(tasks)} tasks; dependency graph is acyclic.')
