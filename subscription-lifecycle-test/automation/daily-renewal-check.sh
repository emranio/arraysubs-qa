#!/usr/bin/env bash
#
# SLT daily renewal + email watch.
#
# Runs once per day for the 12 days following D0 (2026-08-01). Each run:
#   1. Refuses to overlap with a previous run (flock).
#   2. Works out which watch day it is; exits quietly outside D1..D12.
#   3. Collects a deterministic facts snapshot (WP-CLI + Action Scheduler + Mailpit)
#      so the agent starts from real data instead of re-deriving it.
#   4. Invokes Codex non-interactively against that day's row of
#      watch-schedule.md, which tells it what should have happened overnight
#      and which browser test tasks are due that day.
#   5. Persists the report, the facts snapshot, and a one-line run summary.
#
# Installed by install-cron.sh as /etc/cron.d/slt-daily-renewal-watch.
#
set -uo pipefail

D0="2026-08-02"          # plan day zero
LAST_WATCH_DAY=12        # D1..D12 inclusive
CODEX_TIMEOUT=5400       # 90 minutes; browser test days are long

PLAN_DIR="/home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public/wp-content/plugins/qa/subscription-lifecycle-test"
PLUGIN_ROOT="/home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public/wp-content/plugins"
WP_ROOT="/home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public"

AUTOMATION_DIR="$PLAN_DIR/automation"
LOG_DIR="$AUTOMATION_DIR/logs"
REPORT_DIR="$PLAN_DIR/watch-reports"
LOCK_FILE="/tmp/slt-daily-renewal-watch.lock"
RUN_LOG="$LOG_DIR/run-summary.log"

export HOME="${HOME:-/root}"
export PATH="/root/.local/bin:/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin"

mkdir -p "$LOG_DIR" "$REPORT_DIR"

# --- single instance ----------------------------------------------------------
exec 9>"$LOCK_FILE"
if ! flock -n 9; then
    echo "$(date -Is) SKIP another watch run already holds $LOCK_FILE" >>"$RUN_LOG"
    exit 0
fi

# --- which watch day is it? ---------------------------------------------------
today="$(date +%F)"
d0_epoch="$(date -d "$D0" +%s)"
today_epoch="$(date -d "$today" +%s)"
DAY=$(( (today_epoch - d0_epoch) / 86400 ))

if (( DAY < 1 )); then
    echo "$(date -Is) SKIP day=$DAY is before the watch window" >>"$RUN_LOG"
    exit 0
fi
if (( DAY > LAST_WATCH_DAY )); then
    echo "$(date -Is) DONE day=$DAY is past D$LAST_WATCH_DAY; removing cron entry" >>"$RUN_LOG"
    rm -f /etc/cron.d/slt-daily-renewal-watch
    exit 0
fi

DAY_LABEL="$(printf 'D%02d' "$DAY")"
STAMP="$(date +%F)"
REPORT_FILE="$REPORT_DIR/${DAY_LABEL}-${STAMP}.md"
FACTS_FILE="$LOG_DIR/${DAY_LABEL}-${STAMP}-facts.txt"
CODEX_LOG="$LOG_DIR/${DAY_LABEL}-${STAMP}-codex.log"

echo "$(date -Is) START $DAY_LABEL ($STAMP)" >>"$RUN_LOG"

# --- deterministic facts snapshot ---------------------------------------------
# Everything here is read-only. The agent gets this verbatim so it never has to
# guess at state it could simply have been handed.
{
    echo "# SLT watch facts — $DAY_LABEL ($STAMP)"
    echo "Generated: $(date -Is)  |  UTC: $(date -u +'%F %T')  |  site TZ is UTC+6"
    echo

    echo "## Subscriptions by status"
    wp --path="$WP_ROOT" --allow-root db query \
        "SELECT post_status, COUNT(*) AS n FROM wp_posts WHERE post_type='arraysubs_data' GROUP BY post_status;" 2>&1

    echo
    echo "## SLT subscriptions (created on/after $D0) with schedule meta"
    wp --path="$WP_ROOT" --allow-root db query "
        SELECT p.ID,
               p.post_status,
               p.post_date,
               MAX(CASE WHEN pm.meta_key='_customer_id'                    THEN pm.meta_value END) AS customer,
               MAX(CASE WHEN pm.meta_key='_product_id'                     THEN pm.meta_value END) AS product,
               MAX(CASE WHEN pm.meta_key='_payment_method'                 THEN pm.meta_value END) AS gateway,
               MAX(CASE WHEN pm.meta_key='_next_payment_date'              THEN pm.meta_value END) AS next_payment,
               MAX(CASE WHEN pm.meta_key='_trial_end_date'                 THEN pm.meta_value END) AS trial_end,
               MAX(CASE WHEN pm.meta_key='_end_date'                       THEN pm.meta_value END) AS end_date,
               MAX(CASE WHEN pm.meta_key='_completed_payments'             THEN pm.meta_value END) AS cycles,
               MAX(CASE WHEN pm.meta_key='_recurring_amount'               THEN pm.meta_value END) AS recurring,
               MAX(CASE WHEN pm.meta_key='_renewal_sync_first_charge_mode' THEN pm.meta_value END) AS sync_mode,
               MAX(CASE WHEN pm.meta_key='_renewal_sync_first_full_renewal_date' THEN pm.meta_value END) AS sync_first_renewal
        FROM wp_posts p
        LEFT JOIN wp_postmeta pm ON pm.post_id = p.ID
        WHERE p.post_type='arraysubs_data' AND p.post_date >= '$D0 00:00:00'
        GROUP BY p.ID, p.post_status, p.post_date
        ORDER BY p.ID;" 2>&1

    echo
    echo "## Action Scheduler — arraysubs actions attempted in the last 36h"
    wp --path="$WP_ROOT" --allow-root db query "
        SELECT a.action_id, a.hook, a.status, a.scheduled_date_gmt, a.last_attempt_gmt, g.slug AS grp
        FROM wp_actionscheduler_actions a
        LEFT JOIN wp_actionscheduler_groups g ON a.group_id = g.group_id
        WHERE a.hook LIKE 'arraysubs%'
          AND a.last_attempt_gmt >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 36 HOUR)
        ORDER BY a.last_attempt_gmt DESC
        LIMIT 80;" 2>&1

    echo
    echo "## Action Scheduler — pending arraysubs actions in the next 48h"
    wp --path="$WP_ROOT" --allow-root db query "
        SELECT a.action_id, a.hook, a.status, a.scheduled_date_gmt, g.slug AS grp
        FROM wp_actionscheduler_actions a
        LEFT JOIN wp_actionscheduler_groups g ON a.group_id = g.group_id
        WHERE a.hook LIKE 'arraysubs%' AND a.status='pending'
          AND a.scheduled_date_gmt <= DATE_ADD(UTC_TIMESTAMP(), INTERVAL 48 HOUR)
        ORDER BY a.scheduled_date_gmt ASC
        LIMIT 80;" 2>&1

    echo
    echo "## Action Scheduler — FAILED arraysubs actions in the last 36h (always investigate)"
    wp --path="$WP_ROOT" --allow-root db query "
        SELECT a.action_id, a.hook, a.scheduled_date_gmt, a.last_attempt_gmt
        FROM wp_actionscheduler_actions a
        WHERE a.hook LIKE 'arraysubs%' AND a.status='failed'
          AND a.last_attempt_gmt >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 36 HOUR)
        ORDER BY a.last_attempt_gmt DESC;" 2>&1

    echo
    echo "## Orders created in the last 36h"
    wp --path="$WP_ROOT" --allow-root db query "
        SELECT id, status, type, total_amount, payment_method, date_created_gmt, parent_order_id
        FROM wp_wc_orders
        WHERE date_created_gmt >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 36 HOUR)
        ORDER BY id DESC
        LIMIT 60;" 2>&1

    echo
    echo "## Mailpit"
    echo "latest-id: $(/usr/local/bin/mailpit-agent latest-id 2>&1)"
    /usr/local/bin/mailpit-agent list 60 2>&1

    echo
    echo "## Board snapshot"
    ( cd "$PLAN_DIR/kanban/.." && kanban-md board 2>&1 )
} >"$FACTS_FILE" 2>&1

# --- build the prompt ---------------------------------------------------------
PROMPT="$(cat "$AUTOMATION_DIR/watch-prompt.md")"
PROMPT="${PROMPT//__DAY__/$DAY}"
PROMPT="${PROMPT//__DAY_LABEL__/$DAY_LABEL}"
PROMPT="${PROMPT//__DATE__/$STAMP}"
PROMPT="${PROMPT//__REPORT_FILE__/$REPORT_FILE}"
PROMPT="${PROMPT//__FACTS_FILE__/$FACTS_FILE}"
PROMPT="${PROMPT//__PLAN_DIR__/$PLAN_DIR}"

# --- run the agent ------------------------------------------------------------
cd "$PLUGIN_ROOT" || exit 1

timeout "$CODEX_TIMEOUT" codex exec \
    --model gpt-5.6-sol \
    --config 'model_reasoning_effort="ultra"' \
    --dangerously-bypass-approvals-and-sandbox \
    "$PROMPT" >"$CODEX_LOG" 2>&1

rc=$?

if [[ $rc -eq 124 ]]; then
    echo "$(date -Is) TIMEOUT $DAY_LABEL after ${CODEX_TIMEOUT}s — see $CODEX_LOG" >>"$RUN_LOG"
elif [[ $rc -ne 0 ]]; then
    echo "$(date -Is) FAIL $DAY_LABEL codex exited $rc — see $CODEX_LOG" >>"$RUN_LOG"
else
    echo "$(date -Is) OK $DAY_LABEL report=$REPORT_FILE" >>"$RUN_LOG"
fi

# The agent is instructed to write the report itself. If it did not, leave a
# stub so a missing day is visible on the board rather than silently absent.
if [[ ! -f "$REPORT_FILE" ]]; then
    {
        echo "# SLT watch $DAY_LABEL — $STAMP"
        echo
        echo "**The agent did not produce a report.** codex exit code: $rc"
        echo
        echo "- Facts snapshot: \`$FACTS_FILE\`"
        echo "- Agent log: \`$CODEX_LOG\`"
        echo
        echo "Investigate manually before the next watch day."
    } >"$REPORT_FILE"
fi

# Close any browser sessions the agent may have left open, so tomorrow's run
# starts clean and no headless Chrome is left resident.
agent-browser close --all >/dev/null 2>&1 || true

exit 0
