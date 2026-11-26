"""Validate PSR-4 namespaces, strict_types, and four-layer architecture boundaries (ADR 0004 D1)."""
from pathlib import Path
import re
import sys

ROOT = Path(__file__).resolve().parents[1]

STRICT_RE = re.compile(r'declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;')
NAMESPACE_RE = re.compile(r'^\s*namespace\s+([A-Za-z0-9_\\]+)\s*;', re.MULTILINE)
USE_RE = re.compile(r'^\s*use\s+(?:function\s+|const\s+)?\\?([A-Za-z0-9_\\]+)', re.MULTILINE)
FQCN_RE = re.compile(r'\\(QuestionAnswer\\[A-Za-z0-9_\\]+)')
BLOCK_COMMENT_RE = re.compile(r'/\*.*?\*/', re.DOTALL)
LINE_COMMENT_RE = re.compile(r'(^|[^:])//[^\n]*', re.MULTILINE)


def strip_comments(source: str) -> str:
    without_blocks = BLOCK_COMMENT_RE.sub('', source)
    return LINE_COMMENT_RE.sub(r'\1', without_blocks)


def collect_references(code: str) -> list[str]:
    refs: list[str] = []
    for match in USE_RE.finditer(code):
        refs.append(match.group(1).lstrip('\\'))
    for match in FQCN_RE.finditer(code):
        refs.append(match.group(1).lstrip('\\'))
    return refs


def check_layer_rules(rel_parts: tuple[str, ...], refs: list[str]) -> list[str]:
    violations: list[str] = []
    if len(rel_parts) < 2:
        return violations

    top = rel_parts[0]
    layer = rel_parts[1] if top == 'app' else top

    for ref in refs:
        if not ref.startswith('QuestionAnswer\\'):
            continue

        if top == 'app' and layer == 'Contracts':
            if ref.startswith(('QuestionAnswer\\Core\\', 'QuestionAnswer\\Core', 'QuestionAnswer\\Modules\\', 'QuestionAnswer\\Modules', 'QuestionAnswer\\Extensions\\', 'QuestionAnswer\\Extensions')):
                if ref == 'QuestionAnswer\\Core' or ref.startswith('QuestionAnswer\\Core\\') or ref == 'QuestionAnswer\\Modules' or ref.startswith('QuestionAnswer\\Modules\\') or ref == 'QuestionAnswer\\Extensions' or ref.startswith('QuestionAnswer\\Extensions\\'):
                    violations.append(f'Contracts must not depend on {ref}')

        elif top == 'app' and layer == 'Core':
            if ref == 'QuestionAnswer\\Modules' or ref.startswith('QuestionAnswer\\Modules\\') or ref == 'QuestionAnswer\\Extensions' or ref.startswith('QuestionAnswer\\Extensions\\'):
                violations.append(f'Core must not depend on {ref}')

        elif top == 'app' and layer == 'Modules':
            own_module = rel_parts[2] if len(rel_parts) >= 3 else ''
            if ref == 'QuestionAnswer\\Core' or ref.startswith('QuestionAnswer\\Core\\'):
                violations.append(f'Module {own_module} must not depend on Core concrete class {ref}')
            elif ref == 'QuestionAnswer\\Extensions' or ref.startswith('QuestionAnswer\\Extensions\\'):
                violations.append(f'Module {own_module} must not depend on extension {ref}')
            elif ref.startswith('QuestionAnswer\\Modules\\'):
                target_parts = ref.split('\\')
                if len(target_parts) >= 3 and target_parts[2] != own_module:
                    violations.append(f'Module {own_module} must not depend on sibling module {target_parts[2]} ({ref})')

        elif top == 'extensions':
            if ref == 'QuestionAnswer\\Core' or ref.startswith('QuestionAnswer\\Core\\') or ref == 'QuestionAnswer\\Modules' or ref.startswith('QuestionAnswer\\Modules\\'):
                violations.append(f'Extension must not depend on internal {ref}')

    return violations


def main() -> None:
    errors: list[str] = []
    checked_php = 0

    for base_name in ('app', 'config', 'resources', 'extensions'):
        base_dir = ROOT / base_name
        if not base_dir.is_dir():
            continue

        for php_file in sorted(base_dir.rglob('*.php')):
            checked_php += 1
            rel = php_file.relative_to(ROOT)
            raw = php_file.read_text(encoding='utf-8')
            code = strip_comments(raw)

            if not STRICT_RE.search(code):
                errors.append(f'{rel}: missing declare(strict_types=1);')

            if base_name == 'app':
                ns_match = NAMESPACE_RE.search(code)
                expected_ns = 'QuestionAnswer'
                if len(rel.parts) > 2:
                    expected_ns += '\\' + '\\'.join(rel.parts[1:-1])
                if not ns_match:
                    errors.append(f'{rel}: missing namespace declaration (expected {expected_ns})')
                elif ns_match.group(1) != expected_ns:
                    errors.append(f'{rel}: namespace {ns_match.group(1)} does not match PSR-4 path ({expected_ns})')

            if base_name in ('app', 'extensions'):
                refs = collect_references(code)
                for violation in check_layer_rules(rel.parts, refs):
                    errors.append(f'{rel}: {violation}')

    if errors:
        for err in errors:
            print(f'ERROR: {err}', file=sys.stderr)
        raise SystemExit(1)

    print(f'Validated {checked_php} PHP files; PSR-4 namespaces, strict_types and four-layer boundaries are clean.')


if __name__ == '__main__':
    main()
