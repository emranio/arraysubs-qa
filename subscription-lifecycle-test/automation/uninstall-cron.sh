#!/usr/bin/env bash
#
# Stop the SLT daily renewal/email watch early.
#
# The watch normally removes its own cron entry after D12 (2026-08-13); this is
# only needed to end the run ahead of schedule. Reports, logs, and the board are
# left untouched.
#
set -euo pipefail

CRON_FILE="/etc/cron.d/slt-daily-renewal-watch"
LOCK_FILE="/tmp/slt-daily-renewal-watch.lock"

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
echo "Reports and logs kept under the plan directory."
