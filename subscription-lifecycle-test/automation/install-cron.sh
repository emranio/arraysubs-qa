#!/usr/bin/env bash
#
# Install the 12-day SLT daily renewal/email watch as an /etc/cron.d entry.
#
# Runs at 02:10, 06:10, 12:10, 15:10 and 17:42 in the host's Europe/Berlin
# timezone. During this August run (CEST, UTC+2), those are 06:10, 10:10,
# 16:10, 19:10 and 21:42 at the site's fixed UTC+6 offset. The phases cover
# early/late morning verification, afternoon purchases, and evening/night gates.
# The invoked watcher may remain alive until a hard gate that falls before the
# next phase; it polls the clock in intervals no longer than 60 seconds.
#
# The watch script refuses to run before D1, watches D1..D12, retries final
# teardown from D13 onward, and deletes this cron entry only after task 119 is
# confirmed done. Run
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
CRON_TZ=Europe/Berlin
# ArraySubs subscription-lifecycle QA — daily renewal + email watch.
# Window: D1 (2026-08-03) .. D12 (2026-08-14), then D13 teardown retries from
# 2026-08-15 until task 119 is done. Plan: $PLAN_DIR
10 2,6,12,15 * * * root $SCRIPT
42 17 * * * root $SCRIPT
EOF

chmod 0644 "$CRON_FILE"
echo "Installed $CRON_FILE"
echo
cat "$CRON_FILE"
echo
echo "Verify with:  systemctl status cron --no-pager | head"
echo "Dry run now:  $SCRIPT   (safe: it exits quietly before D1 and self-removes only after task 119 is done)"
