---
id: 115
title: 'Capability gating: shop_manager vs editor vs subscriber on every admin surface'
status: todo
priority: medium
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - admin
    - portal
    - day-09
due: "2026-09-01"
estimate: 1h30m
depends_on:
    - 12
    - 5
    - 23
    - 1
    - 33
class: standard
---

> **SLT-ADM-10** · group `admin` · scheduled **D09** (2026-09-01)

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
- SLT-SETUP-03 done; S1 (`SLT2 Daily Core`, slt2-core) and S_FAIL (`SLT2 Retry Daily`, slt2-fail) exist with orders.
- This task ALSO CREATES three users, all `slt2-`-prefixed so SLT-SETUP-99B removes them. Run on its binding calendar date **D9 = 2026-09-01**; read-only after setup.

## Test data
| Item | Value |
|---|---|
| Users | `slt2-shopmgr` / `slt2-editor` / `slt2-subscriber`, `<login>@example.test` |
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
8. As `cap-sub-SLT-ADM-10` open `/my-account/` -> `snapshot -i`, then `/my-account/view-subscription/<S1>/` (slt2-core's); copy the on-screen message.
9. As `cap-shopmgr-SLT-ADM-10` open `#/subscriptions/detail/<S_FAIL>`; confirm notes box and action buttons render. Click nothing.
10. `wp eval 'foreach(["slt2-shopmgr","slt2-editor","slt2-subscriber"] as $u){$x=get_user_by("login",$u);echo $u,(int)user_can($x,"manage_woocommerce"),(int)user_can($x,"edit_posts"),(int)user_can($x,"manage_options");}' --allow-root`
11. Inspect the complete delta after M1 and require zero permission-probe-attributable mail. Close only the four exact sessions, independently review the role×route/body/capability matrix, then move through `review` to `done` with Review empty. Any security or authorization finding goes only in `qa/issues/` kanban card named `SLT-ADM-10-<concise-slug>` with task/stage/plan path; affected subscription/order/customer IDs; all probe user IDs/logins/emails/roles/capabilities; exact routes/sessions/status codes; reproduction; expected/actual; and redacted response/UI evidence. Never include secrets.

## Expected results
1. `slt2-shopmgr`: `manage_woocommerce=1`, `manage_options=0`. `slt2-editor`: `manage_woocommerce=0`, `edit_posts=1`. `slt2-subscriber`: all three 0.
2. shop_manager: ArraySubs menu present, 5a-5e render, every step-6 route returns 200.
3. editor: no ArraySubs menu, 5a-5e refused, every step-6 route 401/403 with `rest_forbidden` or the controller's error code.
4. subscriber: no ArraySubs menu, no wp-admin beyond the profile screen, all step-6 routes refused.
5. Record what `/wp/v2/arraysubs_data` returns per role. Subscription data exposed to `editor` or `subscriber` is a security failure and creates/updates a critical `qa/issues/` kanban card with response body, role and CPT evidence.
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
- [ ] Exact sessions closed; dedicated security findings and independent review reach `done` with Review empty

## Isolation / teardown
- Creates only `slt2-shopmgr`, `slt2-editor`, `slt2-subscriber`; register them so SLT-SETUP-99B deletes them with the other `slt2-*` users. Nothing that mutates state is clicked and no setting is written. Close the three probe sessions by name.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
