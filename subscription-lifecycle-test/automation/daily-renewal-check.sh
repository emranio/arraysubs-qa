#!/usr/bin/env bash
#
# SLT2 daily renewal + email watch.
#
# Runs in five time-gated phases per day from D0 (2026-08-23) through D12,
# then uses every phase from D13 onward to retry final teardown until task 120
# is actually done. Each run:
#   1. Refuses to overlap with a previous run (flock).
#   2. Works out which watch day it is; exits quietly before D0 and pins every
#      post-window teardown retry to D13.
#   3. Collects a deterministic facts snapshot (WP-CLI + Action Scheduler + Mailpit)
#      so the agent starts from real data instead of re-deriving it.
#   4. Invokes Codex non-interactively against that day's row of
#      watch-schedule.md, which tells it what should have happened overnight
#      and which browser test tasks are due that day.
#   5. Persists the report, the facts snapshot, and a one-line run summary.
#
# Installed by install-cron.sh as /etc/cron.d/slt2-daily-renewal-watch.
#
set -uo pipefail

D0="2026-08-23"          # plan day zero
LAST_WATCH_DAY=12        # D0..D11 execution plus D12 read-only tail
FINAL_TEARDOWN_DAY=13    # D13 = 2026-09-05, SLT-TEARDOWN-13
FINAL_TEARDOWN_DATE="2026-09-05"
CODEX_TIMEOUT=21600      # 6h: a phase may prepare, then poll into the next hard task gate

if (( FINAL_TEARDOWN_DAY != LAST_WATCH_DAY + 1 )); then
    echo "Invalid watch window: teardown must immediately follow the last read-only watch day." >&2
    exit 1
fi

PLAN_DIR="/home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public/wp-content/plugins/qa/subscription-lifecycle-test"
QA_ROOT="/home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public/wp-content/plugins/qa"
WP_ROOT="/home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public"
EVIDENCE_ROOT="/home/server-manager/slt-evidence"

AUTOMATION_DIR="$PLAN_DIR/automation"
LOG_DIR="$AUTOMATION_DIR/logs"
REPORT_DIR="$PLAN_DIR/watch-reports"
LOCK_FILE="/tmp/slt2-daily-renewal-watch.lock"
RUN_LOG="$LOG_DIR/run-summary.log"
TEARDOWN_TASK="$PLAN_DIR/kanban/tasks/120-slt-setup-99b-post-watch-teardown-on-2026-09-05.md"
CRON_FILE="/etc/cron.d/slt2-daily-renewal-watch"

export PATH="/root/.local/bin:/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin"

mkdir -p "$LOG_DIR" "$REPORT_DIR" "$EVIDENCE_ROOT"

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
ACTUAL_DAY=$(( (today_epoch - d0_epoch) / 86400 ))

if (( ACTUAL_DAY < 0 )); then
    echo "$(date -Is) SKIP day=$ACTUAL_DAY is before the watch window" >>"$RUN_LOG"
    exit 0
fi

IS_TEARDOWN=0
DAY=$ACTUAL_DAY
REPORT_STAMP="$today"
if (( ACTUAL_DAY >= FINAL_TEARDOWN_DAY )); then
    IS_TEARDOWN=1
    DAY=$FINAL_TEARDOWN_DAY
    REPORT_STAMP="$FINAL_TEARDOWN_DATE"
fi

if (( IS_TEARDOWN == 1 )) && grep -Eq '^status:[[:space:]]*done[[:space:]]*$' "$TEARDOWN_TASK"; then
    echo "$(date -Is) DONE task 120 is already done; removing cron entry" >>"$RUN_LOG"
    rm -f "$CRON_FILE"
    exit 0
fi

DAY_LABEL="$(printf 'D%02d' "$DAY")"
STAMP="$(date +%F)"
site_hour="$(wp --path="$WP_ROOT" --allow-root eval 'echo (int) current_time("G");' 2>/dev/null || true)"
if [[ ! "$site_hour" =~ ^[0-9]+$ ]] || (( site_hour < 0 || site_hour > 23 )); then
    site_hour=$(( (10#$(date -u +%H) + 6) % 24 ))
fi
site_timezone="$(wp --path="$WP_ROOT" --allow-root option get timezone_string 2>/dev/null || true)"
if [[ -z "$site_timezone" ]]; then
    site_timezone="UTC$(wp --path="$WP_ROOT" --allow-root option get gmt_offset 2>/dev/null || true)"
fi
SITE_TIME="$(printf '%02d:%s %s' "$site_hour" "$(date -u +%M)" "$site_timezone")"

if (( site_hour < 8 )); then
    TIME_PHASE="early-morning"
elif (( site_hour < 12 )); then
    TIME_PHASE="late-morning"
elif (( site_hour < 18 )); then
    TIME_PHASE="afternoon"
elif (( site_hour < 21 )); then
    TIME_PHASE="evening"
elif (( site_hour < 23 )); then
    TIME_PHASE="night"
else
    TIME_PHASE="late"
fi

if (( IS_TEARDOWN == 1 )); then
    PHASE="teardown-$TIME_PHASE"
else
    PHASE="$TIME_PHASE"
fi

REPORT_FILE="$REPORT_DIR/${DAY_LABEL}-${REPORT_STAMP}.md"
FACTS_FILE="$LOG_DIR/${DAY_LABEL}-${STAMP}-${PHASE}-facts.txt"
CODEX_LOG="$LOG_DIR/${DAY_LABEL}-${STAMP}-${PHASE}-codex.log"

echo "$(date -Is) START $DAY_LABEL ($STAMP) phase=$PHASE site=$SITE_TIME" >>"$RUN_LOG"

# --- deterministic facts snapshot ---------------------------------------------
# Everything here is read-only. The agent gets this verbatim so it never has to
# guess at state it could simply have been handed.
{
    echo "# SLT2 watch facts — $DAY_LABEL ($STAMP)"
    echo "Generated: $(date -Is)  |  UTC: $(date -u +'%F %T')  |  site: $SITE_TIME"
    echo

    echo "## Subscriptions by status"
    wp --path="$WP_ROOT" --allow-root db query \
        "SELECT post_status, COUNT(*) AS n FROM wp_posts WHERE post_type='arraysubs_data' GROUP BY post_status;" 2>&1

    echo
    echo "## SLT2 subscriptions (SLT2-titled product, created on/after $D0) with schedule meta"
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
        WHERE p.post_type='arraysubs_data'
          AND p.post_date >= '$D0 00:00:00'
          AND EXISTS (
              SELECT 1
              FROM wp_postmeta product_ref
              JOIN wp_posts product ON product.ID = CAST(product_ref.meta_value AS UNSIGNED)
              WHERE product_ref.post_id = p.ID
                AND product_ref.meta_key = '_product_id'
                AND product.post_title LIKE 'SLT2 %'
          )
        GROUP BY p.ID, p.post_status, p.post_date
        ORDER BY p.ID;" 2>&1

    echo
    echo "## Active plugins"
    wp --path="$WP_ROOT" --allow-root plugin list --status=active --fields=name,status,version 2>&1

    echo
    echo "## In-scope gateway host registry (host class is distinct from the ArraySubs delegate owner)"
    wp --path="$WP_ROOT" --allow-root eval '
        $gateways = WC()->payment_gateways()->payment_gateways();
        foreach ( $gateways as $id => $gateway ) {
            if ( in_array( $id, array( "stripe", "arraysubs_paddle", "bacs" ), true ) ) {
                $reflection = new ReflectionClass( $gateway );
                echo $id . "\t" . $gateway->enabled . "\t" . get_class( $gateway ) . "\t" . $reflection->getFileName() . PHP_EOL;
            }
        }
    ' 2>&1

    echo
    echo "## Fresh fixture registry"
    if [[ -f "$PLAN_DIR/evidence/fixture-registry.tsv" ]]; then
        sed -n '1,240p' "$PLAN_DIR/evidence/fixture-registry.tsv"
    else
        echo "MISSING: $PLAN_DIR/evidence/fixture-registry.tsv"
    fi

    echo
    echo "## Future-gate registry"
    if [[ -f "$PLAN_DIR/evidence/future-gates.tsv" ]]; then
        sed -n '1,320p' "$PLAN_DIR/evidence/future-gates.tsv"
    else
        echo "MISSING: $PLAN_DIR/evidence/future-gates.tsv"
    fi

    echo
    echo "## Action Scheduler — arraysubs actions attempted in the last 36h"
    wp --path="$WP_ROOT" --allow-root db query "
        SELECT a.action_id, a.hook, a.status, a.scheduled_date_gmt, a.last_attempt_gmt, g.slug AS grp, a.args
        FROM wp_actionscheduler_actions a
        LEFT JOIN wp_actionscheduler_groups g ON a.group_id = g.group_id
        WHERE a.hook LIKE 'arraysubs%'
          AND a.last_attempt_gmt >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 36 HOUR)
        ORDER BY a.last_attempt_gmt DESC
        LIMIT 80;" 2>&1

    echo
    echo "## Action Scheduler — pending arraysubs actions in the next 48h"
    wp --path="$WP_ROOT" --allow-root db query "
        SELECT a.action_id, a.hook, a.status, a.scheduled_date_gmt, g.slug AS grp, a.args
        FROM wp_actionscheduler_actions a
        LEFT JOIN wp_actionscheduler_groups g ON a.group_id = g.group_id
        WHERE a.hook LIKE 'arraysubs%' AND a.status='pending'
          AND a.scheduled_date_gmt <= DATE_ADD(UTC_TIMESTAMP(), INTERVAL 48 HOUR)
        ORDER BY a.scheduled_date_gmt ASC
        LIMIT 80;" 2>&1

    echo
    echo "## Action Scheduler — FAILED arraysubs actions in the last 36h (always investigate)"
    wp --path="$WP_ROOT" --allow-root db query "
        SELECT a.action_id, a.hook, a.scheduled_date_gmt, a.last_attempt_gmt, a.args
        FROM wp_actionscheduler_actions a
        WHERE a.hook LIKE 'arraysubs%' AND a.status='failed'
          AND a.last_attempt_gmt >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 36 HOUR)
        ORDER BY a.last_attempt_gmt DESC;" 2>&1

    echo
    echo "## Orders created in the last 36h"
    wp --path="$WP_ROOT" --allow-root db query "
        SELECT o.id, o.status, o.type, o.customer_id, o.total_amount, o.payment_method,
               o.date_created_gmt, o.parent_order_id,
               GROUP_CONCAT(DISTINCT CASE
                   WHEN om.meta_key IN ('_subscription_id','_subscription_ids','_subscription_renewal')
                   THEN CONCAT(om.meta_key, '=', om.meta_value)
               END ORDER BY om.meta_key SEPARATOR ' | ') AS subscription_refs
        FROM wp_wc_orders o
        LEFT JOIN wp_wc_orders_meta om ON om.order_id = o.id
        WHERE o.date_created_gmt >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 36 HOUR)
        GROUP BY o.id, o.status, o.type, o.customer_id, o.total_amount, o.payment_method,
                 o.date_created_gmt, o.parent_order_id
        ORDER BY o.id DESC
        LIMIT 60;" 2>&1

    echo
    echo "## Mailpit"
    echo "latest-id: $(/usr/local/bin/mailpit-agent latest-id 2>&1)"
    /usr/local/bin/mailpit-agent list 60 2>&1

    echo
    echo "## Board snapshot"
    ( cd "$PLAN_DIR/kanban" && kanban-md board 2>&1 )
} >"$FACTS_FILE" 2>&1

# --- build the prompt ---------------------------------------------------------
if (( IS_TEARDOWN == 1 )); then
    PROMPT="$(cat "$AUTOMATION_DIR/teardown-prompt.md")"
else
    PROMPT="$(cat "$AUTOMATION_DIR/watch-prompt.md")"
fi
PROMPT="${PROMPT//__DAY__/$DAY}"
PROMPT="${PROMPT//__DAY_LABEL__/$DAY_LABEL}"
PROMPT="${PROMPT//__DATE__/$STAMP}"
PROMPT="${PROMPT//__PHASE__/$PHASE}"
PROMPT="${PROMPT//__SITE_TIME__/$SITE_TIME}"
PROMPT="${PROMPT//__REPORT_FILE__/$REPORT_FILE}"
PROMPT="${PROMPT//__FACTS_FILE__/$FACTS_FILE}"
PROMPT="${PROMPT//__PLAN_DIR__/$PLAN_DIR}"
PROMPT="${PROMPT//__QA_ROOT__/$QA_ROOT}"

# --- run the agent ------------------------------------------------------------
cd "$PLAN_DIR" || exit 1

timeout "$CODEX_TIMEOUT" codex exec \
    --model gpt-5.6-sol \
    --config 'model_reasoning_effort="ultra"' \
    --config 'approval_policy="never"' \
    --config 'sandbox_workspace_write.network_access=true' \
    --sandbox workspace-write \
    --cd "$PLAN_DIR" \
    --add-dir "$QA_ROOT" \
    --add-dir "$EVIDENCE_ROOT" \
    --add-dir /root/.agent-browser \
    "$PROMPT" >"$CODEX_LOG" 2>&1

rc=$?

if [[ $rc -eq 124 ]]; then
    echo "$(date -Is) TIMEOUT $DAY_LABEL phase=$PHASE after ${CODEX_TIMEOUT}s — verify that no settings bracket remains open; see $CODEX_LOG" >>"$RUN_LOG"
elif [[ $rc -ne 0 ]]; then
    echo "$(date -Is) FAIL $DAY_LABEL phase=$PHASE codex exited $rc — see $CODEX_LOG" >>"$RUN_LOG"
else
    echo "$(date -Is) OK $DAY_LABEL phase=$PHASE report=$REPORT_FILE" >>"$RUN_LOG"
fi

# The agent is instructed to write the report itself. If it did not, leave a
# stub so a missing day is visible on the board rather than silently absent.
if [[ ! -f "$REPORT_FILE" ]]; then
    {
        echo "# SLT2 watch $DAY_LABEL — $STAMP"
        echo
        echo "**The agent did not produce a report.** codex exit code: $rc"
        echo
        echo "- Facts snapshot: \`$FACTS_FILE\`"
        echo "- Agent log: \`$CODEX_LOG\`"
        echo
        echo "Investigate manually before the next watch day."
    } >"$REPORT_FILE"
fi

# Close only QA sessions the agent may have left open. Other projects share the
# agent-browser daemon, so a global close would disrupt unrelated work.
while IFS= read -r qa_session; do
    case "$qa_session" in
        *SLT-*|*slt2-*)
            agent-browser --session "$qa_session" close >/dev/null 2>&1 || true
            ;;
    esac
done < <(agent-browser session list --json 2>/dev/null | jq -r '.data.sessions[]?' 2>/dev/null)

if (( IS_TEARDOWN == 1 )); then
    if grep -Eq '^status:[[:space:]]*done[[:space:]]*$' "$TEARDOWN_TASK"; then
        echo "$(date -Is) DONE task 120 reached done; removing cron entry" >>"$RUN_LOG"
        rm -f "$CRON_FILE"
    else
        echo "$(date -Is) RETAIN task 120 is not done; teardown will retry at the next cron phase" >>"$RUN_LOG"
    fi
fi

exit 0
