#!/usr/bin/env bash
# Install the fresh SLT2 D0-D12 lifecycle run/watch and D13 teardown retry schedule.
set -euo pipefail

PLAN_DIR="/home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public/wp-content/plugins/qa/subscription-lifecycle-test"
SCRIPT="$PLAN_DIR/automation/daily-renewal-check.sh"
TEMPLATE="$PLAN_DIR/automation/slt2-daily-renewal-watch.cron"
CRON_FILE="/etc/cron.d/slt2-daily-renewal-watch"

if [[ $EUID -ne 0 ]]; then
    echo "Must run as root (writes $CRON_FILE)." >&2
    exit 1
fi

if [[ ! -x "$SCRIPT" ]]; then
    echo "Watch script is missing or not executable: $SCRIPT" >&2
    exit 1
fi

if [[ ! -f "$TEMPLATE" ]]; then
    echo "Cron template is missing: $TEMPLATE" >&2
    exit 1
fi

bash -n "$SCRIPT" "$PLAN_DIR/automation/install-cron.sh" "$PLAN_DIR/automation/uninstall-cron.sh"
jq -e . "$PLAN_DIR/automation/key-to-task-id.json" >/dev/null

if [[ ! -f "$PLAN_DIR/kanban/tasks/120-slt-setup-99b-post-watch-teardown-on-2026-09-05.md" ]]; then
    echo "Teardown task 120 is missing; refusing to install the watcher." >&2
    exit 1
fi

install -o root -g root -m 0644 "$TEMPLATE" "$CRON_FILE"

echo "Installed $CRON_FILE"
echo
sed -n '1,120p' "$CRON_FILE"
echo
echo "Verify with: systemctl status cron --no-pager"
echo "Safe date check: $SCRIPT (exits before D0; teardown self-removes only after task 120 is done)"
