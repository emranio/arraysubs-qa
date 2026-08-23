#!/usr/bin/env bash
# Stop the fresh SLT2 lifecycle watcher early; reports, evidence, and boards are preserved.
set -euo pipefail

CRON_FILE="/etc/cron.d/slt2-daily-renewal-watch"
LOCK_FILE="/tmp/slt2-daily-renewal-watch.lock"

if [[ $EUID -ne 0 ]]; then
    echo "Must run as root (removes $CRON_FILE)." >&2
    exit 1
fi

if [[ -f "$CRON_FILE" ]]; then
    rm -f "$CRON_FILE"
    echo "Removed $CRON_FILE"
else
    echo "No cron entry at $CRON_FILE — nothing to remove."
fi

rm -f "$LOCK_FILE"
echo "Cleared $LOCK_FILE"
echo "Reports, logs, evidence, and board records were preserved."
