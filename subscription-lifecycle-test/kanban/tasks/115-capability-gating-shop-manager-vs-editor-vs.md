---
id: 115
title: 'Capability gating: shop_manager vs editor vs subscriber on every admin surface'
status: done
priority: medium
created: 2026-08-02T03:43:12.538116048+02:00
updated: 2026-08-05T21:40:40.256721293+02:00
started: 2026-08-05T21:40:40.256719801+02:00
completed: 2026-08-05T21:40:40.256719801+02:00
tags:
    - admin
    - portal
    - day-09
due: "2026-08-11"
estimate: 1h30m
depends_on:
    - 12
    - 5
    - 23
    - 1
    - 33
class: standard
---

> **SLT-ADM-10** · group `admin` · scheduled **D09** (2026-08-11)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Prove the admin surfaces are gated by `manage_woocommerce`, not generic WordPress caps. The ArraySubs menu declares `manage_woocommerce` (`MainAdmin/Services/Hooks.php:26`) and every ArraySubs REST admin route checks `manage_woocommerce || manage_options` — but `arraysubs_data` registers `capability_type = post`, `map_meta_cap = true` and `show_in_rest = true` (`SubscriptionCPT.php:64-88`), metas gated on `edit_posts`. Whether `/wp/v2/arraysubs_data` leaks subscriptions to an editor is the headline probe.

## Scope
- Gateway: N/A
- Checkout: N/A
- Account: admin-created (role probes)
- Plugins: both

## Preconditions
- SLT-SETUP-03 done; S1 (`SLT Daily Core`, slt-core) and S_FAIL (`SLT Retry Daily`, slt-fail) exist with orders.
- This task ALSO CREATES three users, all `slt-`-prefixed so SLT-SETUP-99B removes them. Run on its binding calendar date **D9 = 2026-08-11**; read-only after setup.

## Test data
| Item | Value |
|---|---|
| Users | `slt-shopmgr` / `slt-editor` / `slt-subscriber`, `<login>@example.test` |
| Roles | `shop_manager`, `editor`, `subscriber` |
| Password | `SltQa!2026#Pass` |
| Probes | S1, S_FAIL |

## Steps
1. `M0=$(mailpit-agent latest-id)`.
2. As `--session admin-SLT-ADM-10` open `https://mirror-help.arrayhash.com/wp-admin/user-new.php`; create the three users with **Send User Notification** unticked. Record their ids.
3. Inspect the complete delta after `M0`: require exactly three admin-only `New User Registration` messages, one per created user, and zero customer account/password messages; classify unrelated/background mail separately. Set `M1=$(mailpit-agent latest-id)` for the permission-probe silence baseline.
4. In its own task-keyed session (`cap-shopmgr-SLT-ADM-10`, `cap-editor-SLT-ADM-10`, `cap-sub-SLT-ADM-10`) log each role in at `/wp-admin`, `snapshot -i`, screenshot the menu; note whether **ArraySubs** appears.
5. In each session open these and record the outcome (renders / "Sorry, you are not allowed to access this page." / redirect):
   a-d. `/wp-admin/admin.php?page=arraysubs-mainadmin` with hash `#/subscriptions`, `#/subscriptions/detail/<S1>`, `#/audits/scheduled-job-logs`, `#/settings/general`; e. `?page=wc-orders`
6. In each session open these and record HTTP status + body code: `/wp-json/arraysubs/v1/subscriptions/<S1>/detail`, `.../<S1>/notes`, `.../audits/renewal-failures`, `.../settings/refunds`.
7. Core-CPT probe: in each session open `/wp-json/wp/v2/arraysubs_data?per_page=5`; record status and whether any subscription record, meta or customer id returns.
8. As `cap-sub-SLT-ADM-10` open `/my-account/` -> `snapshot -i`, then `/my-account/view-subscription/<S1>/` (slt-core's); copy the on-screen message.
9. As `cap-shopmgr-SLT-ADM-10` open `#/subscriptions/detail/<S_FAIL>`; confirm notes box and action buttons render. Click nothing.
10. `wp eval 'foreach(["slt-shopmgr","slt-editor","slt-subscriber"] as $u){$x=get_user_by("login",$u);echo $u,(int)user_can($x,"manage_woocommerce"),(int)user_can($x,"edit_posts"),(int)user_can($x,"manage_options");}' --allow-root`
11. Inspect the complete delta after M1 and require zero permission-probe-attributable mail. Close only the four exact sessions, independently review the role×route/body/capability matrix, then move through `review` to `done` with Review empty. Any security or authorization finding goes only in `issues/SLT-ADM-10-<concise-slug>.md` with task/stage/plan path; affected subscription/order/customer IDs; all probe user IDs/logins/emails/roles/capabilities; exact routes/sessions/status codes; reproduction; expected/actual; and redacted response/UI evidence. Never include secrets.

## Expected results
1. `slt-shopmgr`: `manage_woocommerce=1`, `manage_options=0`. `slt-editor`: `manage_woocommerce=0`, `edit_posts=1`. `slt-subscriber`: all three 0.
2. shop_manager: ArraySubs menu present, 5a-5e render, every step-6 route returns 200.
3. editor: no ArraySubs menu, 5a-5e refused, every step-6 route 401/403 with `rest_forbidden` or the controller's error code.
4. subscriber: no ArraySubs menu, no wp-admin beyond the profile screen, all step-6 routes refused.
5. Step 7 is the finding: record what `/wp/v2/arraysubs_data` returns per role. Subscription data exposed to `editor` or `subscriber` is a **security issue** — write a standalone markdown file under `issues/` with body, role and CPT registration; never create a lifecycle-board card.
6. The subscriber gets `Subscription not found or you do not have permission to view it.` on another customer's subscription, and an empty list of its own.
7. shop_manager sees the S_FAIL notes and actions; nothing clicked, no state changed, no mail sent.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | WP New User Registration ×3 | User creation, customer notification unticked | admin | `New User Registration` | exactly three after `M0`; zero customer account/password mail |
| 2 | NONE EXPECTED | All permission probes | — | — | Complete delta after `M1`; zero permission-probe-attributable mail, while unrelated/background mail is allowed and classified |

## Evidence to capture
- Screenshots per role: `SLT-ADM-10-<role>-menu.png`, `-<role>-subs.png`, `-<role>-rest.png`.
- The three user ids, the step-10 capability matrix, a role x route table of HTTP status + message, the `/wp/v2/arraysubs_data` body per role.

## Pass criteria
- [ ] Three role users created; exactly three admin registration notices, zero customer account mail, and zero probe-triggered mail
- [ ] shop_manager sees the menu, all five screens, all four REST routes (200)
- [ ] editor sees no menu and is refused on every ArraySubs screen and route
- [ ] subscriber refused everywhere; cannot view another customer's subscription
- [ ] `/wp/v2/arraysubs_data` behaviour recorded per role; issue filed if it leaks
- [ ] No subscription, order or setting modified
- [ ] Exact sessions closed; standalone security findings and independent review reach `done` with Review empty

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
  fire on their own; **a renewal that does not fire is a real bug** — capture evidence and do not force a natural-watch action.
- Give this task its own browser session (`agent-browser --session <role>-<TASK-KEY>`). Sessions are
  keyed by name and **share a cart**.
- Never run a bare or `--hooks=` Action Scheduler drain. Run one known action ID at a time only when the task explicitly authorizes it and after the required queue pre-flight; natural-watch actions are never forced.
- Evidence goes under `/home/server-manager/slt-evidence/` using task-key-prefixed filenames.

[[2026-08-05]] Wed 21:40
UNVERIFIED (required S_FAIL probe fixture absent) on 2026-08-05.

This authored card is no longer executable as written because its own preconditions and pass criteria require `S_FAIL` to exist with orders, and step 9 explicitly requires opening `#/subscriptions/detail/<S_FAIL>` so shop_manager visibility of that failing-ladder subscription's notes/actions can be checked. Upstream task #33 published the immutable `S_FAIL unavailable` branch on 2026-08-05, task #101 then closed because the ladder's terminal cancellation can never occur, and live verification still shows no ArraySubs subscription row for `slt-fail` user 351 on product 12108. That means the named `S_FAIL` probe object for this task will never exist.

Most of the role/capability matrix could be reauthored around `S1` alone, but this kanban card was not written that way: it names both `S1` and `S_FAIL` as required probes and includes expected result 7 (`shop_manager sees the S_FAIL notes and actions`) as a pass condition. No later recovery path authorizes fabricating the missing dunning fixture or silently substituting another subscription for the authored `S_FAIL` route. Closing this specific card `UNVERIFIED` rather than mutating scope.
