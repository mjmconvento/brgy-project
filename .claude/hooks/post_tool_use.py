#!/usr/bin/env python3
"""PostToolUse: format the PHP file just written with Pint and surface the rules it touches."""

from __future__ import annotations

import hashlib

from _common import ROOT, clip, has, project_path, read_event, respond, run, running_services

QUERY_BUDGET_FILES = (
    "app/Models/Constituent.php",
    "app/Models/Concerns/HasAddress.php",
    "app/Models/Concerns/HasPersonName.php",
    "app/Queries/DashboardMetrics.php",
    "app/Http/Controllers/ConstituentController.php",
    "app/Http/Controllers/DashboardController.php",
    "resources/views/constituents/index.blade.php",
    "resources/views/dashboard.blade.php",
)


def digest(rel: str) -> str:
    return hashlib.sha256((ROOT / rel).read_bytes()).hexdigest()


def pint(rel: str) -> tuple[str | None, str | None]:
    """(note, problem) after running Pint on one file, where it can run."""
    if not (ROOT / "vendor/bin/pint").exists():
        return None, None
    before = digest(rel)
    if has("php"):
        code, out = run(["php", "vendor/bin/pint", rel])
    elif "app" in (running_services() or []):
        code, out = run(["docker", "compose", "exec", "-T", "-u", "app", "app", "./vendor/bin/pint", rel])
    else:
        return None, None
    if code not in (0, 127):
        return None, f"Pint could not format {rel} (usually a syntax error):\n{clip(out)}"
    if code == 0 and digest(rel) != before:
        return f"Pint reformatted {rel}; re-read it before the next edit.", None
    return None, None


def main() -> None:
    rel = project_path(read_event())
    if rel is None or not (ROOT / rel).is_file():
        return
    notes: list[str] = []
    problems: list[str] = []

    if rel.startswith("app/Models/") and rel.endswith(".php"):
        notes.append(
            "Model changed. A new column belongs in the migration, the model's #[Fillable([...])] and casts(), the FormRequest rules, "
            "the factory, and BarangaySeeder if seeded data needs it. Values that can be derived (like has_unpaid_taxes) stay "
            "accessors plus aggregate scopes, never stored columns."
        )
    if rel.startswith("database/migrations/"):
        notes.append(
            "Integrity rules live in the migration (foreign keys with cascadeOnDelete/nullOnDelete, unique indexes) and are mirrored "
            "as readable validation in the matching FormRequest, as TaxRequest mirrors taxes_period_unique. Production does not "
            "migrate on deploy: migrations are run by hand against Neon's direct endpoint (render.yaml, RUN_MIGRATIONS=false)."
        )
    if rel in QUERY_BUDGET_FILES:
        notes.append(
            "Query counts are pinned by tests: the constituent listing costs exactly 2 queries (DerivedConstituentStateTest) and the "
            "dashboard at most 12 regardless of row count (DashboardTest). Keep work in withListingAggregates()/DashboardMetrics aggregates."
        )
    if rel == "routes/web.php":
        notes.append(
            "Authorization is one boundary: everything except login, registration and the verification round trip sits in the "
            "['auth', 'verified'] group. Route names come from Route::resource; `php artisan route:list` is the source of truth."
        )

    if rel.endswith(".php"):
        note, problem = pint(rel)
        if note:
            notes.append(note)
        if problem:
            problems.append(problem)
    respond("PostToolUse", notes, problems)


if __name__ == "__main__":
    main()
