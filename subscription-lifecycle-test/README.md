# Subscription Lifecycle, Renewal & Email QA

A 10-day, browser-first regression run against **ArraySubs 1.8.11** (free) and **ArraySubsPro 1.1.2** (premium)
on the shared staging site, proving that auto-renewals fire unattended and that every email the plugins
claim to send actually arrives with the right content.

- **Execution window:** D0 `2026-08-01` → D10 `2026-08-11`
- **Automated watch window:** D1 `2026-08-02` → D12 `2026-08-13` (2 extra days to catch retry tails and grace-period cancellations)
- **Board:** `kanban/` in this directory — run `kanban-md` commands only after `cd`-ing here
- **Issues:** `issues/*.md`, one file per finding
- **Daily reports:** `watch-reports/D01-2026-08-02.md` …
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
| Admin | `https://mirror-help.arrayhash.com/wp-admin` — `admin` / `@GuDw(0$K7M9t8ehjqDb4Vwj` |
| WP root | `/home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public` |
| Plugin workspace | `<WP root>/wp-content/plugins` |
| Site timezone | `gmt_offset = +6` (UTC+6), `timezone_string` empty |
| Theme | `twentytwentyfive` 1.5 |
| Orders storage | **HPOS** — orders live in `wp_wc_orders`, not `wp_posts` |
| Subscription CPT | `arraysubs_data`; statuses `arraysubs-active`, `-pending`, `-on-hold`, `-cancelled`, `-expired` |
| Checkout page | ID **8**, uses the **Block** checkout (`wp:woocommerce/checkout`) |
| Cart page | ID 7 |
| WP-CLI | must be run from WP root with `--allow-root` |

### WP-Cron really is running

`wp-config.php` sets `DISABLE_WP_CRON = true`, but `/etc/cron.d/mirror-help-arrayhash-wordpress` runs
`wp-cron.php` **every minute** as `www-data` behind a `flock`. Confirmed live: Action Scheduler actions
complete within ~60 s of their scheduled time, and there is no backlog.

**This is the load-bearing fact of the whole plan.** Renewals fire on their own, so a renewal that does *not*
fire is a genuine bug — never mask it by force-running the hook before you have captured the evidence.

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

Human UI: `https://mailpit.arrayhash.com` (HTTP basic auth — credentials stay out of this repo).

### Settings that drive the timing in this plan

Read from `wp option get arraysubs_settings`. Tests assert against these exact numbers.

| Setting | Value | Consequence |
|---|---|---|
| `renewals.invoice_before_due` | 6 hours | renewal invoice/order appears 6 h before the due time |
| `renewals.grace_days_before_on_hold` | 1 | failed renewal → on-hold after 1 day |
| `renewals.grace_days_before_cancel` | 3 | still-failing → cancelled after 3 days |
| `renewals.sync_to_billing_cycle` | `true` | global billing-cycle sync is ON |
| `renewals.sync_first_charge_mode` | `full` | global sync default is full-amount-now |
| `emails.renewal_upcoming.days_before` | 3 | renewal alert 3 days ahead |
| `emails.trial_ending.days_before` | 3 | trial-ending alert 3 days ahead |
| `emails.expiring_soon.days_before` | 7 | expiry alert 7 days ahead |
| `plan_switching.proration_type` | `prorate_immediately` | switches charge/credit at once |
| `plan_switching.auto_downgrade_timing` | `on_expire` | downgrades land at period end |
| `proration.switch_fees` | all `0` | no switch fee unless a test sets one |
| `multiple_subscriptions.allow_multiple_in_cart` | `false` | two subscriptions in one cart must be rejected |
| `checkout.auto_create_account` | `true` | guest checkout creates an account |
| `trials.require_payment_method` | `true` | trials still collect a card |
| `customer_actions.allow_early_renew` | **`false`** | must be flipped ON for early-renew tests |
| `customer_actions.allow_reactivation` | **`false`** | must be flipped ON for reactivation tests |
| `pause_subscription.enabled` | **`false`** | must be flipped ON for pause tests |

The three **bold** settings are changed once on D0 as declared *window-wide baseline changes*
(`SLT-SETUP-02`), held for the whole run, and restored by `SLT-SETUP-99` on D10. Every other task treats
them as fixed and must not toggle them ad hoc.

---

## Isolation contract

The site is shared. These rules are not negotiable.

1. Products created by this plan are titled `SLT …` with slug `slt-…`.
2. Users are `slt-<purpose>` with email `slt-<purpose>@example.test`.
3. Coupons are `SLT…`.
4. **Never** edit, cancel, refund, or delete anything that is not `SLT`-prefixed.
5. **Never** change the system clock. Time-travel by editing subscription date meta, then draining the
   *specific* hook for the *specific* subscription.
6. **Never** run `wp action-scheduler run --force` without a `--hooks=` filter. A broad drain fires other
   tests' pending renewals early and silently destroys their evidence. This is the single largest
   cross-contamination risk in the plan.
7. Any global setting a task changes outside the declared baseline must be recorded in that task's Notes
   and restored in the same task's teardown step.
8. Classic checkout is tested on a **dedicated page** carrying `[woocommerce_checkout]`, created by
   `SLT-SETUP-01`. Page 8 stays on the Block checkout for the entire window, so classic and block tests
   never contend for the same page.

---

## Running the plan

```bash
cd /home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public/wp-content/plugins/qa/subscription-lifecycle-test

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
- Console errors, PHP notices, and 4xx/5xx responses are failures even when the UI looks correct.
- A missing email is a failure. An **unexpected** email is also a failure — the negative checks matter.
- Cannot verify? The verdict is `UNVERIFIED` with the reason. Never round up to PASS.

### Filing an issue

Write `issues/<TASK-KEY>-<slug>.md` containing: title, severity, date, watch day, originating task key and
plan path, affected subscription/order/product IDs, affected user IDs and roles, gateway and checkout type,
non-default settings in play, exact URL/route, numbered repro steps, expected vs actual stated separately,
concrete proof (UI text, screenshot paths, Mailpit IDs and subjects, WP-CLI output, meta values, Action
Scheduler rows, console errors), and scope notes including any counterexample where the same flow works.

Then create a board task for it tagged `bug` so it is visible.

---

## The automated 12-day watch

`automation/daily-renewal-check.sh` runs once a day at **02:10 UTC** (08:10 site-local) from
`/etc/cron.d/slt-daily-renewal-watch`. Each run:

1. Takes a `flock` so runs cannot overlap.
2. Computes the watch day; exits quietly before D1 and **deletes its own cron entry** after D12.
3. Collects a read-only facts snapshot — subscription statuses and schedule meta, Action Scheduler activity
   for the last 36 h, pending actions for the next 48 h, failed actions, orders from the last 36 h, recent
   Mailpit messages, and the board — into `automation/logs/`.
4. Runs Claude Code non-interactively (`claude --print --permission-mode bypassPermissions`) against that
   day's row of `watch-schedule.md`, with a 90-minute timeout.
5. The agent verifies what should have happened overnight, **executes that day's browser test tasks**,
   updates the board, files issues, and writes `watch-reports/D<NN>-<date>.md`.
6. If the agent produces no report, the script writes a stub so a missing day is visible rather than silent.
7. Leftover browser sessions are closed so the next run starts clean.

```bash
sudo automation/install-cron.sh      # install
sudo automation/uninstall-cron.sh    # stop early (it self-removes after D12 anyway)
automation/daily-renewal-check.sh    # safe dry run — exits quietly outside D1..D12
tail -f automation/logs/run-summary.log
```

---

## Layout

```
subscription-lifecycle-test/
├── README.md            this file
├── calendar.md          D0..D10 execution calendar — what a human runs each day
├── watch-schedule.md    D1..D12 — what the automated watch verifies each morning
├── catalog.md           SLT products, accounts, and coupons
├── reference/           SLT-REF-01..10, code-verified mechanics with file:line citations
├── issues/              one markdown file per finding
├── watch-reports/       one report per watch day
├── evidence/            screenshots, grouped by task key or watch day
├── automation/          the daily watch script, prompt, and cron installers
└── kanban/              the kanban-md board
```
