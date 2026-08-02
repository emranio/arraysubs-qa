---
id: 115
title: 'Capability gating: shop_manager vs editor vs subscriber on every admin surface'
status: todo
priority: medium
created: 2026-08-02T03:43:12.538116048+02:00
updated: 2026-08-02T03:43:24.047693579+02:00
tags:
    - admin
    - portal
    - day-09
    - has-conflicts
due: "2026-08-11"
estimate: 1h30m
depends_on:
    - 12
    - 5
    - 23
class: standard
---

> **SLT-ADM-10** · group `admin` · scheduled **D09** (2026-08-11)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`high` · session collision (shared admin session)** — with `SLT-EML-01`, `SLT-EML-02`, `SLT-EML-03`, `SLT-EML-05`, `SLT-EML-10`, `SLT-EML-12`

- *Problem:* More than twenty tasks open `--session admin` by that bare name, in direct violation of audit C09's fix (which only got applied to guest/customer sessions). agent-browser sessions are keyed by name, so these tasks share one browser profile: one task's `agent-browser close --session admin` logs out another mid-run; one task navigating the SPA away from a settings screen invalidates another's snapshot; and any admin session that ever adds to cart (SLT-ADM-01's bulk-action screen, SLT-EML-12's status juggling) puts a cart on the admin user shared by all of them. The failures this produces look like flaky UI, not contamination.
- *Required fix:* Rename every admin session to `admin-<TASK-KEY>` (SLT-ADM-01/02/03/04/05 already do this correctly - copy the pattern). Each task closes only its own session by name; `agent-browser close --all` is reserved to the last task of the day, named explicitly in the calendar. Add this to the isolation contract as rule 9 and add a pass criterion to every task that opens an admin session: 'the session name contains this task's key'.

---
## Objective
Prove the admin surfaces are gated by `manage_woocommerce`, not generic WordPress caps. The ArraySubs menu declares `manage_woocommerce` (`MainAdmin/Services/Hooks.php:26`) and every ArraySubs REST admin route checks `manage_woocommerce || manage_options` — but `arraysubs_data` registers `capability_type = post`, `map_meta_cap = true` and `show_in_rest = true` (`SubscriptionCPT.php:64-88`), metas gated on `edit_posts`. Whether `/wp/v2/arraysubs_data` leaks subscriptions to an editor is the headline probe.

## Scope
- Gateway: N/A
- Checkout: N/A
- Account: admin-created (role probes)
- Plugins: both

## Preconditions
- SLT-SETUP-03 done; S1 (`SLT Daily Core`, slt-core) and S_FAIL (`SLT Retry Daily`, slt-fail) exist with orders.
- This task ALSO CREATES three users, all `slt-`-prefixed so SLT-SETUP-99B removes them. Run D6 = 2026-08-08; read-only otherwise.

## Test data
| Item | Value |
|---|---|
| Users | `slt-shopmgr` / `slt-editor` / `slt-subscriber`, `<login>@example.test` |
| Roles | `shop_manager`, `editor`, `subscriber` |
| Password | `SltQa!2026#Pass` |
| Probes | S1, S_FAIL |

## Steps
1. `mailpit-agent latest-id` -> `M0`.
2. As `--session admin` open `https://mirror-help.arrayhash.com/wp-admin/user-new.php`; create the three users with **Send User Notification** unticked. Record their ids.
3. `mailpit-agent latest-id` must still equal `M0`.
4. In its own session (`cap-shopmgr`, `cap-editor`, `cap-sub`) log each role in at `/wp-admin`, `snapshot -i`, screenshot the menu; note whether **ArraySubs** appears.
5. In each session open these and record the outcome (renders / "Sorry, you are not allowed to access this page." / redirect):
   a-d. `/wp-admin/admin.php?page=arraysubs-mainadmin` with hash `#/subscriptions`, `#/subscriptions/detail/<S1>`, `#/audits/scheduled-job-logs`, `#/settings/general`; e. `?page=wc-orders`
6. In each session open these and record HTTP status + body code: `/wp-json/arraysubs/v1/subscriptions/<S1>/detail`, `.../<S1>/notes`, `.../audits/renewal-failures`, `.../settings/refunds`.
7. Core-CPT probe: in each session open `/wp-json/wp/v2/arraysubs_data?per_page=5`; record status and whether any subscription record, meta or customer id returns.
8. As `cap-sub` open `/my-account/` -> `snapshot -i`, then `/my-account/view-subscription/<S1>/` (slt-core's); copy the on-screen message.
9. As `cap-shopmgr` open `#/subscriptions/detail/<S_FAIL>`; confirm notes box and action buttons render. Click nothing.
10. `wp eval 'foreach(["slt-shopmgr","slt-editor","slt-subscriber"] as $u){$x=get_user_by("login",$u);echo $u,(int)user_can($x,"manage_woocommerce"),(int)user_can($x,"edit_posts"),(int)user_can($x,"manage_options");}' --allow-root`
11. Close `cap-shopmgr`, `cap-editor`, `cap-sub` by name.

## Expected results
1. `slt-shopmgr`: `manage_woocommerce=1`, `manage_options=0`. `slt-editor`: `manage_woocommerce=0`, `edit_posts=1`. `slt-subscriber`: all three 0.
2. shop_manager: ArraySubs menu present, 5a-5e render, every step-6 route returns 200.
3. editor: no ArraySubs menu, 5a-5e refused, every step-6 route 401/403 with `rest_forbidden` or the controller's error code.
4. subscriber: no ArraySubs menu, no wp-admin beyond the profile screen, all step-6 routes refused.
5. Step 7 is the finding: record what `/wp/v2/arraysubs_data` returns per role. Subscription data exposed to `editor` or `subscriber` is a **security issue** — file it with body, role and CPT registration.
6. The subscriber gets `Subscription not found or you do not have permission to view it.` on another customer's subscription, and an empty list of its own.
7. shop_manager sees the S_FAIL notes and actions; nothing clicked, no state changed, no mail sent.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | User creation, notice unticked | — | — | `mailpit-agent latest-id` at step 3 equals `M0` |
| 2 | NONE EXPECTED | All permission probes | — | — | `latest-id` at the end equals `M0` |

## Evidence to capture
- Screenshots per role: `SLT-ADM-10-<role>-menu.png`, `-<role>-subs.png`, `-<role>-rest.png`.
- The three user ids, the step-10 capability matrix, a role x route table of HTTP status + message, the `/wp/v2/arraysubs_data` body per role.

## Pass criteria
- [ ] Three role users created; zero mail
- [ ] shop_manager sees the menu, all five screens, all four REST routes (200)
- [ ] editor sees no menu and is refused on every ArraySubs screen and route
- [ ] subscriber refused everywhere; cannot view another customer's subscription
- [ ] `/wp/v2/arraysubs_data` behaviour recorded per role; issue filed if it leaks
- [ ] No subscription, order or setting modified

## Isolation / teardown
- Creates only `slt-shopmgr`, `slt-editor`, `slt-subscriber`; register them so SLT-SETUP-99B deletes them with the other `slt-*` users. Nothing that mutates state is clicked and no setting is written. Close the three probe sessions by name.

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
