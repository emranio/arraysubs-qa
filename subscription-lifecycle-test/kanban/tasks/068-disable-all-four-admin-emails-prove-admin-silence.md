---
id: 68
title: Disable all four admin emails, prove admin silence with customer mail unaffected, restore and re-prove
status: todo
priority: high
created: 2026-08-02T03:43:09.077879164+02:00
updated: 2026-08-02T03:43:19.545028873+02:00
tags:
    - email
    - day-04
    - has-conflicts
due: "2026-08-06"
estimate: 1h 30m
depends_on:
    - 55
    - 56
class: standard
---

> **SLT-EML-13** · group `emails` · scheduled **D04** (2026-08-06)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`high` · session collision (shared admin session)** — with `SLT-EML-01`, `SLT-EML-02`, `SLT-EML-03`, `SLT-EML-05`, `SLT-EML-10`, `SLT-EML-12`

- *Problem:* More than twenty tasks open `--session admin` by that bare name, in direct violation of audit C09's fix (which only got applied to guest/customer sessions). agent-browser sessions are keyed by name, so these tasks share one browser profile: one task's `agent-browser close --session admin` logs out another mid-run; one task navigating the SPA away from a settings screen invalidates another's snapshot; and any admin session that ever adds to cart (SLT-ADM-01's bulk-action screen, SLT-EML-12's status juggling) puts a cart on the admin user shared by all of them. The failures this produces look like flaky UI, not contamination.
- *Required fix:* Rename every admin session to `admin-<TASK-KEY>` (SLT-ADM-01/02/03/04/05 already do this correctly - copy the pattern). Each task closes only its own session by name; `agent-browser close --all` is reserved to the last task of the day, named explicitly in the calendar. Add this to the isolation contract as rule 9 and add a pass criterion to every task that opens an admin session: 'the session name contains this task's key'.

**`high` · shared-global-setting / undeclared exclusive bracket** — with `SLT-CHK-08`, `SLT-CHK-13`, `SLT-SYN-07`, `SLT-SYN-11`, `SLT-SW-09`, `SLT-IMP-03`

- *Problem:* SLT-EML-13 (d4) disables all four ArraySubs admin emails site-wide for a bracket it bounds only as '08:00-09:00 site, under 20 min'. D4 (2026-08-06) carries the heaviest checkout load of the middle of the window: SLT-CHK-08 places two checkouts, SLT-SYN-11 three, SLT-IMP-03 three, SLT-SW-09 two, plus SLT-CHK-13 and SLT-SYN-07. Every admin_new_subscription for a checkout inside the bracket is silently lost, and those tasks' email tables assert it as present. SLT-ADM-03/ADM-05 also drive status transitions on D4 whose admin notifications would vanish. Conversely, if any of those checkouts drifts into the bracket, EML-13's own 'exactly one message' silence proof is contaminated by their customer mail.
- *Required fix:* Fix the bracket at 08:00-08:20 site on D4 and make it the FIRST thing that happens that day - before any product save, cart, checkout or status change. Add a pre-flight step (already half-present as step 1): screenshot Tools -> Scheduled Actions Pending for the next 2h and abort if any renewal/retry/overdue/cancel action is due, AND assert no SLT checkout task is in-progress on the board. Publish the open/close UTC to the registry. Add 'no checkout before 08:30 site on D4' to the D4 row of the calendar.

---
## Objective
Prove the admin-email gate is independent of the customer gate: disable all four ArraySubs admin emails, fire a transition that normally sends one customer and one admin message, show the customer one still arrives and the admin one does not, then restore and re-prove. The OFF bracket is time-boxed and recorded because the toggles are site-wide.

## Scope
- Gateway: N/A (no payment taken)
- Checkout: N/A
- Account: existing (`slt-email`, harness H1)
- Plugins: free-only

## Preconditions
- SLT-EML-11 and -12 complete; H1 is `arraysubs-active` with the `arraysubs_new_subscription` overrides cleared.
- Four admin emails (SLT-REF-04): sections `arraysubs_admin_new_subscription`, `_admin_payment_failed`, `_admin_subscription_cancelled`, `_admin_subscription_pending_cancellation`; all `customer_email = false`, recipient from **Recipient(s)** defaulting to `get_option('admin_email')` (`BaseSubscriptionEmail.php:202-209`). `admin_new_subscription` fires alongside `new_subscription` (`EmailManager.php:332,340`), so one Pending→Active exercises both gates.
- **Bracket:** OFF **08:00–09:00 site on D4 (2026-08-06)**, under 20 min — Retry Daily's retry #2 and on-hold land that afternoon and `admin_payment_failed` must not be swallowed.
- The SPA has no `/settings/emails` route and `emails.admin_*` is writable only by Easy Setup, which rewrites the whole blob. **Do not run Easy Setup.**

## Test data
| Item | Value |
|---|---|
| Subscription | H1 (SLT Lifetime One Time, `slt-email@example.test`) |
| Admin subject | `[mirror-help.arrayhash.com] New subscription #{subscription_id} from {customer_name}` |

## Steps
1. Dump the four `woocommerce_arraysubs_admin_*_settings` rows and `admin_email` to `/home/server-manager/slt-evidence/SLT-EML-13-priors.txt`. Screenshot Tools → Scheduled Actions (Pending, next 2 h); abort if any renewal/retry/overdue action is due.
2. `MP0=$(mailpit-agent latest-id)`; `--session admin` open `page=arraysubs-mainadmin#/subscriptions`, open H1, **Status** = `Pending`, save; then `Active`, save.
3. `wait-new $MP0 180 "New subscription #<H1>"` must succeed; `list 20` shows both the customer `is active` message and the admin message. Record both ids and the admin recipient.
4. **Open the OFF bracket** (record UTC). At `?page=wc-settings&tab=email` screenshot the list with all four admin rows Enabled; then for each open `&section=<section>`, untick **Enable this email notification**, Save, screenshot. Do **not** touch Recipient(s), Subject or Heading.
5. `MP1=$(mailpit-agent latest-id)`; set H1 `Pending`, then `Active`.
6. `wait-new $MP1 180 "New subscription #<H1>"` — **must exit 124**; then `wait-new $MP1 180 "is active"` — **must succeed** (the scoping proof). `list 20`: one new id above `MP1`, to slt-email@, nothing to admin_email.
7. **Restore:** re-tick **Enable** on all four sections, Save, screenshot each; record UTC close.
8. `MP2=$(mailpit-agent latest-id)`; set H1 `Pending` → `Active`; `wait-new $MP2 180 "New subscription #<H1>"` must succeed and the customer message must also arrive. Leave H1 `Active`.
9. Dump the four rows to `-after.txt` and diff against the priors.

## Expected results
1. Baseline: one Pending→Active yields exactly two messages — customer `Your subscription #<H1> is active` and admin `New subscription #<H1> from SLT Email`, the latter To `get_option('admin_email')`.
2. With all four disabled the same transition yields exactly **one** message (the customer one); the admin `wait-new` exits 124 and no admin-addressed message exists in the bracket.
3. The customer email's subject, body and recipient are unchanged by the admin toggles; after restore both messages return unchanged.
4. The four rows end `enabled => yes` (a row previously `a:0:{}` is now populated — accepted residue) and all four `arraysubs_settings.emails.admin_*` booleans are still `true`, proving the WooCommerce checkbox is a second, independent gate.
5. Positive proof of the other three admin emails comes from the watch: `admin_payment_failed` on **D4 = 2026-08-06**, `admin_subscription_cancelled` on **D8 = 2026-08-10**; `admin_subscription_pending_cancellation` has no producer this window.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | new_subscription + admin_new_subscription | Steps 2 and 8, ON | slt-email@ / admin_email | `is active` / `New subscription #<H1>` | `wait-new $MP0 180`, `wait-new $MP2 180` |
| 2 | NONE EXPECTED — admin_new_subscription | Step 5, admin OFF | — | — | `wait-new $MP1 180 "New subscription #<H1>"` exits **124** |
| 3 | new_subscription | Step 5, admin OFF | slt-email@example.test | `is active` | `wait-new $MP1 180 "is active"` succeeds |

## Evidence to capture
- Screenshots `SLT-EML-13-01-pending-clear.png`, `-02-list-before.png`, `-03..06-disabled.png`, `-07-silence.png`, `-08-restored.png`; `-priors.txt`, `-after.txt` and their diff; `MP0/MP1/MP2` and delivered ids with To headers; UTC bracket open/close.

## Pass criteria
- [ ] Baseline transition produces both a customer and an admin message
- [ ] All four disabled via the WooCommerce checkbox only (Easy Setup never opened)
- [ ] Admin message absent while OFF (exit 124); zero admin-addressed mail in the bracket
- [ ] Customer message still delivered while admin emails are OFF
- [ ] All four restored and re-proved live; `emails.admin_*` booleans untouched
- [ ] Bracket under 20 min with UTC recorded; watch days for the other three noted

## Isolation / teardown
- Global settings touched: the `enabled` flag on four `woocommerce_arraysubs_admin_*_settings` rows, OFF only inside the recorded bracket. Any admin notification for a non-SLT subscription in that window is lost — hence the short, pre-checked bracket away from the Retry Daily afternoon events.
- Handed on: H1 left `arraysubs-active`; bracket timestamps posted to the registry. Restores: all four checkboxes re-enabled in step 7, proved in step 8.


---

### Verified environment facts (2026-08-01/02 — do not re-derive)

- **Nothing fires at `_next_payment_date`.** Every scheduled leg is shifted by
  `crc32('arraysubs-spread-'.$subscription_id) % 21600` (0-6 h). Charge fires at `due + offset`,
  invoice at `due + offset - 6h`. The stored date never moves. **Assert a window, not a point.**
- Currency `USD`. **Taxes are OFF** (`woocommerce_calc_taxes = no`) — never assert a tax line.
- Orders use **HPOS** (`wp_wc_orders`), not `wp_posts`.
- `woocommerce_enable_guest_checkout = yes`, but ArraySubs force-requires registration for
  **subscription** carts via `woocommerce_checkout_registration_required`
  (`SubscriptionCheckout/Services/Hooks.php:103`, `CheckoutHelpersTrait.php:93-100`).
- WooCommerce **grouped** products have zero handling in either plugin — grouped tasks are
  exploratory: document behaviour, do not assert a spec.
- WP-Cron runs every minute from `/etc/cron.d/mirror-help-arrayhash-wordpress`. Scheduled actions
  fire on their own; **a renewal that does not fire is a real bug** — capture evidence before forcing.
- Give this task its own browser session (`agent-browser --session <role>-<TASK-KEY>`). Sessions are
  keyed by name and **share a cart**.
- Never run `wp action-scheduler run` without `--hooks=`; prefer a single action by ID.
- Evidence goes in `qa/subscription-lifecycle-test/evidence/<TASK-KEY>/`.
