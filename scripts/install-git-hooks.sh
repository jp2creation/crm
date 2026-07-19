#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(git rev-parse --show-toplevel)"
cd "$ROOT_DIR"

if [ ! -d .git ]; then
    echo "Ce script doit etre lance depuis le depot Git CRM." >&2
    exit 1
fi

if [ ! -f .githooks/pre-commit ]; then
    echo "Hook .githooks/pre-commit introuvable." >&2
    exit 1
fi

chmod +x .githooks/pre-commit
git config core.hooksPath .githooks

echo "Hooks Git installes: core.hooksPath=.githooks"
