#!/usr/bin/env python3
"""PreToolUse guard: protects generated files, committed migrations and the development database."""

from __future__ import annotations

import re
from fnmatch import fnmatch

from _common import ask, committed, deny, project_path, read_event, tool_input

# (path or directory prefix, why it must not be edited by hand)
GENERATED = [
    ("vendor/", "Composer-managed. Change composer.json and run `make install` (or Composer in the app container)."),
    ("node_modules/", "npm-managed. Change package.json and run npm in the node container."),
    ("public/build/", "Vite output; `make assets` rebuilds it."),
    ("public/hot", "Written by the Vite dev server while it runs."),
    ("bootstrap/cache/", "Laravel's compiled caches."),
    ("storage/framework/", "Laravel's runtime caches, sessions and compiled views."),
    ("composer.lock", "Update it with `composer require|update <package>` in the app container."),
    ("package-lock.json", "Update it with `npm install <package>` in the node container."),
]

# Running the suite outside `make test` inside the container: compose exports APP_ENV=local and
# DB_DATABASE=brgy into $_SERVER, which beats phpunit.xml's force="true", so RefreshDatabase would
# target the development database. Tests\TestCase refuses to boot in that case; this saves the round trip.
TEST_RUN = re.compile(r"\b(php\s+artisan\s+test|vendor/bin/(pest|phpunit)|composer\s+(run\s+)?(test|check))\b")
SAFE_TEST_DB = re.compile(r"DB_DATABASE=(brgy_testing|:memory:)")
WIPES_DATA = re.compile(r"\b(migrate:(fresh|reset|rollback|refresh)|db:wipe)\b|\bmake\s+fresh\b")
DELETES_VOLUMES = re.compile(r"\bdocker[- ]compose\b[^;&|]*\bdown\b[^;&|]*(\s-v\b|--volumes)|\bdocker\s+volume\s+(rm|prune)\b|\bmake\s+clean\b")


def check_bash(command: str) -> None:
    if command.lstrip().startswith("git "):
        return  # commit messages and the like only mention these commands
    if TEST_RUN.search(command) and not SAFE_TEST_DB.search(command):
        deny(
            "Run the suite with `make test`. It passes APP_ENV=testing and DB_DATABASE=brgy_testing as real environment "
            "variables; without them the container's DB_DATABASE=brgy wins over phpunit.xml and RefreshDatabase would target "
            "the development database (Tests\\TestCase refuses to boot in that case). For the SQLite portability run, "
            "use the command in the verify skill."
        )
    if DELETES_VOLUMES.search(command):
        ask("Deletes the Postgres and Redis volumes: the development database, including brgy_testing, is gone.")
    if WIPES_DATA.search(command):
        ask("Drops and rebuilds the development database (re-seeding takes a few seconds, but hand-entered data is lost).")


def check_edit(rel: str) -> None:
    for prefix, reason in GENERATED:
        if rel == prefix or (prefix.endswith("/") and rel.startswith(prefix)):
            if rel.endswith(".gitignore"):
                return
            deny(f"{rel} is not hand-edited: {reason}")
    if fnmatch(rel, "database/migrations/*.php") and committed(rel):
        deny(
            f"{rel} is a committed migration and may already have run against Neon. Add a new migration "
            "(`php artisan make:migration ...` in `make shell`) instead of editing this one."
        )


def main() -> None:
    event = read_event()
    if event.get("tool_name") == "Bash":
        check_bash(tool_input(event).get("command", ""))
        return
    rel = project_path(event)
    if rel is not None:
        check_edit(rel)


if __name__ == "__main__":
    main()
