#!/usr/bin/env python3
"""SessionStart: tell Claude what is runnable on this machine right now."""

from __future__ import annotations

import json

from _common import ROOT, run, running_services


def main() -> None:
    facts = []

    code, branch = run(["git", "branch", "--show-current"], timeout=5)
    if code == 0:
        _, status = run(["git", "status", "--porcelain"], timeout=5)
        dirty = len([line for line in status.splitlines() if line.strip()])
        facts.append(f"Git branch: {branch or '(detached)'}; {dirty} uncommitted path(s).")

    _, remotes = run(["git", "branch", "-r", "--list", "origin/dependabot/*"], timeout=5)
    pending = [line.strip() for line in remotes.splitlines() if line.strip()]
    if pending:
        facts.append(f"{len(pending)} Dependabot branch(es) on origin (as of the last fetch); the dependency-upgrader agent triages them.")

    services = running_services()
    if services is None:
        facts.append("Docker is not available, so make targets (test, analyse, lint, migrate) cannot run in this session.")
    elif "app" in services:
        facts.append(f"Docker Compose services running: {', '.join(services)}. Use the make targets; tests only via `make test`.")
    else:
        facts.append("The Docker Compose stack is stopped; `make up` starts it (first run on a new machine: `make setup`).")

    missing = [name for name in (".env", "vendor") if not (ROOT / name).exists()]
    if missing:
        facts.append(f"Missing: {', '.join(missing)}. `make setup` creates both.")
    if (ROOT / "public/hot").exists():
        facts.append("public/hot exists, so pages load assets from the Vite dev server; if it is not running, styles 404 until public/hot is removed.")

    print(json.dumps({"hookSpecificOutput": {"hookEventName": "SessionStart", "additionalContext": "\n".join(facts)}}))


if __name__ == "__main__":
    main()
