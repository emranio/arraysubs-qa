# ArraySubs QA Operations

Last updated: 2026-05-19

## Live Site Access

- Admin URL: https://mirror-help.arrayhash.com/wp-admin
- Username: `admin`
- Password: `@GuDw(0$K7M9t8ehjqDb4Vwj`

## QA Scope

The primary QA plan is in `qa/stages/README.md`.

- 21 ordered stages: `00-preflight` through `20-edge-and-regression`
- 154 executable task files
- Browser-first E2E QA against a real WordPress/WooCommerce site
- ArraySubs and ArraySubsPro must both be considered unless a task explicitly says free-only or pro-only
- Later stages depend on data created earlier, so do not reset the site between stages unless the stage file explicitly allows it

Current live site facts verified from this machine:

- Site URL: `https://mirror-help.arrayhash.com`
- WordPress root: `/home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public`
- Plugin workspace: `/home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public/wp-content/plugins`
- Active plugins: `arraysubs`, `arraysubspro`, `dev-assist`, `woocommerce`, `woocommerce-gateway-stripe`, `wpforms-lite`, `wp-mail-smtp`

## Local Tooling Baseline

Installed and usable:

- Node `v24.13.0`
- npm / npx `11.6.2`
- PHP CLI `8.2.30`
- WP-CLI `2.12.0`
- MySQL client `8.0.45`
- Docker
- `kanban-md`
- Composer `2.7.1`
- agent-browser CLI (Vercel) for browser automation

Missing or not found:

- GitHub CLI (`gh`) is not installed.

WP-CLI commands need DB access. From sandboxed agent sessions, DB access may require escalated command approval.

Recommended shell aliases for manual QA:

```bash
WP_ROOT=/home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public
PLUGIN_ROOT="$WP_ROOT/wp-content/plugins"
QA_ROOT="$PLUGIN_ROOT/qa"
WP="wp --path=$WP_ROOT --allow-root"
```

Useful checks:

```bash
$WP plugin list --status=active
$WP cron event list --fields=hook,next_run_relative,recurrence
$WP cron event run --due-now
$WP action-scheduler status
$WP action-scheduler run --hooks=arraysubs_generate_upcoming_renewals --batch-size=10 --force
```

This site's Action Scheduler CLI has `run`, `status`, `source`, `version`, `clean`, and related subcommands, but no `list` subcommand. Use the wp-admin Scheduled Actions UI for queue inspection, or add a dedicated QA helper if CLI listing becomes necessary.

## Recommended Machine Setup

### 1. Browser Automation Layer

Use Vercel's agent-browser CLI for browser automation, repeatable UI checks, screenshots, and exploratory visual inspection, while keeping the stage files as the source of truth.

Recommended workflow:

- Load the usage guide first (`agent-browser skills get core`), then drive the target site with the agent-browser CLI, using an isolated `--session` per role.
- Use the documented QA credentials and role-specific accounts from the active stage.
- Run real end-to-end browser flows against the live test site.
- Capture screenshots and concrete browser observations for sign-off evidence, failures, and layout-sensitive checks.
- Use screenshot-based UI inspection to understand screen state, visual hierarchy, visibility, spacing, loading states, disabled states, modals, notices, and interaction behavior before passing or filing an issue.
- Use separate browser sessions or contexts for admin, shop manager, customer, and guest flows when role isolation matters.
- Record console errors, failed network responses, current URL, viewport, and screenshots in the stage/task notes or issue body.
- Group artifacts and notes by `stage/task`, for example `05-checkout/01-classic-checkout-basic-subscription/`.
- Create one smoke flow per stage first; only automate deeper flows after the manual plan stabilizes.

### 2. WordPress Time-Travel Tooling

Do not change the system clock on this live/staging machine unless the environment has been isolated and the user explicitly approves it. System clock changes can corrupt gateway auth, cookies, cron timing, and unrelated tests.

Preferred time-travel methods:

1. Admin UI: edit subscription date meta such as `_next_payment_date`, `_trial_end_date`, or `_end_date` from the subscription screen or Custom Fields panel.
2. WP-CLI: update exact post meta, then run the specific Action Scheduler hook.
3. Optional QA-only helper plugin or mu-plugin: expose a guarded admin page for adjusting ArraySubs test dates and running whitelisted hooks. Keep it disabled outside QA.

WP-CLI pattern:

```bash
$WP post meta update SUBSCRIPTION_ID _next_payment_date "YYYY-MM-DD HH:MM:SS"
$WP action-scheduler run --hooks=arraysubs_generate_upcoming_renewals --batch-size=10 --force
```

Stage 18 is the canonical renewal time-travel stage. Keep its selected method documented in `qa/stages/18-renewal-followup/01-time-travel-method.md`.

### 3. Snapshots And Recovery

Take database snapshots after the stable setup milestones named by the QA plan:

```bash
mkdir -p "$QA_ROOT/artifacts/db"
$WP db export "$QA_ROOT/artifacts/db/post-stage-03-products.sql"
$WP db export "$QA_ROOT/artifacts/db/post-stage-06-subscriptions.sql"
```

Restore only when the user approves a reset:

```bash
$WP db import "$QA_ROOT/artifacts/db/post-stage-03-products.sql"
```

### 4. Mail, Logs, And Cron

Before Stage 00 sign-off:

- Confirm the OS cron entry in `/etc/cron.d/mirror-help-arrayhash-wordpress` is present and points at the current WordPress root.
- Confirm Action Scheduler is advancing every minute.
- Confirm WP Mail SMTP is sending to a test inbox or mail catcher.
- Keep `wp-content/debug.log` visible while running high-risk stages.
- Treat any PHP notice, JavaScript console error, REST 4xx/5xx, or asset 404 as a QA failure.

Current cron entry:

```cron
* * * * * www-data /usr/bin/flock -n /tmp/mirror-help.arrayhash.com-wp-cron.lock /usr/bin/php /home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public/wp-cron.php >/dev/null 2>&1
```

Verified on 2026-05-19: cron service is active and this command is running once per minute as `www-data`.

## Kanban Workflow

Use the `kanban-based-development` workflow for QA execution and defect tracking.

There are two separate boards:

- Progress board: `qa/progress`
  - Tracks stage execution work.
  - One task should reference one stage task file or a tightly grouped set of stage task files.
- Issues board: `qa/issues`
  - Tracks product bugs, QA plan defects, setup blockers, and regressions discovered during QA.
  - Every issue task must reference the exact stage/task/sub-task where it was found.

Both boards currently use these statuses:

- `open`
- `in-progress`
- `blocked`
- `closed`

### Starting Any QA Session

Generate an agent name once per session and use it for claims:

```bash
cd /home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public/wp-content/plugins/qa/progress
kanban-md agent-name
```

Check both boards before doing stage work:

```bash
cd /home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public/wp-content/plugins/qa/progress
kanban-md board --compact
kanban-md list --status open --compact
kanban-md list --status in-progress --compact
kanban-md list --status blocked --compact

cd /home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public/wp-content/plugins/qa/issues
kanban-md board --compact
kanban-md list --status open --compact
kanban-md list --status in-progress --compact
kanban-md list --status blocked --compact
```

### When The User Says "Work On This Stage"

1. Check `qa/progress` first.
2. If any `open`, `in-progress`, or `blocked` progress tasks remain for the current or earlier stage, report them to the user and do not proceed until they are cleared or the user explicitly reprioritizes.
3. If the progress board has no blocking tasks for the requested stage, analyze the requested stage folder under `qa/stages/`.
4. Create progress tasks before running QA. Each task body must include:
   - Stage name and number
   - Reference `.md` file path
   - Preconditions
   - Expected artifacts or screenshots
   - Any dependency on earlier stages
5. Claim the task before starting browser work.
6. Move the task through `in-progress` and then `closed` only after the stage task is executed and evidence is recorded.

Example progress task:

```bash
cd /home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public/wp-content/plugins/qa/progress
kanban-md create "Stage 05 Task 01 - Classic checkout basic subscription" \
  --status open \
  --priority high \
  --tags stage-05,checkout,qa \
  --body "Reference: ../stages/05-checkout/01-classic-checkout-basic-subscription.md

Run the task exactly as written. Capture checkout screenshots, console/network failures, order ID, subscription ID, and sign-off notes."
```

### Filing Bugs And QA Issues

Create a task in `qa/issues` for every bug, setup blocker, or QA plan defect found during QA.

Issue task body template:

```text
Found in: qa/stages/STAGE/TASK.md, Sub-Task X.Y
Environment: browser/version, viewport, logged-in role
Expected:
Actual:
Evidence: screenshot path, trace path, console/network details
Reproduction steps:
Likely area: arraysubs / arraysubspro / WooCommerce / QA plan / environment
Blocks stage: yes/no
```

Example:

```bash
cd /home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public/wp-content/plugins/qa/issues
kanban-md create "Bug: Renewal action creates duplicate pending invoice" \
  --status open \
  --priority critical \
  --tags stage-18,renewal,arraysubs \
  --body "Found in: ../stages/18-renewal-followup/02-successful-manual-renewal.md, Sub-Task 02.4

Expected:
Actual:
Evidence:
Reproduction steps:
Blocks stage: yes"
```

If an issue blocks the active stage, also mark the progress task as `blocked` and reference the issue task ID in the progress task body.

### Maintaining The Two Boards

- `qa/progress` answers: what QA stage work is currently planned, active, blocked, or completed?
- `qa/issues` answers: what defects or setup problems were discovered?
- Do not mix product bugs into the progress board.
- Do not use the issues board to track normal stage execution.
- Keep references as relative paths to `qa/stages/...` wherever possible.
- Before continuing a stage, always clear or explicitly defer earlier unfinished progress tasks.

## Immediate Setup Gaps To Resolve

- Decide whether to add a QA-only date/action helper plugin for controlled time-travel.
- Confirm mail capture/delivery tooling before Stage 00.
- Decide whether GitHub CLI is needed on this machine.
- Server has a pending kernel upgrade notice (`6.8.0-90-generic` running, `6.8.0-111-generic` expected). Reboot only in an approved maintenance window.
