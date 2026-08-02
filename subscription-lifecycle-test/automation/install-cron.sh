#!/usr/bin/env bash
#
# Install the 12-day SLT daily renewal/email watch as an /etc/cron.d entry.
#
# Runs once per day at 02:10 UTC. The site is UTC+6, so that is 08:10 site-local
# — late enough that anything scheduled for the previous site-day has fired, and
# early enough to leave the whole working day for follow-up.
#
# The watch script itself refuses to run outside D1..D12 and deletes this cron
# entry after the final day, so no manual cleanup is required. Run
# uninstall-cron.sh to stop it early.
#
set -euo pipefail

PLAN_DIR="/home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public/wp-content/plugins/qa/subscription-lifecycle-test"
SCRIPT="$PLAN_DIR/automation/daily-renewal-check.sh"
CRON_FILE="/etc/cron.d/slt-daily-renewal-watch"

if [[ $EUID -ne 0 ]]; then
    echo "Must run as root (writes $CRON_FILE)." >&2
    exit 1
fi

if [[ ! -x "$SCRIPT" ]]; then
    echo "Watch script is missing or not executable: $SCRIPT" >&2
    exit 1
fi

cat >"$CRON_FILE" <<EOF
SHELL=/bin/bash
PATH=/root/.local/bin:/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin
HOME=/root

# ArraySubs subscription-lifecycle QA — daily renewal + email watch.
# Window: D1 (2026-08-03) .. D12 (2026-08-14). The script self-removes this
# file once the window closes. Plan: $PLAN_DIR
10 2 * * * root $SCRIPT
EOF

chmod 0644 "$CRON_FILE"
echo "Installed $CRON_FILE"
echo
cat "$CRON_FILE"
echo
echo "Verify with:  systemctl status cron --no-pager | head"
echo "Dry run now:  $SCRIPT   (safe: it exits quietly outside D1..D12)"
