# Subscription Lifecycle, Renewal & Email QA

A 10-day, browser-first regression run against **ArraySubs 1.8.11** (free) and **ArraySubsPro 1.1.2** (premium)
on the shared staging site, proving that auto-renewals fire unattended and that every email the plugins
claim to send actually arrives with the right content.

- **Execution window:** D0 `2026-08-02` → D10 `2026-08-12`
- **Automated watch window:** D1 `2026-08-03` → D12 `2026-08-14` (2 extra days to catch retry tails and grace-period cancellations)
- **Board:** `kanban/` in this directory — run `kanban-md` commands only after `cd`-ing here
- **Issues:** `issues/*.md`, one file per finding
- **Daily reports:** `watch-reports/D01-2026-08-03.md` … `D12-2026-08-14.md`, plus final teardown report `D13-2026-08-15.md`
- **Code-verified mechanics:** `reference/SLT-REF-*.md`
- **Calendar:** `calendar.md` (what a human runs each day) and `watch-schedule.md` (what the robot checks each morning)

---

## Why this plan exists

The staging site already carries 354 subscriptions and 437 orders from earlier QA stages. This plan does not
re-test those. It builds a **self-contained, `SLT`-prefixed** catalog whose billing intervals are short enough
that a complete subscription life cycle — signup → renewal → renewal → failure → retry → grace → cancellation —
happens for real inside ten days, without touching the clock and without disturbing anyone else's data.

---

## Verified environment baseline

Everything below was confirmed on this machine on 2026-08-01. Do not re-derive it; do contradict it loudly
if you find it false.

| Fact | Value |
|---|---|
| Site | `https://mirror-help.arrayhash.com` |
| Admin | `https://mirror-help.arrayhash.com/wp-admin` — use the current local credential source in the workspace `AGENTS.md` |
| WP root | `/home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public` |
| Plugin workspace | `<WP root>/wp-content/plugins` |
| Site timezone | `gmt_offset = +6` (UTC+6), `timezone_string` empty |
| Theme | `twentytwentyfive` 1.5 |
| Orders storage | **HPOS** — authoritative order data lives in `wp_wc_orders`; this environment also keeps draft `shop_order_placehold` placeholder rows in `wp_posts`, so do not treat `wp_posts` presence alone as a failure |
| Subscription CPT | `arraysubs_data`; statuses `arraysubs-active`, `-pending`, `-on-hold`, `-cancelled`, `-expired` |
| Checkout page | ID **8**, uses the **Block** checkout (`wp:woocommerce/checkout`) |
| Cart page | ID 7 |
| WP-CLI | must be run from WP root with `--allow-root` |
| Currency | `USD` |
| Taxes | **OFF** (`woocommerce_calc_taxes = no`) — never assert a tax line |
| Guest checkout | `woocommerce_enable_guest_checkout = yes`, but ArraySubs **force-requires registration for subscription carts** via `woocommerce_checkout_registration_required` (`SubscriptionCheckout/Services/Hooks.php:103`, `CheckoutHelpersTrait.php:93-100`). Anonymous checkout stays possible for non-subscription carts. |
| Grouped products | **Zero handling in either plugin** (verified by grep). Grouped-product tasks are exploratory — document behaviour, do not assert a spec. |

### WP-Cron really is running

`wp-config.php` sets `DISABLE_WP_CRON = true`, but `/etc/cron.d/mirror-help-arrayhash-wordpress` runs
`wp-cron.php` **every minute** as `www-data` behind a `flock`. Confirmed live: Action Scheduler actions
complete within ~60 s of their scheduled time, and there is no backlog.

**This is the load-bearing fact of the whole plan.** Renewals fire on their own, so a renewal that does *not*
fire is a genuine bug. Capture the evidence, file the standalone issue, and carry the dependency forward;
never force a natural-watch action merely to make the plan continue.

### Nothing fires exactly at `_next_payment_date` — the renewal spread offset

This is the fact most likely to make a tester file a false bug, so learn it before Day 0.

Every scheduled renewal leg is shifted by a deterministic, permanent, per-subscription offset:

```
offset = crc32('arraysubs-spread-' . $subscription_id) % 21600      # 0 .. 6 hours
```

- `renewals.spread_window_hours` is unset here, so the default **6 h** applies, capped at 25 % of the billing
  cycle — for any cycle of a day or longer that evaluates to the full 21600 s.
- The **invoice** leg fires at `due + offset − 6h`. The **charge** leg fires at `due + offset`.
- `_next_payment_date` itself is **never** shifted. Only the Action Scheduler timestamps move.
- The offset is stable forever for a given subscription ID, and you can compute it outside WordPress:

```bash
php -r 'foreach([100,1234] as $id){$h=(int)sprintf("%u",crc32("arraysubs-spread-".$id));
printf("id=%d offset=%ds (%s)\n",$id,$h%21600,gmdate("H:i:s",$h%21600));}'
```

**Consequence for every assertion in this plan:** assert a *window*, not a point in time. Compute the
subscription's offset first, then expect the charge inside `[due+offset, due+offset+few minutes]`. A renewal
that has not fired at `due` exactly is not late — it is normal.

Full derivation with `file:line` citations is in `reference/SLT-REF-01-*.md`.

### Gateways in scope

Only **Stripe** and **Paddle**. PayPal (`arraysubs_paypal`) is disabled and out of scope; BACS/cheque/COD are
used only as manual controls where a test explicitly calls for a non-gateway path.

| Gateway | Mode | Notes |
|---|---|---|
| Stripe (`stripe`) | `testmode: yes` | Test keys + test webhook secret present. UPE accordion layout, `saved_cards: yes`, express checkout on. |
| Paddle (`arraysubs_paddle`) | `test_mode: yes` | Sandbox api_key, client_token, seller_id `80267`, webhook secret all set. |

Stripe test cards:

| Card | Behaviour |
|---|---|
| `4242 4242 4242 4242` | succeeds |
| `4000 0027 6000 3184` | 3-D Secure / SCA challenge |
| `4000 0000 0000 0341` | attaches fine, **declines on every off-session renewal** — the failed-renewal workhorse |
| `4000 0000 0000 9995` | insufficient funds decline |

### Email capture

All site mail is sunk by Mailpit; nothing reaches a real inbox.

```bash
mailpit-agent status
mailpit-agent list 50
mailpit-agent latest-id
mailpit-agent show <id|latest>
mailpit-agent text <id|latest>
mailpit-agent html <id|latest>
mailpit-agent wait-new <previous-id|none> <timeout-sec> "<subject substring>"   # exit 124 on timeout
```

**Always** snapshot `latest-id` *before* the action that should send mail, then `wait-new` against it.
Asserting on "the newest message" without a baseline produces false passes.

This is a shared Mailpit. A task's positive and negative assertions are scoped to the complete delta after
its own baseline and correlated by exact subscription/order/user ID, recipient, and subject. A fixed
`list 20/50` or `latest-id unchanged` check is only a shortcut when no unrelated message arrived. If the
global latest ID moves, classify the full delta and pass the task when no message is attributable to its
action; preserve unrelated/background mail as external evidence. This rule is authoritative over older
task shorthand. Only an unexpected message attributable to an SLT artifact/action is a product finding.

Human UI: `https://mailpit.arrayhash.com` (HTTP basic auth — credentials stay out of this repo).

### Settings that drive the timing in this plan

Read from `wp option get arraysubs_settings --allow-root`. Tests assert against these exact numbers.

| Setting | Pre-window value | Active QA-window value / consequence |
|---|---|---|
| `renewals.invoice_before_due` | 6 hours | unchanged; renewal invoice/order appears 6 h before the due time |
| `renewals.grace_days_before_on_hold` | 1 | unchanged; failed renewal → on-hold after 1 day |
| `renewals.grace_days_before_cancel` | 3 | unchanged; still-failing → cancelled after 3 days |
| `renewals.sync_to_billing_cycle` | **`true`** | **`false`**; plain subscriptions use anniversary scheduling and Paddle remains testable |
| `renewals.sync_first_charge_mode` | `full` | unchanged; do not touch while global sync is off |
| `emails.renewal_upcoming.days_before` | 3 | unchanged; renewal alert 3 days ahead |
| `emails.trial_ending.days_before` | 3 | unchanged; trial-ending alert setting 3 days ahead |
| `emails.expiring_soon.days_before` | 7 | unchanged; expiry alert 7 days ahead |
| `plan_switching.proration_type` | `prorate_immediately` | unchanged; switches charge/credit at once |
| `plan_switching.auto_downgrade_timing` | `on_expire` | unchanged; downgrades land at period end |
| `proration.switch_fees` | all `0` | unchanged except an explicitly bracketed test |
| `multiple_subscriptions.allow_multiple_in_cart` | `false` | unchanged; two subscriptions in one cart must be rejected |
| `multiple_subscriptions.one_per_customer` | `false` | unchanged; `auto_migrate_on_checkout` is inert at this baseline, so a repeat checkout creates a duplicate subscription instead of migrating |
| `checkout.auto_create_account` | `true` | unchanged; subscription checkout creates an account |
| `trials.require_payment_method` | `true` | unchanged; trials still collect a card |
| `customer_actions.allow_early_renew` | **`false`** | **`true`** for the QA window |
| `customer_actions.allow_reactivation` | **`false`** | **`true`** for the QA window |
| `pause_subscription.enabled` | **`false`** | **`true`** for the QA window |
| `pause_subscription.customer_can_pause` | **`false`** | **`true`** for the QA window |

These four controls (five boolean paths) were changed once on D0 by `SLT-SETUP-02`, are frozen for the
whole run, and are restored by `SLT-SETUP-99A` on D10. `SLT-SYN-04` is the sole short-lived exception for
global sync. Every other task must use the registry's `WINDOW BASELINE (frozen)` table and must not toggle
these values ad hoc.

---

## Isolation contract

The site is shared. These rules are not negotiable.

1. Products created by this plan are titled `SLT …` with slug `slt-…`.
2. Users are `slt-<purpose>` with email `slt-<purpose>@example.test`.
3. Coupons are `SLT…`.
4. **Never** edit, cancel, refund, or delete anything that is not `SLT`-prefixed.
5. **Never** change the system clock. Editing `_next_payment_date`, `_end_date`, or `_renewal_scheduled_date`
   to move an event in time is permitted only on D8 and only when the exact task authorizes it, with the
   required non-SLT before/after schedule proof.
6. **Never** run a bare, hook-wide, or group-wide Action Scheduler drain. An authorized task may run one known
   action ID at a time after the required pending-queue pre-flight. Natural-watch actions are never
   forced merely to make a task pass.
7. Any global setting a task changes outside the declared baseline must be recorded in that task's Notes
   and restored in the same task's teardown step.
8. Classic checkout is tested on a **dedicated page** carrying `[woocommerce_checkout]`, created by
   `SLT-SETUP-01`. Page 8 stays on the Block checkout for the entire window, so classic and block tests
   never contend for the same page.
9. **`wp action-scheduler list` does not exist on this site.** The installed CLI exposes only `run`,
   `status`, `source`, `clean` and friends. A few authored task bodies reference `action-scheduler list`
   anyway — those instructions are wrong. Inspect the queue through **Tools → Scheduled Actions** in
   wp-admin, or query `wp_actionscheduler_actions` directly (see `watch-schedule.md` for the SQL). The
   daily facts snapshot already runs the pending / recently-attempted / failed queries for you.
10. The pre-existing Shop Access rule `rule_1784662676378_maa3te08s` restricts the full store. Its exact
    pre-window `enabled` + `ecommerce_rules` state is frozen at
    `/home/server-manager/slt-evidence/SLT-PROD-01-members-access-rules.json`. Immediately after publishing
    any `SLT ` product, append its **parent product ID** to that rule's `exclusion_product_ids` before any
    storefront or checkout step. Never disable or replace the rule, never exclude a non-SLT product, and
    verify the saved ID with a fresh query-string product request because Cloudflare may retain canonical
    product HTML. `SLT-SETUP-99A` must restore the captured rule exactly and prove a sorted JSON diff is empty.
11. Product source is completely out of scope for this run: do not open, grep, inspect, edit, revert, or
    otherwise touch files under `arraysubs/` or `arraysubspro/`. Use the suite-local `reference/` notes and
    live UI/runtime evidence. Correct plan defects only inside this QA suite and record product findings as
    standalone markdown files under `issues/`.
12. Every browser task uses a task-keyed session, and every logged-in checkout proves the persistent cart
    empty before and after. Never run two tasks concurrently under the same `slt-*` account.
13. The automated runner closes leftover `SLT-` browser sessions at the end of every phase. Multi-day tasks
    must reopen their same task-keyed session name and re-authenticate at each dated follow-up; they must not
    rely on cookies or an open browser surviving from an earlier phase.
14. Admin-created WordPress users follow the D0-observed mail contract: unticking **Send User Notification**
    suppresses the customer account/password message but still emits exactly one admin-only **New User
    Registration** message per new user. Capture/classify those messages even when a task takes its checkout
    Mailpit baseline only after user creation; they are expected setup mail, not product findings.
15. Never identify a new subscription by recency, a highest ID, or `tail`. Resolve it from the recorded parent
    order's `_subscription_ids`, require the expected cardinality, then cross-check `_parent_order_id`,
    `_customer_id`, and `_product_id` plus any before/after count the task records. Concurrent QA can create
    unrelated subscriptions between a checkout and its verification.
16. Mail assertions are baseline-delta scoped. Correlate by exact SLT subscription/order/user ID and
    recipient. Never fail a task merely because unrelated/background mail moved the global latest ID, and
    never use a fixed recent-message count as proof when the task's complete delta is longer.
17. Symbolic registry aliases in task prose (`SUB_CORE`, `S1`, `S4`, `S_FAIL`, and similar) are placeholders,
    never literal command arguments or email-subject text. Resolve each alias to its recorded numeric ID,
    assign it to a shell variable, require it to match `^[0-9]+$`, and interpolate that value into WP-CLI,
    SQL, PHP crc32, Mailpit, URL, and Action Scheduler queries. Abort the step rather than hashing or
    searching for the alias string itself.
18. Payment fixtures may be entered only in the hosted test fields. Never echo, save, paste into reports, or
    screenshot an unmasked full card number or raw Stripe/Paddle credential; evidence records only the named
    fixture, brand, last four digits, and redacted API fields.

---

## Running the plan

```bash
cd /home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public/wp-content/plugins/qa/subscription-lifecycle-test/kanban

kanban-md board                          # status overview
kanban-md list --status todo --table     # what is queued
kanban-md show <id>                      # full task body
kanban-md move <id> in-progress
kanban-md move <id> done                 # or: review / blocked
```

Work the day's tasks from `calendar.md`. Every task body is self-contained: objective, scope, preconditions,
test data, numbered browser steps, expected results, an emails table, evidence to capture, pass criteria,
and its isolation/teardown obligations.

### Verdict policy

- **PASS** requires an observation that proves it. "It usually works" is not an observation.
- Board status records whether the QA work is complete, not whether the product passed. A fully executed,
  evidenced **FAIL** is a completed test: write its standalone `issues/` file, add the verdict/evidence path
  to the task execution note, self-review it, and move the task through `review` to `done`.
- Console errors, PHP notices, and 4xx/5xx responses are failures even when the UI looks correct.
- A missing expected SLT email is a failure. An **unexpected task-attributable** email is also a failure —
  the negative checks matter. Unrelated/background mail is classified and preserved, not charged to the task.
- Cannot verify? The verdict is `UNVERIFIED` with the reason. Never round up to PASS.
- Use `blocked` only while a concrete prerequisite prevents the remaining QA action. A future authored clock
  gate stays `in-progress`, not blocked. Once the final permitted observation/retry window has passed, record
  the unresolved portion as `UNVERIFIED`, preserve the blocker evidence, self-review, and close the execution
  task as `done`; do not strand completed QA work in `review` or `blocked` merely because product code is out
  of scope for this run.

### Filing an issue

Write `issues/<TASK-KEY>-<slug>.md` containing: title, severity, date, watch day, originating task key and
plan path, affected subscription/order/product IDs, affected user IDs and roles, gateway and checkout type,
non-default settings in play, exact URL/route, numbered repro steps, expected vs actual stated separately,
concrete proof (UI text, screenshot paths, Mailpit IDs and subjects, WP-CLI output, meta values, Action
Scheduler rows, console errors), and scope notes including any counterexample where the same flow works.

Do **not** create a kanban card for the finding. Product bugs and unexpected behaviour live only as standalone files in `issues/`; the lifecycle board tracks execution-plan tasks, not remediation work.

---

## The automated 12-day watch

`automation/daily-renewal-check.sh` has five serialized phase starts each day from
`/etc/cron.d/slt-daily-renewal-watch`: **02:10, 06:10, 12:10, 15:10, and 17:42 Europe/Berlin**
(06:10, 10:10, 16:10, 19:10, and 21:42 site-local during this August CEST run). The phases exist because the
calendar contains strict morning, after-noon, 18:00, 21:00, and 23:40 gates. Each run:

1. Takes a `flock` so runs cannot overlap.
2. Computes the watch day and phase; exits quietly before D1. D1-D12 perform the watch and calendar work. Every phase from D13 onward retries `SLT-SETUP-99B`; the cron entry is deleted only after task 119 reaches `done`.
3. Collects a read-only facts snapshot — subscription statuses and schedule meta, Action Scheduler activity
   for the last 36 h, pending actions for the next 48 h, failed actions, orders from the last 36 h, recent
   Mailpit messages, and the board — into `automation/logs/`.
4. Runs Codex non-interactively with `gpt-5.6-sol`, `model_reasoning_effort="ultra"`, a six-hour safety
   timeout, and a workspace-write sandbox rooted at this QA suite. Only this suite,
   `/home/server-manager/slt-evidence`, and agent-browser's own state directory are writable; the product
   source trees are outside the writable roots.
5. The morning agent verifies overnight activity; every phase executes only tasks whose authored time gate has opened, skips already-completed work, updates the same daily report, and leaves Review empty. When an **interactive** hard gate falls before the next phase start, the current invocation prepares without changing global state and polls in intervals of at most 60 seconds until the gate. A natural unattended event across the 21:42→06:10 gap instead gets a recorded pre-gate baseline and is verified from persisted evidence by the next phase, so the six-hour timeout is never used as an overnight wait. The `flock` serializes phases if one invocation spans a later cron start. Findings are standalone issue files only.
6. If the agent produces no report, the script writes a stub so a missing day is visible rather than silent.
7. Leftover sessions whose names contain an SLT task key are closed so the next phase starts clean; unrelated agent-browser sessions are preserved.

```bash
sudo automation/install-cron.sh      # install
sudo automation/uninstall-cron.sh    # stop early (it self-removes only after task 119 is done)
automation/daily-renewal-check.sh    # safe dry run — exits quietly before D1 / after completed teardown
tail -f automation/logs/run-summary.log
```

---

## Layout

```
subscription-lifecycle-test/
├── README.md            this file
├── calendar.md          D0..D10 execution calendar — what a human runs each day
├── watch-schedule.md    D1..D12 — what the automated watch verifies each morning
├── catalog.md           short prerequisite task index; task bodies remain authoritative
├── reference/           SLT-REF-01..10, code-verified mechanics with file:line citations
├── issues/              one markdown file per finding
├── watch-reports/       one report per watch day
├── automation/          the daily watch script, prompt, and cron installers
└── kanban/              the kanban-md board
```

Screenshots and command transcripts live outside the repository at `/home/server-manager/slt-evidence/`,
grouped by task key or watch day.
