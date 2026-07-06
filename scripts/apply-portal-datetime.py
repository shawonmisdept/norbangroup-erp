#!/usr/bin/env python3
"""Replace 24h display formats with @portal* Blade directives."""

import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1] / "resources" / "views"

REPLACEMENTS = [
    (re.compile(r"\{\{\s*(\$[a-zA-Z0-9_>?\-\[\]'\"]+)->format\('H:i'\)\s*\?\?\s*'—'\s*\}\}"), r"@portalTime(\1)"),
    (re.compile(r"\{\{\s*(\$[a-zA-Z0-9_>?\-\[\]'\"]+)->format\('H:i'\)\s*\}\}"), r"@portalTime(\1)"),
    (re.compile(r"\{\{\s*(\$[a-zA-Z0-9_>?\-\[\]'\"]+)->format\('d M Y H:i:s'\)\s*\}\}"), r"@portalDateTimeSeconds(\1)"),
    (re.compile(r"\{\{\s*(\$[a-zA-Z0-9_>?\-\[\]'\"]+)->format\('d M Y, H:i'\)\s*\?\?\s*'—'\s*\}\}"), r"@portalDateCommaTime(\1)"),
    (re.compile(r"\{\{\s*(\$[a-zA-Z0-9_>?\-\[\]'\"]+)->format\('d M Y, H:i'\)\s*\}\}"), r"@portalDateCommaTime(\1)"),
    (re.compile(r"\{\{\s*(\$[a-zA-Z0-9_>?\-\[\]'\"]+)->format\('d M Y H:i'\)\s*\?\?\s*'—'\s*\}\}"), r"@portalDateTime(\1)"),
    (re.compile(r"\{\{\s*(\$[a-zA-Z0-9_>?\-\[\]'\"]+)->format\('d M Y H:i'\)\s*\}\}"), r"@portalDateTime(\1)"),
    (re.compile(r"\{\{\s*(\$[a-zA-Z0-9_>?\-\[\]'\"]+)->format\('d M H:i'\)\s*\?\?\s*'—'\s*\}\}"), r"@portalDateTimeShort(\1)"),
    (re.compile(r"\{\{\s*(\$[a-zA-Z0-9_>?\-\[\]'\"]+)->format\('d M H:i'\)\s*\}\}"), r"@portalDateTimeShort(\1)"),
]

MANUAL = {
    ROOT / "admin/hrm/attendance/daily.blade.php": [
        ("{{ $log->check_in?->format('H:i') ?? '—' }}", "@portalTime($log->check_in)"),
        ("{{ $log->check_out?->format('H:i') ?? '—' }}", "@portalTime($log->check_out)"),
        ("{{ $photoPunch->punched_at->format('g:i A') }}", "@portalTime($photoPunch->punched_at)"),
    ],
    ROOT / "admin/masters/partials/column.blade.php": [
        (
            "{{ \\App\\Support\\TimeInput::formatForInput($record->{$column}) }}",
            "{{ \\App\\Support\\TimeInput::formatForDisplay($record->{$column}) }}",
        ),
    ],
    ROOT / "admin/hrm/attendance/manual-punch/form.blade.php": [
        (
            "old('punch_time', $punch->punched_at?->format('H:i') ?? '08:00')",
            "old('punch_time', isset($punch) ? \\App\\Support\\TimeInput::formatForInput($punch->punched_at) : '08:00')",
        ),
    ],
}


def main() -> None:
    updated = 0

    for path in ROOT.rglob("*.blade.php"):
        content = path.read_text(encoding="utf-8")
        original = content

        for pattern, repl in REPLACEMENTS:
            content = pattern.sub(repl, content)

        if path in MANUAL:
            for old, new in MANUAL[path]:
                content = content.replace(old, new)

        if content != original:
            path.write_text(content, encoding="utf-8")
            updated += 1
            print(path.relative_to(ROOT.parents[1]))

    print(f"Updated {updated} files")


if __name__ == "__main__":
    main()
