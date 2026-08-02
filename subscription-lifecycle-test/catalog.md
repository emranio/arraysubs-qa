# SLT catalog — environment, accounts, coupons, products

> Authored 2026-08-01, re-anchored to **D0 = 2026-08-02**.
> These are the Day-0 foundation tasks. Every other task in the plan references the
> products, accounts, and coupons defined here by name. Each section below is the full
> body of the corresponding board task — the board is the working copy, this file is the
> reference copy.

## Contents

- **Environment, baseline settings, accounts, coupons** — SLT-SETUP-01, SLT-SETUP-02, SLT-SETUP-03, SLT-SETUP-04, SLT-SETUP-05, SLT-SETUP-99
- **Product catalog** — SLT-PROD-01, SLT-PROD-02, SLT-PROD-03, SLT-PROD-04, SLT-PROD-05, SLT-PROD-06, SLT-PROD-07, SLT-PROD-08, SLT-PROD-09, SLT-PROD-10, SLT-PROD-11, SLT-PROD-12, SLT-PROD-13, SLT-PROD-14, SLT-PROD-15, SLT-PROD-16
- **Flexible-renewal-sync audits and control products** — SLT-SYN-01, SLT-SYN-02, SLT-SYN-03, SLT-SYN-04


---

# Environment, baseline settings, accounts, coupons


## SLT-SETUP-01 Recon environment, create SLT evidence + classic checkout pages, publish registry

*day D00 · priority critical · estimate 1h*

## Objective
Establish the shared ground truth every other SLT task depends on: record the untouched environment, create the SLT-only helper pages (classic cart/checkout, catalog registry), verify Mailpit and Action Scheduler respond, and publish the naming/ID conventions so no later author has to make a design decision.

## Scope
- Gateway: both
- Checkout: both
- Account: N/A
- Plugins: both

## Preconditions
- No prerequisite tasks. This is the first task of the window.
- Verified facts (do NOT re-verify): site timezone UTC+6, `gmt_offset=6`, `timezone_string` empty; site-local midnight 2026-08-01 == `2026-07-31 18:00:00` UTC; currency USD; `woocommerce_price_num_decimals=2`; `woocommerce_calc_taxes=no`; `start_of_week=6` (Saturday); `woocommerce_enable_guest_checkout=no`; `blogname=mirror-help.arrayhash.com`.
- Cart page and Checkout page are BLOCK based (`wp:woocommerce/cart`, `wp:woocommerce/checkout`). Classic checkout does not exist yet — this task creates it.

## Test data
| Item | Value |
|---|---|
| Product | N/A |
| Account | admin / @GuDw(0$K7M9t8ehjqDb4Vwj |
| Coupon | N/A |
| Card | N/A |
| Amounts | N/A |

## Steps
1. `mkdir -p /home/server-manager/slt-evidence` — the single evidence root for the whole window. Every screenshot is `<TASK-KEY>-NN-<slug>.png`.
2. Snapshot the untouched settings blob to a file that SLT-SETUP-99 will diff against: from WP root run `wp option get arraysubs_settings --format=json --allow-root > /home/server-manager/slt-evidence/SLT-SETUP-01-arraysubs_settings-D0.json`.
3. Record the WooCommerce/gateway baseline: `wp option get woocommerce_stripe_settings --format=json --allow-root` and `wp option get woocommerce_arraysubs_paddle_settings --format=json --allow-root`, saving both under `/home/server-manager/slt-evidence/`.
4. Confirm Mailpit is reachable and capture the pre-window message id: `mailpit-agent status` then `mailpit-agent latest-id`. Record the id as `MAILPIT_BASE`.
5. Confirm Action Scheduler CLI: `wp action-scheduler status --allow-root`. Note that this install has `run`, `status`, `source`, `clean` but NO `list` — queue inspection is via wp-admin -> Tools -> Scheduled Actions.
6. `agent-browser skills get core` (mandatory before any browser work in this window).
7. `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin"` -> `agent-browser --session admin snapshot -i` -> log in as `admin` if the snapshot shows the login form.
8. Create the classic-checkout harness page: `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin/post-new.php?post_type=page"`; title `SLT Classic Checkout`; in the block editor add a Shortcode block containing `[woocommerce_checkout]`; set the URL slug to `slt-classic-checkout`; Publish.
9. Create the classic-cart harness page the same way: title `SLT Classic Cart`, Shortcode block `[woocommerce_cart]`, slug `slt-classic-cart`, Publish.
10. Create the shared ID registry: new page, title `SLT Catalog Registry`, slug `slt-catalog-registry`, visibility **Private**, body = an empty markdown-style table with header row `| Key | Artifact | Type | WP ID | Notes |`. Publish. Every later SLT task appends its created IDs here.
11. Verify the two harness pages render: `agent-browser --session guest open "https://mirror-help.arrayhash.com/slt-classic-cart"` -> `snapshot -i` (expect the classic empty-cart notice, not a block skeleton).
12. Screenshot the ArraySubs settings landing page for the before-state: `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/settings/general"` -> `agent-browser --session admin screenshot --full /home/server-manager/slt-evidence/SLT-SETUP-01-01-settings-general-before.png`.
13. `agent-browser close --all`.

## Expected results
1. `/home/server-manager/slt-evidence/` exists and holds `SLT-SETUP-01-arraysubs_settings-D0.json` with the pre-window `arraysubs_settings` blob.
2. `mailpit-agent status` reports the sink is up and `MAILPIT_BASE` is recorded as an integer message id.
3. `wp action-scheduler status --allow-root` returns without error and shows pending/complete counts.
4. Page `slt-classic-checkout` exists, is published, and renders the classic checkout shortcode output (not the block checkout).
5. Page `slt-classic-cart` exists, is published, and renders the classic cart shortcode output.
6. Page `slt-catalog-registry` exists, is Private, and contains the registry table header.
7. No existing product, order, subscription, coupon, user, page or setting was modified by this task.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Whole task | — | — | `mailpit-agent latest-id` at the end must equal `MAILPIT_BASE` recorded in step 4 |

## Evidence to capture
- Screenshots: `SLT-SETUP-01-01-settings-general-before.png`, `SLT-SETUP-01-02-classic-cart-renders.png`, `SLT-SETUP-01-03-registry-page.png`.
- WP IDs of the three created pages.
- `MAILPIT_BASE` value, `wp action-scheduler status` output, both settings JSON dumps.
- Any console/network errors from the block editor while publishing.

## Pass criteria
- [ ] Evidence root and D0 settings dump exist
- [ ] Mailpit reachable and MAILPIT_BASE recorded
- [ ] Action Scheduler status returns cleanly
- [ ] slt-classic-checkout published and renders classic checkout
- [ ] slt-classic-cart published and renders classic cart
- [ ] slt-catalog-registry published as Private with the header row
- [ ] No non-SLT artifact touched

## Isolation / teardown
- Leaves behind for every later task: the evidence root `/home/server-manager/slt-evidence/`, the classic checkout/cart harness pages (use these whenever a task's Scope says `Checkout: classic`), the private ID registry page, and `MAILPIT_BASE`.
- Conventions fixed here and binding on all SLT tasks: products titled `SLT <Name>` / slug `slt-<name>`; users `slt-<purpose>` / `slt-<purpose>@example.test`; coupons `SLT<PURPOSE>`; every SLT product is **Virtual** (keeps the pro SubscriptionShipping fields out of scope) and has stock management OFF.
- Restores: nothing (this task changes no setting). The three pages are torn down by SLT-SETUP-99.


## SLT-SETUP-02 Apply and record the four window-wide baseline setting changes

*day D00 · priority critical · estimate 45m*

## Objective
Flip exactly four global settings that the 10-day plan depends on, record each prior value verbatim, and declare them frozen for the window so no other task touches them. The most consequential is turning global renewal sync OFF: with it ON, `arraysubs_subscription_data_supports_renewal_sync()` returns true for every non-trial, non-lifetime subscription product, and `CheckoutHelpersTrait::maybeHideUnsupportedRenewalSyncGateways()` then removes Paddle from checkout for all of them — making Paddle coverage impossible.

## Scope
- Gateway: both
- Checkout: both
- Account: N/A
- Plugins: both

## Preconditions
- SLT-SETUP-01 complete (pre-window `arraysubs_settings` dump exists).
- Code-verified basis for each change is stated in Steps; do not re-derive.

## Test data
| Item | Value |
|---|---|
| Product | N/A |
| Account | admin |
| Coupon | N/A |
| Card | N/A |
| Amounts | N/A |

## Steps
1. Record priors exactly (they are already known; confirm them): `renewals.sync_to_billing_cycle = true`, `renewals.sync_first_charge_mode = "full"`, `customer_actions.allow_early_renew = false`, `customer_actions.allow_reactivation = false`, `pause_subscription.enabled = false`, `pause_subscription.customer_can_pause = false`. Write these into this task's Notes and into `/home/server-manager/slt-evidence/SLT-SETUP-02-priors.txt`.
2. `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/settings/general"` -> `agent-browser --session admin snapshot -i`.
3. In the **Renewal Sync** card, switch **Sync Renewals to Next Billing Cycle** OFF. Do NOT touch the **First Charge** select (it hides when sync is off; its stored value `full` must remain untouched). Justification, code-verified: `arraysubs/src/functions/renewal-sync-helpers.php::arraysubs_is_renewal_sync_supported_gateway()` returns false for `arraysubs_paddle`, and `CheckoutHelpersTrait::maybeHideUnsupportedRenewalSyncGateways()` hides every unsupported gateway whenever the cart holds a sync-eligible item. Turning the global off also gives every non-flex SLT product deterministic anniversary renewals (checkout time + interval) instead of midnight-boundary renewals.
4. In the **Customer Actions** card, switch **Allow Early Renew** ON (pro EarlyRenew module is active; verified `arraysubs_has_module('EarlyRenew') === 1`).
5. In the same card, switch **Allow Reactivation** ON.
6. Save the General settings page and re-snapshot to confirm the save toast.
7. `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/settings/skip-pause"` -> `snapshot -i`.
8. Switch **Enable Pause Subscription** ON, then switch **Allow Customers to Pause** ON. Leave **Maximum Pause Duration (Days)** = 30, **Maximum Pauses per Subscription** = 2, **Cooldown Between Pauses (Days)** = 0, **Require Pause Reason** = on, all at their stored values. Save.
9. Verify from WP root: `wp option get arraysubs_settings --allow-root | grep -o 'sync_to_billing_cycle";b:[01]'` and equivalent greps for `allow_early_renew`, `allow_reactivation`, `pause_subscription` `enabled` / `customer_can_pause`.
10. Dump the post-change blob: `wp option get arraysubs_settings --format=json --allow-root > /home/server-manager/slt-evidence/SLT-SETUP-02-arraysubs_settings-baseline.json`.

## Expected results
1. `renewals.sync_to_billing_cycle` is `false`; `renewals.sync_first_charge_mode` is still the string `full`.
2. `customer_actions.allow_early_renew` is `true`.
3. `customer_actions.allow_reactivation` is `true`.
4. `pause_subscription.enabled` is `true` and `pause_subscription.customer_can_pause` is `true`.
5. Every other key in `arraysubs_settings` is byte-identical to the SLT-SETUP-01 D0 dump — specifically unchanged: `multiple_subscriptions.allow_multiple_in_cart=false`, `multiple_subscriptions.allow_mixed_cart=true`, `trials.require_payment_method=true`, `renewals.grace_days_before_on_hold=1`, `renewals.grace_days_before_cancel=3`, `renewals.invoice_before_due_value=6`/`unit=hours`, all `plan_switching.*`, all `refunds.*`, all `emails.*`.
6. With sync off, checkout for a plain subscription product now offers both Stripe and Paddle.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Settings save | — | — | Capture `mailpit-agent latest-id` before step 2; it must be unchanged after step 9 |

## Evidence to capture
- Screenshots: `SLT-SETUP-02-01-general-after.png`, `SLT-SETUP-02-02-skip-pause-after.png`.
- `SLT-SETUP-02-priors.txt` and `SLT-SETUP-02-arraysubs_settings-baseline.json`.
- The grep output proving each of the five booleans.

## Pass criteria
- [ ] sync_to_billing_cycle == false, sync_first_charge_mode still "full"
- [ ] allow_early_renew == true
- [ ] allow_reactivation == true
- [ ] pause_subscription.enabled == true and customer_can_pause == true
- [ ] No other setting differs from the D0 dump
- [ ] Priors recorded in Notes and on disk

## Isolation / teardown
- State handoff: these four changes are the WINDOW-WIDE BASELINE. Every other SLT task treats them as fixed and must NOT flip them. A task needing global sync ON (none is planned) must flip it and restore inside the same task.
- Consequence other authors must assume: non-flex SLT products bill on **anniversary** schedule (checkout timestamp + interval), NOT at site-local midnight. Flex-sync products still sync, because `ArraySubsPro\Features\FlexibleRenewalSync\Services\Hooks::filterSupportsRenewalSync()` grants support per-product regardless of the global switch — which is exactly what SLT-PROD-12/13/14/15 prove.
- Restores: SLT-SETUP-99 sets all four (five booleans) back to the recorded priors on day 10.


## SLT-SETUP-03 Create the SLT account matrix (7 slt-* users) and document the guest path

*day D00 · priority critical · estimate 1h*

## Objective
Provision every customer identity the window needs, one purpose per account, so no test ever reuses another test's customer and no pre-existing site user is mutated. Also nail down what "guest checkout" actually means on this install: `woocommerce_enable_guest_checkout=no`, so an anonymous purchase is impossible — the guest path is "not logged in, account auto-created at checkout" (`checkout.auto_create_account=true`, `woocommerce_enable_signup_and_login_from_checkout=yes`).

## Scope
- Gateway: N/A
- Checkout: N/A
- Account: new registered
- Plugins: both

## Preconditions
- SLT-SETUP-01 complete.
- Verified: no user matching `slt` exists on the site today.
- Existing users `cust1` (id 5), `customer1` (id 32), `sync-stripe` (id 319) are DOCUMENTED BUT OFF-LIMITS for mutation. Only SLT-CHK-style read-only checks may reference them; no SLT task may place an order, cancel a subscription, or edit profile data on them.

## Test data
| Item | Value |
|---|---|
| Product | N/A |
| Account | see matrix below |
| Coupon | N/A |
| Card | N/A |
| Amounts | N/A |

Account matrix (role `customer`, password for all: `SltQa!2026#Pass`):

| Key | Username | Email | Purpose |
|---|---|---|---|
| A1 | slt-core | slt-core@example.test | Pre-existing registered buyer for the daily workhorse and most Stripe happy paths |
| A2 | slt-trial | slt-trial@example.test | Trial + free-signup products only |
| A3 | slt-switch | slt-switch@example.test | Plan-switching ladder only (upgrade/downgrade/crossgrade) |
| A4 | slt-flex | slt-flex@example.test | Flexible-renewal-sync products only |
| A5 | slt-fail | slt-fail@example.test | Failing-card / retry / dunning only |
| A6 | slt-paddle | slt-paddle@example.test | Paddle sandbox only — never used with Stripe |
| A7 | slt-admincreated | slt-admincreated@example.test | Target of an admin-created subscription; never checks out |
| A8 | (none — created at checkout) | slt-guest-d0@example.test | The "guest -> new" path; the account is born at checkout, so it is NOT created here |

## Steps
1. `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin/user-new.php"` -> `agent-browser --session admin snapshot -i`.
2. For each of A1..A7 in the table: fill **Username**, **Email**, **First Name** = `SLT`, **Last Name** = the purpose word (e.g. `Core`), untick **Send User Notification**, set **Password** to `SltQa!2026#Pass` via the *Set New Password* button, set **Role** = `Customer`, click **Add New User**, then re-snapshot and re-open `user-new.php` for the next one.
3. Capture `mailpit-agent latest-id` BEFORE the first Add New User click — with *Send User Notification* unticked no mail may be sent, and this is the negative check.
4. For A1..A7 set a billing address so block checkout does not stall: `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin/user-edit.php?user_id=<ID>"`, scroll to **Customer billing address**, set First name `SLT`, Last name `<Purpose>`, Address line 1 `1 SLT Way`, City `Dhaka`, Country/Region `Bangladesh`, Postcode `1207`, Phone `+8801700000000`, Email = the account email. Update User.
5. Do NOT create A8. Record in the registry that `slt-guest-d0@example.test` is reserved and must not be pre-created — the checkout task that uses it proves auto-account-creation.
6. Reserve a second guest email `slt-guest-d5@example.test` in the registry for a later-window guest run.
7. Verify: `wp user list --format=csv --fields=ID,user_login,user_email,roles --allow-root | grep slt-`.
8. Append all seven user IDs to the `slt-catalog-registry` page.

## Expected results
1. Exactly 7 users exist with logins `slt-core`, `slt-trial`, `slt-switch`, `slt-flex`, `slt-fail`, `slt-paddle`, `slt-admincreated`, all with role `customer`.
2. Each has the billing address block populated (country BD, postcode 1207).
3. No user named `slt-guest-d0` or `slt-guest-d5` exists.
4. `cust1` (5), `customer1` (32), `sync-stripe` (319) are untouched — their `user_registered` and email are unchanged.
5. The registry page lists all 7 IDs plus the two reserved guest emails.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Each **Add New User** | — | — | `mailpit-agent latest-id` after step 7 must equal the id captured in step 3; if it moved, open it with `mailpit-agent show latest` and record which account leaked a notification |

## Evidence to capture
- Screenshot `SLT-SETUP-03-01-users-list-slt.png` of `edit.php` filtered to the slt users.
- The `wp user list | grep slt-` output.
- Seven WP user IDs, recorded in the registry.

## Pass criteria
- [ ] 7 slt-* customers exist with correct emails and role
- [ ] Billing address populated on all 7
- [ ] No guest account pre-created; both guest emails reserved in the registry
- [ ] Zero mail sent (latest-id unchanged)
- [ ] Existing users 5 / 32 / 319 untouched

## Isolation / teardown
- State handoff: the account matrix. Later tasks MUST use the account whose purpose matches; crossing purposes (e.g. buying a flex product as `slt-core`) invalidates the isolation guarantee because subscription-per-customer state leaks between tests.
- Restores: nothing changed globally. SLT-SETUP-99 deletes all `slt-*` users (including any created at checkout) and reassigns their content to nobody.


## SLT-SETUP-04 Create the six SLT coupons covering recurring, one-time, N-cycle, fee and reject paths

*day D00 · priority high · estimate 1h*

## Objective
Build the coupon matrix against the real `ArraySubs\Features\CouponTracking\Services\Hooks` contract: the plugin adds exactly four coupon metas — `_arraysubs_apply_to_subscriptions`, `_arraysubs_discount_duration` (`one-time`|`recurring`), `_arraysubs_discount_cycles`, `_arraysubs_count_initial_checkout` — and rejects any coupon lacking the first one on a subscription-only cart.

## Scope
- Gateway: N/A
- Checkout: N/A
- Account: N/A
- Plugins: free-only

## Preconditions
- SLT-SETUP-01 complete.
- Existing coupons `qa-audit-coupon, save20, NONSUB5, RENEW20FOR3, SUB10ONCE, halfoff3, nosub10, welcome15` are OFF-LIMITS — do not open, edit, or apply them.
- Code facts to rely on (verified, do not re-derive): `validateSubscriptionCouponEligibility()` returns false — i.e. the coupon is rejected outright — when the cart contains at least one subscription item and NO regular item and the coupon has `_arraysubs_apply_to_subscriptions != 'yes'`. On a MIXED cart the same coupon stays valid but `filterSubscriptionCouponItems()` strips the subscription lines from the discount. The signup fee is a **cart fee** added by `SubscriptionProducts\Services\Hooks::addSignupFeeToCart()` named `Subscription Signup Fee`; WooCommerce coupons never discount fees, so a "signup-fee-only coupon" is NOT a supported concept — `SLTFEEPROBE` exists to prove that.

## Test data
| Item | Value |
|---|---|
| Product | N/A (coupons only) |
| Account | admin |
| Coupon | the six below |
| Card | N/A |
| Amounts | see table |

| Code | WC discount type | Amount | Apply to subscriptions | Discount duration | Renewal cycles | Count initial checkout | Proves |
|---|---|---|---|---|---|---|---|
| SLTPCT20REC | Percentage discount | 20 | yes | Recurring | 0 | no | Percent off forever, initial + unlimited renewals |
| SLTFIX5FIRST | Fixed cart discount | 5.00 | yes | One-time (initial order only) | 0 | no | Fixed off first payment only, renewals full price |
| SLTREC3 | Percentage discount | 25 | yes | Recurring | 3 | yes | N-cycle counting WITH the checkout consuming a cycle -> 2 discounted renewals |
| SLTREC3NOINIT | Percentage discount | 25 | yes | Recurring | 3 | no | N-cycle counting WITHOUT the checkout consuming a cycle -> 3 discounted renewals |
| SLTFEEPROBE | Fixed cart discount | 10.00 | yes | One-time (initial order only) | 0 | no | Signup fee is a fee, not a discountable line — negative control |
| SLTNOSUB | Percentage discount | 30 | **no** | One-time (initial order only) | 0 | no | Must be REJECTED on a subscription-only cart; applies only to the regular line on a mixed cart |

## Steps
1. Capture `mailpit-agent latest-id` before starting.
2. `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin/post-new.php?post_type=shop_coupon"` -> `agent-browser --session admin snapshot -i`.
3. For each row: type the code into the **Coupon code** title field; set **Description** to `SLT window coupon — delete on 2026-08-11`.
4. On the **General** tab set **Discount type** and **Coupon amount** per the table. Leave **Allow free shipping** off. Set **Coupon expiry date** = `2026-08-12` for all six (past the watch window so nothing expires mid-test, but they self-limit).
5. On the **Usage restriction** tab leave everything empty except **Minimum spend** blank; do NOT set product/category restrictions — later tasks rely on these coupons being cart-wide.
6. On the **Usage limits** tab set **Usage limit per coupon** = blank (unlimited) and **Usage limit per user** = blank.
7. Scroll to the **ArraySubs Subscription Settings** group (rendered by `CouponTracking` under the coupon data panel) and set: **Apply to subscriptions** checkbox, **Discount duration** select, **Number of renewal cycles** number field, **Count initial checkout** checkbox — exactly as the table says. For SLTNOSUB leave **Apply to subscriptions** UNCHECKED.
8. Publish. Re-snapshot and confirm the four ArraySubs fields persisted after the reload (they are saved on `woocommerce_coupon_options_save`).
9. Repeat for all six coupons.
10. Verify the metas from WP root for each coupon id: `wp post meta list <ID> --keys=_arraysubs_apply_to_subscriptions,_arraysubs_discount_duration,_arraysubs_discount_cycles,_arraysubs_count_initial_checkout --allow-root`.
11. Append the six coupon IDs to `slt-catalog-registry`.

## Expected results
1. Six coupons exist with exactly the codes above and status `publish`.
2. `SLTPCT20REC`: `_arraysubs_apply_to_subscriptions=yes`, `_arraysubs_discount_duration=recurring`, `_arraysubs_discount_cycles=0`, `_arraysubs_count_initial_checkout=` (empty).
3. `SLTFIX5FIRST`: apply=yes, duration=one-time, cycles=0, count_initial empty.
4. `SLTREC3`: apply=yes, duration=recurring, cycles=3, count_initial=yes.
5. `SLTREC3NOINIT`: apply=yes, duration=recurring, cycles=3, count_initial empty.
6. `SLTFEEPROBE`: apply=yes, duration=one-time, cycles=0, count_initial empty.
7. `SLTNOSUB`: `_arraysubs_apply_to_subscriptions` is empty string (NOT `yes`).
8. All eight pre-existing coupons are untouched (`post_modified` unchanged).

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Coupon publish | — | — | `mailpit-agent latest-id` unchanged from step 1 |

## Evidence to capture
- Screenshot per coupon of the **ArraySubs Subscription Settings** group after reload: `SLT-SETUP-04-0N-<code>.png`.
- `wp post meta list` output for each of the six.
- Six coupon IDs in the registry.

## Pass criteria
- [ ] All six coupons published with the exact codes
- [ ] All four ArraySubs metas correct on each coupon and surviving a reload
- [ ] SLTNOSUB has apply-to-subscriptions unset
- [ ] Pre-existing coupons untouched
- [ ] Zero mail sent

## Isolation / teardown
- State handoff: `SLTPCT20REC` for recurring-discount renewal tests; `SLTFIX5FIRST` for first-payment-only; `SLTREC3` / `SLTREC3NOINIT` for the cycle-counting pair (they must be used on two DIFFERENT subscriptions — the plugin stores only one captured coupon per subscription via `_applied_coupon_id`); `SLTFEEPROBE` only with SLT Signup Fee Daily; `SLTNOSUB` for the rejection path (subscription-only cart) and the mixed-cart partial path.
- Restores: nothing global. SLT-SETUP-99 trashes and permanently deletes all six.


## SLT-SETUP-05 Verify Paddle sandbox readiness and record the two-gateway capability matrix

*day D00 · priority high · estimate 1h*

## Objective
Prove that, with global renewal sync now off, Paddle is actually selectable at checkout, that `PaddleProductSync` pushed the SLT Paddle product into the Paddle sandbox catalogue, and publish the capability matrix that tells every later author which gateway can legitimately be used for which behaviour.

## Scope
- Gateway: both
- Checkout: block
- Account: N/A
- Plugins: pro-required

## Preconditions
- SLT-SETUP-02 complete (global sync OFF — without it Paddle is hidden for every sync-eligible subscription product).
- SLT-PROD-16 complete (`SLT Paddle Daily` and `SLT Retry Daily` exist).
- Verified gateway config: Stripe enabled, `testmode: yes`, UPE/accordion, saved cards on; Paddle id `arraysubs_paddle`, enabled, `test_mode: yes`, sandbox api_key/client_token/seller_id/webhook_secret set.

## Test data
| Item | Value |
|---|---|
| Product | SLT Paddle Daily ($11.00, day/1), SLT Daily Core ($10.00, day/1) |
| Account | N/A (guest browse only, no order placed) |
| Coupon | N/A |
| Card | N/A — do NOT complete a purchase in this task |
| Amounts | none charged |

## Steps
1. Re-save `SLT Paddle Daily` once to trigger the sync: `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin/post.php?post=<SLT Paddle Daily ID>&action=edit"` -> click **Update** -> re-snapshot.
2. Verify the sync metas landed: `wp post meta list <ID> --keys=_arraysubs_gateway_paddle_product_id,_arraysubs_gateway_paddle_price_id,_arraysubs_gateway_paddle_synced_at --allow-root`.
3. If the price id is empty, check the log source: `wp option get woocommerce_arraysubs_paddle_settings --allow-root` and the WooCommerce log source `arraysubs_paddle_sync` at `/wp-admin/admin.php?page=wc-status&tab=logs`. Record the failure rather than fixing gateway credentials.
4. Add `SLT Daily Core` to a clean guest cart via the helper link and open block checkout: `agent-browser --session guest open "https://mirror-help.arrayhash.com/checkout/?add-to-cart=<SLT Daily Core ID>"` -> `agent-browser --session guest snapshot -i`.
5. Read the payment-method accordion from the snapshot and record which gateways are offered. This is the direct proof of the SLT-SETUP-02 rationale.
6. Empty the cart, then repeat step 4 with `SLT Paddle Daily`.
7. Empty the cart, then repeat with `SLT Flex Daily Next Cycle` (from SLT-PROD-14) — this one IS sync-eligible via the per-product pro override, so Paddle must be hidden here.
8. Do not place any order. `agent-browser --session guest open "https://mirror-help.arrayhash.com/cart/"` and remove all items; close with `agent-browser close --all`.
9. Append the capability matrix (Expected results 5) to `slt-catalog-registry`.

## Expected results
1. `SLT Paddle Daily` has non-empty `_arraysubs_gateway_paddle_product_id` (`pro_...`) and `_arraysubs_gateway_paddle_price_id` (`pri_...`) plus a `_arraysubs_gateway_paddle_synced_at` timestamp.
2. Block checkout with `SLT Daily Core` offers BOTH `Stripe` and `Paddle` (proves the baseline change achieved its purpose).
3. Block checkout with `SLT Paddle Daily` offers both gateways.
4. Block checkout with `SLT Flex Daily Next Cycle` offers Stripe but NOT Paddle — `arraysubs_is_renewal_sync_supported_gateway('arraysubs_paddle')` is hard-coded false and `maybeHideUnsupportedRenewalSyncGateways()` removes it while a sync-eligible item is in the cart.
5. The recorded matrix reads: Stripe — automatic payments yes, SCA yes, renewal sync yes, early renew yes, trials yes, recurring coupon discounts yes. Paddle — automatic payments yes, `sca: false`, renewal sync NO (gateway hidden on sync carts), `early_renewal: false` (Paddle owns `next_billed_at`, so the Renew Early button stays hidden even with the baseline toggle on), trials yes, `different_billing_cycles: false` (never put two different-cycle Paddle subscriptions in one cart), `retention_amount_update: false`.
6. Cart is empty at the end; no order, no subscription, no customer created.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Whole task (no order placed) | — | — | Capture `mailpit-agent latest-id` at step 1; it must be unchanged at step 8 |

## Evidence to capture
- Screenshots: `SLT-SETUP-05-01-paddle-sync-meta.png`, `SLT-SETUP-05-02-checkout-daily-core-gateways.png`, `SLT-SETUP-05-03-checkout-paddle-daily-gateways.png`, `SLT-SETUP-05-04-checkout-flex-nextcycle-gateways.png`.
- `wp post meta list` output for the three Paddle metas.
- Any `arraysubs_paddle_sync` log lines; console/network errors from the Paddle overlay script.

## Pass criteria
- [ ] Paddle product id and price id present on SLT Paddle Daily
- [ ] Stripe AND Paddle both offered for SLT Daily Core
- [ ] Paddle hidden for SLT Flex Daily Next Cycle
- [ ] Capability matrix recorded in the registry
- [ ] No order/subscription/customer created; zero mail

## Isolation / teardown
- State handoff: the capability matrix is binding. No SLT task may schedule Paddle for early renew, SCA, flexible-renewal-sync, or a mixed-cycle multi-subscription cart; those combinations are gateway-unsupported by design and must be filed as expected negatives, not bugs.
- Restores: cart emptied; nothing else changed. Paddle catalogue objects created in the sandbox are left in place (sandbox-only, no cleanup path from WP).


## SLT-SETUP-99 Restore baseline settings and delete every SLT artifact

*day D10 · priority critical · estimate 2h*

## Objective
Return the shared staging site to its 2026-08-01 state: restore the five baseline booleans to their recorded priors, cancel every SLT subscription and its scheduled actions, and permanently delete every SLT-prefixed product, coupon, page, user, order and subscription — while proving that no pre-existing artifact was touched.

## Scope
- Gateway: both
- Checkout: N/A
- Account: N/A
- Plugins: both

## Preconditions
- Every SLT execution and watch task is finished. Run this only on day 10 (2026-08-11) or later; the renewal watch runs to D12, so if watch tasks are still open, restore the settings (Part 1) and defer the deletions (Parts 2-4) until the watch closes — settings restoration is safe to do first because it only affects NEW subscriptions.
- `/home/server-manager/slt-evidence/SLT-SETUP-02-priors.txt` and `SLT-SETUP-01-arraysubs_settings-D0.json` exist.
- The `slt-catalog-registry` page holds every created ID.

## Test data
| Item | Value |
|---|---|
| Product | every product whose title starts `SLT ` |
| Account | every user whose login starts `slt-` (including checkout-created guests `slt-guest-d0@example.test`, `slt-guest-d5@example.test`) |
| Coupon | SLTPCT20REC, SLTFIX5FIRST, SLTREC3, SLTREC3NOINIT, SLTFEEPROBE, SLTNOSUB |
| Card | N/A |
| Amounts | N/A |

## Steps
**Part 1 — restore the window-wide baseline (do this first).**
1. `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/settings/general"` -> `snapshot -i`.
2. Switch **Sync Renewals to Next Billing Cycle** back ON. Confirm the **First Charge** select reappears reading `Charge the full recurring amount` (stored value `full`); do not change it.
3. Switch **Allow Early Renew** back OFF and **Allow Reactivation** back OFF. Save.
4. `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/settings/skip-pause"`; switch **Allow Customers to Pause** OFF, then **Enable Pause Subscription** OFF. Save.
5. Diff against D0: `wp option get arraysubs_settings --format=json --allow-root > /home/server-manager/slt-evidence/SLT-SETUP-99-arraysubs_settings-restored.json` then `diff <(jq -S . /home/server-manager/slt-evidence/SLT-SETUP-01-arraysubs_settings-D0.json) <(jq -S . /home/server-manager/slt-evidence/SLT-SETUP-99-arraysubs_settings-restored.json)`.

**Part 2 — wind down SLT subscriptions and their scheduled actions.**
6. `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin/edit.php?post_type=arraysubs_data"`; filter/search for the SLT customers; for each SLT subscription set the status to Cancelled from the admin edit screen. Cancel before deleting so gateway-side subscriptions (Stripe, Paddle) are closed rather than orphaned.
7. In wp-admin -> Tools -> Scheduled Actions, search each SLT subscription id and cancel any remaining pending `arraysubs_generate_renewal_invoice`, `arraysubs_process_renewal`, `arraysubs_hold_subscription`, `arraysubs_cancel_subscription`, `arraysubs_expire_subscription`, `arraysubs_send_renewal_reminder`, `arraysubs_send_expiring_soon`, `arraysubs_process_trial_conversion` action for it. Screenshot the empty result set afterwards.
8. Delete the SLT subscription posts (Trash then Empty Trash) using the ids from the registry ONLY. Never bulk-delete by status.

**Part 3 — delete SLT orders, products, coupons, pages, users.**
9. Orders: `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin/admin.php?page=wc-orders"`, filter by each SLT customer, move their orders to trash, then permanently delete. Do not touch any order belonging to a non-SLT customer.
10. Products: `https://mirror-help.arrayhash.com/wp-admin/edit.php?post_type=product&s=SLT` — verify every result is SLT-prefixed, then Move to Trash and Empty Trash. Include the Subscription Box, its three children, the grouped parent and `SLT Grouped Extra`, both variable parents (their variations delete with them) and all four plan rungs.
11. Coupons: `https://mirror-help.arrayhash.com/wp-admin/edit.php?post_type=shop_coupon&s=SLT` — trash and permanently delete the six SLT coupons only. The eight pre-existing coupons must remain.
12. Pages: delete `SLT Classic Checkout`, `SLT Classic Cart`, and `SLT Catalog Registry` (export the registry contents to `/home/server-manager/slt-evidence/SLT-SETUP-99-registry-final.md` first).
13. Users: `https://mirror-help.arrayhash.com/wp-admin/users.php?s=slt-` — delete every `slt-*` user, choosing **Delete all content** (their orders are already gone). Confirm `cust1` (5), `customer1` (32) and `sync-stripe` (319) are NOT in the selection.

**Part 4 — prove the site is back to baseline.**
14. `wp post list --post_type=product --format=count --allow-root` (expect 64), `wp post list --post_type=shop_order --format=count --allow-root` / the HPOS order count (expect 437), `wp post list --post_type=arraysubs_data --format=count --allow-root` (expect 354), `wp post list --post_type=shop_coupon --format=count --allow-root` (expect 8).
15. `wp user list --allow-root | grep -c slt-` (expect 0) and `wp post list --post_type=product --allow-root | grep -c SLT` (expect 0).
16. `mailpit-agent latest-id` — record the final id for the record; do NOT purge Mailpit, the captured messages are the window's evidence.

## Expected results
1. `renewals.sync_to_billing_cycle=true`, `renewals.sync_first_charge_mode="full"`, `customer_actions.allow_early_renew=false`, `customer_actions.allow_reactivation=false`, `pause_subscription.enabled=false`, `pause_subscription.customer_can_pause=false`.
2. The jq diff between the D0 dump and the restored dump is EMPTY. Any residual difference is reported verbatim, not silently accepted.
3. Zero pending Action Scheduler entries reference any SLT subscription id.
4. Product count is back to 64, subscription count 354, coupon count 8, order count 437.
5. No user matching `slt-` exists; users 5, 32 and 319 still exist with unchanged emails.
6. `SLT Classic Checkout`, `SLT Classic Cart` and `SLT Catalog Registry` pages no longer exist, and the registry has been exported to disk first.
7. Mailpit still holds the window's messages.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | Subscription cancelled | Each SLT subscription cancelled in step 6 | the SLT customer + admin | `has been cancelled` / `cancelled by` | `mailpit-agent list 50` — these are expected side effects of teardown, count them and record the ids |
| 2 | NONE EXPECTED beyond #1 | Product/coupon/page/user deletion | — | — | No new message ids after the last cancellation; verify with `mailpit-agent latest-id` before and after step 9 |

## Evidence to capture
- Screenshots: `SLT-SETUP-99-01-general-restored.png`, `SLT-SETUP-99-02-skip-pause-restored.png`, `SLT-SETUP-99-03-scheduled-actions-empty.png`, `SLT-SETUP-99-04-product-search-empty.png`, `SLT-SETUP-99-05-users-empty.png`.
- `SLT-SETUP-99-arraysubs_settings-restored.json`, the jq diff output (expected empty), `SLT-SETUP-99-registry-final.md`, all four count outputs.

## Pass criteria
- [ ] All five baseline booleans restored to the recorded priors
- [ ] jq diff against the D0 settings dump is empty
- [ ] All SLT subscriptions cancelled before deletion and no pending actions remain
- [ ] Counts back to 64 products / 354 subscriptions / 437 orders / 8 coupons
- [ ] Zero slt-* users; users 5, 32, 319 intact
- [ ] Registry exported to disk before its page was deleted
- [ ] Mailpit evidence preserved (not purged)

## Isolation / teardown
- This task leaves behind only `/home/server-manager/slt-evidence/` (screenshots, settings dumps, the exported registry) and the Mailpit message history. Both are intentionally kept as the window's audit trail.
- If any deletion cannot be completed (e.g. a Stripe or Paddle subscription refuses to cancel), STOP and record the blocker with ids rather than force-deleting the local post — an orphaned local record is recoverable, an orphaned live gateway subscription that keeps charging is not.


---

# Product catalog


## SLT-PROD-01 Create SLT Daily Core, the day/1 workhorse subscription product

*day D00 · priority critical · estimate 30m*

## Objective
Create the plainest possible recurring product — day period, interval 1, no trial, no signup fee, no length limit, no flexible sync — so that renewals genuinely fire on their own once per day for the whole window and every other test has a known-good control to compare against.

## Scope
- Gateway: both
- Checkout: both
- Account: N/A (creation only)
- Plugins: free-only

## Preconditions
- SLT-SETUP-01 complete (conventions, evidence root, registry page).
- Billing period `day` with interval 1 is chosen deliberately: the window is 10 real days, and `arraysubs_calculate_next_payment_from_date()` gives `start + 1 day`, so this product produces up to 9 unattended renewals inside D0..D9 with zero time-travel.

## Test data
| Item | Value |
|---|---|
| Product | SLT Daily Core / slug `slt-daily-core` |
| Account | N/A |
| Coupon | N/A |
| Card | N/A |
| Amounts | Regular price $10.00; expected first charge $10.00; expected renewal $10.00/day |

## Steps
1. Capture `mailpit-agent latest-id` before any admin save.
2. `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin/post-new.php?post_type=product"` -> `agent-browser --session admin snapshot -i`.
3. **Product title**: `SLT Daily Core`.
4. **Description**: `SLT window product. Daily recurring workhorse. Delete on 2026-08-11.`
5. In the **Product data** panel keep the type dropdown on **Simple product**; tick **Virtual**; leave **Downloadable** unticked.
6. Tick the header checkbox **Subscription [ArraySubs]** (this writes `_is_subscription=yes`; it renders next to Virtual/Downloadable and is only offered for simple and variable types).
7. **General** tab: **Regular price ($)** = `10.00`. Leave **Sale price** empty. Note: `SubscriptionProducts\Services\Hooks::getPostedSubscriptionProductValidationErrors()` blocks the save with "Subscription products must have a valid regular price greater than zero" if this is 0 or empty.
8. Open the **Subscription [ArraySubs]** tab and set: **Billing Period** = `Day`; **Billing Interval** = `1`; **Subscription Length** = `0` (never expires); **Trial Length** = `0`; **Trial Period** = `Day`; **Sign-up Fee ($)** = empty; **Different Renewal Price** = UNTICKED.
9. Confirm the **Flexible Renewal Sync to Next Billing Cycle** checkbox is visible but leave it UNTICKED — a 1-day nominal cycle is below `SegmentPlan::MIN_CYCLE_DAYS = 3`, so even if ticked `SegmentPlan::getConfig()` would return null. Screenshot this state.
10. **Inventory** tab: leave **Manage stock?** unticked, **Stock status** = In stock.
11. Set the URL slug to `slt-daily-core` in the sidebar Permalink field. Publish.
12. Reload the edit screen and confirm every subscription field survived the save.
13. Verify meta: `wp post meta list <ID> --keys=_is_subscription,_subscription_period,_subscription_interval,_subscription_length,_trial_length,_signup_fee,_enable_renewal_price,_regular_price --allow-root`.
14. Open the storefront page `https://mirror-help.arrayhash.com/?p=<ID>` as `--session guest` and confirm the subscription price/schedule summary renders under the price.
15. Append the product ID to `slt-catalog-registry`.

## Expected results
1. Product published, type `simple`, virtual, slug exactly `slt-daily-core`.
2. `_is_subscription=yes`, `_subscription_period=day`, `_subscription_interval=1`, `_subscription_length=0`, `_trial_length=0`, `_signup_fee` absent or `0`, `_enable_renewal_price` absent, `_regular_price=10.00`.
3. `_arraysubs_flex_sync_enabled` is absent.
4. The single-product page shows the recurring schedule text "every day" (rendered by `displaySubscriptionInfo()` at `woocommerce_single_product_summary` priority 11) and the add-to-cart button uses the subscription button text.
5. No admin error notice from `WC_Admin_Meta_Boxes` on save; the post status is `publish`, not silently held back by `preserveProductStatusForInvalidSubscriptionSave()`.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Product publish | — | — | `mailpit-agent latest-id` after step 13 equals the id from step 1 |

## Evidence to capture
- Screenshots: `SLT-PROD-01-01-general-tab.png`, `SLT-PROD-01-02-subscription-tab.png`, `SLT-PROD-01-03-frontend.png`.
- Product ID; `wp post meta list` output; any admin notice text; console errors on the product page.

## Pass criteria
- [ ] Published as simple + virtual + subscription with slug slt-daily-core
- [ ] All eight metas exactly as listed
- [ ] Flex sync meta absent
- [ ] Front end renders the daily recurring summary
- [ ] Zero mail, zero admin errors

## Isolation / teardown
- State handoff: this is THE control product. Use it for the guest->new checkout path, the block-vs-classic comparison, the Stripe SCA card path, the cancellation/reactivation flow, and as the non-flex baseline in gateway comparisons. Buy it with `slt-core` (or a guest email) only.
- Restores: nothing. Deleted by SLT-SETUP-99.


## SLT-PROD-02 Create SLT Free Signup Daily, the $0.00-today free-signup-then-paid product

*day D00 · priority high · estimate 40m*

## Objective
Provide the "signup free" product. A true $0 recurring SIMPLE subscription is impossible on this build — `getPostedSubscriptionProductValidationErrors()` rejects any simple subscription save whose regular price is empty or <= 0, and `validateProduct()` additionally restores the old prices — so free signup is implemented the supported way: a priced product with a short trial, which `beforeCalculateTotals()` zeroes at checkout, giving $0.00 due today and a first real charge when the trial converts. The $0-recurring branch is probed separately on a VARIATION in SLT-PROD-08, where no such validation runs.

## Scope
- Gateway: both
- Checkout: both
- Account: N/A (creation only)
- Plugins: free-only

## Preconditions
- SLT-SETUP-01 complete.
- Baseline `trials.require_payment_method = true` is NOT changed by this window, so checkout still requires a card even though the total is $0.00 (`TrialCheckoutTrait::maybeSkipPaymentForTrial()` returns the setting value).
- Trial length 2 days is deliberate: `emails.trial_ending.days_before = 3` is LONGER than the trial, so the trial-ending reminder has no valid send window — that negative is the point of this product and is contrasted with SLT-PROD-03.

## Test data
| Item | Value |
|---|---|
| Product | SLT Free Signup Daily / slug `slt-free-signup-daily` |
| Account | N/A |
| Coupon | N/A |
| Card | N/A |
| Amounts | Regular price $8.00; expected charge today $0.00; expected first paid charge $8.00 at trial end; renewal $8.00/day |

## Steps
1. Capture `mailpit-agent latest-id`.
2. `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin/post-new.php?post_type=product"` -> `snapshot -i`.
3. **Product title**: `SLT Free Signup Daily`. **Description**: `SLT window product. Free signup via 2-day trial, then paid. Delete on 2026-08-11.`
4. Type **Simple product**; tick **Virtual**.
5. Tick **Subscription [ArraySubs]**.
6. **General** tab: **Regular price ($)** = `8.00`, Sale price empty.
7. **Subscription [ArraySubs]** tab: **Billing Period** = `Day`; **Billing Interval** = `1`; **Subscription Length** = `0`; **Trial Length** = `2`; **Trial Period** = `Day`; **Sign-up Fee ($)** = EMPTY (a signup fee would force payment and break the $0.00-today premise — `maybeSkipPaymentForTrial()` returns true unconditionally when `cartHasSignupFee()`); **Different Renewal Price** = unticked.
8. Confirm the **Flexible Renewal Sync to Next Billing Cycle** section is HIDDEN/disabled because a trial is configured (`$arraysubs_flex_section_hidden = ... || $arraysubs_flex_trial_length > 0`). Screenshot it as the trial-exclusivity negative.
9. Slug `slt-free-signup-daily`. Publish. Reload and re-verify the fields.
10. Verify meta: `wp post meta list <ID> --keys=_is_subscription,_subscription_period,_subscription_interval,_trial_length,_trial_period,_signup_fee,_regular_price,_arraysubs_flex_sync_enabled --allow-root`.
11. As `--session guest`, open the product page and then `https://mirror-help.arrayhash.com/cart/?add-to-cart=<ID>`; read the cart totals from the snapshot WITHOUT proceeding to payment, then empty the cart.
12. Append the ID to the registry.

## Expected results
1. Published simple + virtual + subscription, slug `slt-free-signup-daily`.
2. `_trial_length=2`, `_trial_period=day`, `_subscription_period=day`, `_subscription_interval=1`, `_regular_price=8.00`, `_signup_fee` absent or `0`.
3. `_arraysubs_flex_sync_enabled` absent AND the flex section is not offered in the UI while the trial is set.
4. In the guest cart the line total for this item is `$0.00` and the cart total is `$0.00`; the item shows the trial/first-payment summary produced by `getSubscriptionTodayChargeSummary()`.
5. No `Subscription Signup Fee` fee row appears in the cart.
6. Cart emptied at the end; no order created.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Product publish and cart preview | — | — | `mailpit-agent latest-id` unchanged from step 1 |

## Evidence to capture
- Screenshots: `SLT-PROD-02-01-subscription-tab.png`, `SLT-PROD-02-02-flex-hidden-by-trial.png`, `SLT-PROD-02-03-cart-zero-total.png`.
- Product ID; meta list output; the exact cart total string.

## Pass criteria
- [ ] Published with trial 2 day and price 8.00
- [ ] Flex sync section hidden by the trial (exclusivity negative captured)
- [ ] Guest cart total is exactly $0.00 with no signup-fee row
- [ ] Metas exactly as listed
- [ ] Zero mail, cart left empty

## Isolation / teardown
- State handoff: buy this ONLY as `slt-trial`. It is the free-signup / $0-due-today path and also the negative case for the trial-ending reminder (2-day trial vs 3-day reminder lead time). Its trial converts on 2026-08-03 (start + 2 days), inside the window, so the trial-converted email and first paid charge are observable without time travel.
- Restores: cart emptied. Product deleted by SLT-SETUP-99.


## SLT-PROD-03 Create SLT Trial Four Day, the trial product with a live trial-ending reminder

*day D00 · priority high · estimate 30m*

## Objective
Provide the trial product whose trial is long enough for the trial-ending reminder to actually fire inside the window. With `emails.trial_ending.days_before = 3`, a 4-day trial started on D0 puts the reminder on D1 (2026-08-02) and the conversion on D4 (2026-08-05) — both observable without touching the clock. It also carries `trials.require_payment_method = true`, so the card must still be collected on a $0.00 order.

## Scope
- Gateway: Stripe test
- Checkout: both
- Account: N/A (creation only)
- Plugins: free-only

## Preconditions
- SLT-SETUP-01 complete.
- Deliberate deviation from "e.g. 2 days": 4 days is the shortest trial that leaves a valid 3-days-before reminder window. SLT-PROD-02 keeps the 2-day case as the suppressed-reminder negative.
- Baseline `trials.require_payment_method=true` and `trials.one_trial_per_customer=false` are unchanged.

## Test data
| Item | Value |
|---|---|
| Product | SLT Trial Four Day / slug `slt-trial-four-day` |
| Account | N/A |
| Coupon | N/A |
| Card | N/A |
| Amounts | Regular price $12.00; charge today $0.00; first paid charge $12.00 on 2026-08-05; renewal $12.00/day |

## Steps
1. Capture `mailpit-agent latest-id`.
2. `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin/post-new.php?post_type=product"` -> `snapshot -i`.
3. **Product title**: `SLT Trial Four Day`. **Description**: `SLT window product. 4-day free trial, card required. Delete on 2026-08-11.`
4. Type **Simple product**; tick **Virtual**; tick **Subscription [ArraySubs]**.
5. **General** tab: **Regular price ($)** = `12.00`.
6. **Subscription [ArraySubs]** tab: **Billing Period** = `Day`; **Billing Interval** = `1`; **Subscription Length** = `0`; **Trial Length** = `4`; **Trial Period** = `Day`; **Sign-up Fee ($)** empty; **Different Renewal Price** unticked.
7. Confirm again that the **Flexible Renewal Sync** section is hidden while a trial is set; screenshot.
8. Slug `slt-trial-four-day`. Publish. Reload and re-verify.
9. `wp post meta list <ID> --keys=_is_subscription,_subscription_period,_subscription_interval,_trial_length,_trial_period,_regular_price,_signup_fee --allow-root`.
10. As `--session guest`, open the product page and confirm the trial is advertised in the price/schedule summary. Do not add to cart.
11. Append the ID to the registry.

## Expected results
1. Published simple + virtual + subscription, slug `slt-trial-four-day`.
2. `_trial_length=4`, `_trial_period=day`, `_subscription_period=day`, `_subscription_interval=1`, `_regular_price=12.00`.
3. Flex sync section hidden; `_arraysubs_flex_sync_enabled` absent.
4. Product page shows the 4-day free trial in the subscription summary.
5. Date arithmetic to be used by the buying task: trial start = checkout timestamp; `_trial_end_date` = start + 4 days = 2026-08-05; trial-ending reminder due 3 days before = 2026-08-02 (D1); first paid renewal invoice generated 6 hours before the due time on 2026-08-05.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Product publish | — | — | `mailpit-agent latest-id` unchanged from step 1 |

## Evidence to capture
- Screenshots: `SLT-PROD-03-01-subscription-tab.png`, `SLT-PROD-03-02-flex-hidden-by-trial.png`, `SLT-PROD-03-03-frontend-trial-summary.png`.
- Product ID; meta list output.

## Pass criteria
- [ ] Published with trial 4 day and price 12.00
- [ ] Flex sync hidden by trial
- [ ] Front end advertises the trial
- [ ] Metas exactly as listed
- [ ] Zero mail

## Isolation / teardown
- State handoff: buy this ONLY as `slt-trial`, and only AFTER the `SLT Free Signup Daily` purchase has been captured, so the two trial subscriptions on that account are distinguishable by product. The subscription is expected to sit in status `arraysubs-trial` until 2026-08-05, then become `arraysubs-active`. Emails downstream tasks must look for: `Your free trial for SLT Trial Four Day has started`, `Your trial for SLT Trial Four Day ends soon`, `Your trial for SLT Trial Four Day has converted to a paid subscription`.
- Restores: nothing. Deleted by SLT-SETUP-99.


## SLT-PROD-04 Create SLT Signup Fee Daily with a $15.00 one-time signup fee

*day D00 · priority high · estimate 30m*

## Objective
Provide the signup-fee product and pin down exactly how the fee behaves: `addSignupFeeToCart()` adds a taxable cart fee literally named `Subscription Signup Fee`, once per subscription line and NOT multiplied by quantity, and it is skipped entirely on renewal orders (`did_action('arraysubs_creating_renewal_order')`). That makes it the anchor for the fee-vs-coupon negative and for the quantity-independence check.

## Scope
- Gateway: both
- Checkout: both
- Account: N/A (creation only)
- Plugins: free-only

## Preconditions
- SLT-SETUP-01 complete.
- Taxes are off site-wide (`woocommerce_calc_taxes=no`), so the fee's taxable flag has no visible effect and all amounts are exact.

## Test data
| Item | Value |
|---|---|
| Product | SLT Signup Fee Daily / slug `slt-signup-fee-daily` |
| Account | N/A |
| Coupon | N/A (SLTFEEPROBE is used against it later) |
| Card | N/A |
| Amounts | Regular price $9.00 + signup fee $15.00 => $24.00 due today; renewal $9.00/day with NO fee |

## Steps
1. Capture `mailpit-agent latest-id`.
2. `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin/post-new.php?post_type=product"` -> `snapshot -i`.
3. **Product title**: `SLT Signup Fee Daily`. **Description**: `SLT window product. Daily recurring with a one-time signup fee. Delete on 2026-08-11.`
4. Type **Simple product**; tick **Virtual**; tick **Subscription [ArraySubs]**.
5. **General** tab: **Regular price ($)** = `9.00`.
6. **Subscription [ArraySubs]** tab: **Billing Period** = `Day`; **Billing Interval** = `1`; **Subscription Length** = `0`; **Trial Length** = `0`; **Sign-up Fee ($)** = `15.00`; **Different Renewal Price** unticked; **Flexible Renewal Sync** left unticked (1-day cycle is below the 3-day minimum anyway).
7. Slug `slt-signup-fee-daily`. Publish. Reload and re-verify.
8. `wp post meta list <ID> --keys=_is_subscription,_subscription_period,_subscription_interval,_signup_fee,_trial_length,_regular_price --allow-root`.
9. As `--session guest`: `agent-browser --session guest open "https://mirror-help.arrayhash.com/cart/?add-to-cart=<ID>"` -> `snapshot -i`; record the fee row and total. Then set the cart quantity to 2 and re-snapshot to confirm the fee did NOT double.
10. Empty the cart. Append the ID to the registry.

## Expected results
1. Published simple + virtual + subscription, slug `slt-signup-fee-daily`.
2. `_signup_fee=15`, `_subscription_period=day`, `_subscription_interval=1`, `_trial_length=0`, `_regular_price=9.00`.
3. Guest cart at qty 1: line subtotal `$9.00`, a fee row labelled `Subscription Signup Fee` of `$15.00`, cart total `$24.00`.
4. Guest cart at qty 2: line subtotal `$18.00`, fee row still exactly `$15.00`, cart total `$33.00` — the fee is per subscription item, not per unit.
5. Cart emptied; no order created.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Product publish and cart preview | — | — | `mailpit-agent latest-id` unchanged from step 1 |

## Evidence to capture
- Screenshots: `SLT-PROD-04-01-subscription-tab.png`, `SLT-PROD-04-02-cart-qty1-fee.png`, `SLT-PROD-04-03-cart-qty2-fee-unchanged.png`.
- Product ID; meta list output; the exact fee row label and both totals.

## Pass criteria
- [ ] Published with signup fee 15.00 and price 9.00
- [ ] Cart shows a $15.00 `Subscription Signup Fee` row at qty 1, total $24.00
- [ ] Fee stays $15.00 at qty 2, total $33.00
- [ ] Metas exactly as listed
- [ ] Zero mail, cart left empty

## Isolation / teardown
- State handoff: buy as `slt-core`. Downstream expectations: the parent order totals $24.00; every renewal order totals $9.00 with NO fee line. `SLTFEEPROBE` ($10.00 fixed cart discount, one-time, apply-to-subscriptions on) must be applied to this product to prove a WooCommerce coupon discounts the $9.00 line only and never the $15.00 fee — expected checkout total $14.00, not $9.00 and not $24.00.
- Restores: cart emptied. Product deleted by SLT-SETUP-99.


## SLT-PROD-05 Create SLT Renewal Price Step with a different renewal price after 2 cycles

*day D00 · priority high · estimate 40m*

## Objective
Provide the intro-price product (first price != renewal price) and capture, in the UI, the code-verified exclusivity: "Different Renewal Price" and "Flexible Renewal Sync" cannot coexist. `SegmentPlan::getConfig()` returns null whenever `_enable_renewal_price === 'yes'`, and the pro view sets `$arraysubs_flex_section_hidden` on the same condition, so the flex control is hidden the moment the checkbox is ticked.

## Scope
- Gateway: both
- Checkout: both
- Account: N/A (creation only)
- Plugins: both (free feature; pro view provides the negative)

## Preconditions
- SLT-SETUP-01 complete.
- Validation contract to respect: if **Different Renewal Price** is ticked, the save is BLOCKED unless **Renewal Price** > 0 and **Apply Renewal Price After** >= 1.

## Test data
| Item | Value |
|---|---|
| Product | SLT Renewal Price Step / slug `slt-renewal-price-step` |
| Account | N/A |
| Coupon | N/A |
| Card | N/A |
| Amounts | Regular price $5.00; renewal price $20.00 applied after 2 billing periods; expected charge today $5.00 |

## Steps
1. Capture `mailpit-agent latest-id`.
2. `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin/post-new.php?post_type=product"` -> `snapshot -i`.
3. **Product title**: `SLT Renewal Price Step`. **Description**: `SLT window product. $5 intro, $20 from cycle 3. Delete on 2026-08-11.`
4. Type **Simple product**; tick **Virtual**; tick **Subscription [ArraySubs]**.
5. **General** tab: **Regular price ($)** = `5.00`.
6. **Subscription [ArraySubs]** tab: **Billing Period** = `Day`; **Billing Interval** = `1`; **Subscription Length** = `0`; **Trial Length** = `0`; **Sign-up Fee ($)** empty.
7. BEFORE ticking the renewal-price box, screenshot the panel showing the **Flexible Renewal Sync to Next Billing Cycle** checkbox present.
8. Tick **Different Renewal Price**. The `show_if_renewal_price` block reveals: set **Renewal Price ($)** = `20.00` and **Apply Renewal Price After** = `2`.
9. Re-snapshot and screenshot: the **Flexible Renewal Sync** section must now be hidden. This is the exclusivity evidence required by the catalog.
10. Negative save probe: temporarily clear **Renewal Price** and click **Publish**; expect the WooCommerce error notice "If different renewal price is enabled, you must set a valid renewal price." and the post NOT going live. Restore `20.00` and publish for real.
11. Slug `slt-renewal-price-step`. Publish. Reload and re-verify.
12. `wp post meta list <ID> --keys=_is_subscription,_subscription_period,_subscription_interval,_enable_renewal_price,_renewal_price,_renewal_price_after,_regular_price,_arraysubs_flex_sync_enabled --allow-root`.
13. Append the ID to the registry.

## Expected results
1. Published simple + virtual + subscription, slug `slt-renewal-price-step`.
2. `_enable_renewal_price=yes`, `_renewal_price=20`, `_renewal_price_after=2`, `_regular_price=5.00`, `_subscription_period=day`, `_subscription_interval=1`.
3. `_arraysubs_flex_sync_enabled` is absent, and the flex UI section is hidden while the renewal-price box is ticked.
4. The negative save probe produced the exact validation error text and left the product unpublished/unchanged (`preserveProductStatusForInvalidSubscriptionSave()` keeps the prior status; `restoreProductPricingFromSavedMeta()` restores prices).
5. Expected downstream billing for the buyer: charge today $5.00; renewal #1 $5.00; renewal #2 $5.00; renewal #3 onward $20.00 — "after 2 billing periods" means the first two periods keep the regular price. The buying task must record the actual crossover cycle as the authoritative reading.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Product publish and the failed save probe | — | — | `mailpit-agent latest-id` unchanged from step 1 |

## Evidence to capture
- Screenshots: `SLT-PROD-05-01-flex-visible-before.png`, `SLT-PROD-05-02-flex-hidden-after-renewal-price.png`, `SLT-PROD-05-03-validation-error.png`, `SLT-PROD-05-04-final-subscription-tab.png`.
- Product ID; meta list output; verbatim validation error string.

## Pass criteria
- [ ] Published with renewal price 20.00 after 2 and regular price 5.00
- [ ] Flex sync section visibly disappears when Different Renewal Price is ticked
- [ ] Empty-renewal-price save is blocked with the exact message
- [ ] Metas exactly as listed, flex meta absent
- [ ] Zero mail

## Isolation / teardown
- State handoff: buy as `slt-core` on a day when at least 4 renewals still fit in the window (D0 or D1) so the $5 -> $20 crossover is observed live. This product is also the canonical "cannot be a Subscription Box child" case: `BoxConfig::isEligibleChildProduct()` excludes any product with `_enable_renewal_price=yes`.
- Restores: nothing. Deleted by SLT-SETUP-99.


## SLT-PROD-06 Create SLT Fixed Three Cycles, a day/2 subscription that expires on 2026-08-07

*day D00 · priority high · estimate 30m*

## Objective
Provide the limited-cycle product whose entire life — signup, two renewals, expiry — fits inside the window. `arraysubs_calculate_end_date_from_length()` computes end = start + (interval x length) periods, so day/2 with length 3 ends exactly 6 days after checkout: bought on D0 (2026-08-01) it renews 2026-08-03 and 2026-08-05 and expires 2026-08-07, with the `expiring_soon` reminder (7 days before) necessarily suppressed because the whole life is shorter than the lead time.

## Scope
- Gateway: both
- Checkout: both
- Account: N/A (creation only)
- Plugins: free-only

## Preconditions
- SLT-SETUP-01 complete.
- SLT-SETUP-02 baseline (global sync off) means the renewal times are anniversary-based: checkout time + 2 days, not midnight.

## Test data
| Item | Value |
|---|---|
| Product | SLT Fixed Three Cycles / slug `slt-fixed-three-cycles` |
| Account | N/A |
| Coupon | N/A |
| Card | N/A |
| Amounts | Regular price $7.00; charge today $7.00; two renewals of $7.00; total lifetime spend $21.00 |

## Steps
1. Capture `mailpit-agent latest-id`.
2. `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin/post-new.php?post_type=product"` -> `snapshot -i`.
3. **Product title**: `SLT Fixed Three Cycles`. **Description**: `SLT window product. Bills every 2 days for 3 cycles, then expires. Delete on 2026-08-11.`
4. Type **Simple product**; tick **Virtual**; tick **Subscription [ArraySubs]**.
5. **General** tab: **Regular price ($)** = `7.00`.
6. **Subscription [ArraySubs]** tab: **Billing Period** = `Day`; **Billing Interval** = `2`; **Subscription Length** = `3`; **Trial Length** = `0`; **Sign-up Fee ($)** empty; **Different Renewal Price** unticked; **Flexible Renewal Sync** left UNTICKED (a 2-day nominal cycle is below `MIN_CYCLE_DAYS = 3`, so the plan could never resolve).
7. Slug `slt-fixed-three-cycles`. Publish. Reload and re-verify.
8. `wp post meta list <ID> --keys=_is_subscription,_subscription_period,_subscription_interval,_subscription_length,_trial_length,_regular_price --allow-root`.
9. As `--session guest`, open the product page and confirm the summary states the limited number of cycles (`getSubscriptionDurationSummary()`).
10. Append the ID to the registry.

## Expected results
1. Published simple + virtual + subscription, slug `slt-fixed-three-cycles`.
2. `_subscription_period=day`, `_subscription_interval=2`, `_subscription_length=3`, `_trial_length=0`, `_regular_price=7.00`.
3. Product page shows a bounded duration (e.g. "for 3 cycles"), not "until cancelled".
4. Date contract for the buying task, bought on 2026-08-01: `_next_payment_date` = 2026-08-03 (same clock time), second renewal 2026-08-05, `_end_date` = 2026-08-07, final status `arraysubs-expired`.
5. `emails.expiring_soon.days_before = 7` exceeds the 6-day life, so no expiring-soon mail can legitimately be sent — the buying task must assert its absence and only the `has expired` mail on 2026-08-07.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Product publish | — | — | `mailpit-agent latest-id` unchanged from step 1 |

## Evidence to capture
- Screenshots: `SLT-PROD-06-01-subscription-tab.png`, `SLT-PROD-06-02-frontend-duration.png`.
- Product ID; meta list output.

## Pass criteria
- [ ] Published with day/2 and length 3, price 7.00
- [ ] Front end shows the bounded cycle count
- [ ] Metas exactly as listed
- [ ] Flex sync left off (sub-minimum cycle documented)
- [ ] Zero mail

## Isolation / teardown
- State handoff: MUST be purchased on D0 (2026-08-01) as `slt-core` — any later purchase pushes the expiry past D9 and out of the observable window. It is also the only SLT product eligible as a day/2 subscription child of the Subscription Box (matching period AND interval), though SLT-PROD-10 creates its own dedicated child instead to avoid coupling.
- Restores: nothing. Deleted by SLT-SETUP-99.


## SLT-PROD-07 Create SLT Lifetime One Time, the never-renews negative control

*day D00 · priority high · estimate 30m*

## Objective
Provide the negative control that must NEVER produce a renewal, a renewal invoice, a renewal reminder, or a next-payment date. `_subscription_period = lifetime` forces `_subscription_interval=1` and `_subscription_length=0` on save, `arraysubs_calculate_next_payment_from_date()` returns an empty string, `arraysubs_calculate_end_date_from_length()` returns null, and both the core and pro sync paths bail on lifetime.

## Scope
- Gateway: both
- Checkout: both
- Account: N/A (creation only)
- Plugins: both (pro view supplies the flex negative)

## Preconditions
- SLT-SETUP-01 complete.
- Validation note: the billing-interval range check is skipped for lifetime, but the regular-price > 0 rule still applies.

## Test data
| Item | Value |
|---|---|
| Product | SLT Lifetime One Time / slug `slt-lifetime-one-time` |
| Account | N/A |
| Coupon | N/A |
| Card | N/A |
| Amounts | Regular price $49.00; charge today $49.00; expected renewals: none, ever |

## Steps
1. Capture `mailpit-agent latest-id`.
2. `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin/post-new.php?post_type=product"` -> `snapshot -i`.
3. **Product title**: `SLT Lifetime One Time`. **Description**: `SLT window product. Lifetime deal, must never renew. Delete on 2026-08-11.`
4. Type **Simple product**; tick **Virtual**; tick **Subscription [ArraySubs]**.
5. **General** tab: **Regular price ($)** = `49.00`.
6. **Subscription [ArraySubs]** tab: **Billing Period** = `Lifetime Deal`. Leave **Billing Interval** and **Subscription Length** as displayed — the saver overwrites them to 1 and 0. **Trial Length** = `0`; **Sign-up Fee ($)** empty; **Different Renewal Price** unticked.
7. Screenshot the panel: the **Flexible Renewal Sync to Next Billing Cycle** section must be hidden for the lifetime period (`$arraysubs_flex_section_hidden = ... || 'lifetime' === $arraysubs_flex_period`). This is the third exclusivity negative required by the catalog.
8. Slug `slt-lifetime-one-time`. Publish. Reload and re-verify.
9. `wp post meta list <ID> --keys=_is_subscription,_subscription_period,_subscription_interval,_subscription_length,_trial_length,_regular_price,_arraysubs_flex_sync_enabled --allow-root`.
10. As `--session guest`, open the product page and confirm the summary shows a one-time/lifetime purchase, not a recurring schedule.
11. Append the ID to the registry.

## Expected results
1. Published simple + virtual + subscription, slug `slt-lifetime-one-time`.
2. `_subscription_period=lifetime`, `_subscription_interval=1` (force-written), `_subscription_length=0` (force-written), `_regular_price=49.00`, `_trial_length=0`.
3. `_arraysubs_flex_sync_enabled` absent and the flex section hidden in the UI.
4. Product page shows a lifetime/one-time summary with no "every N days" phrasing.
5. Contract for the buying task: after checkout the subscription must have an EMPTY `_next_payment_date`, no `_end_date`, no scheduled `arraysubs_generate_renewal_invoice` or `arraysubs_process_renewal` action in Scheduled Actions, and no renewal-related mail for the whole 12-day watch.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Product publish | — | — | `mailpit-agent latest-id` unchanged from step 1 |

## Evidence to capture
- Screenshots: `SLT-PROD-07-01-subscription-tab-lifetime.png`, `SLT-PROD-07-02-flex-hidden-by-lifetime.png`, `SLT-PROD-07-03-frontend.png`.
- Product ID; meta list output showing the forced interval/length.

## Pass criteria
- [ ] Published with period lifetime and price 49.00
- [ ] Interval forced to 1 and length forced to 0 on save
- [ ] Flex sync hidden by lifetime
- [ ] Front end shows no recurring schedule
- [ ] Zero mail

## Isolation / teardown
- State handoff: buy as `slt-core` on D0 and then leave it alone. Every daily renewal-watch task from D1 to D12 must re-assert that this subscription still has no next-payment date, no renewal order, and no renewal mail. Because lifetime products are never sync-eligible, this is also a valid Paddle target if a second Paddle case is needed.
- Restores: nothing. Deleted by SLT-SETUP-99.


## SLT-PROD-08 Create SLT Variable Daily with four subscription variations incl. a $0 probe

*day D00 · priority high · estimate 1h 30m*

## Objective
Provide the variable subscription product with four variations that differ in billing interval, price, signup fee and trial, and use it to probe the $0-recurring branch: `isSubscriptionProductSaveRequest()` returns false when `product-type == 'variable'`, and `saveVariationMeta()` performs no price validation at all, so a $0 variation can be saved even though the identical simple product cannot. Whether that $0 variation is purchasable is a genuine open question this variation exists to answer.

## Scope
- Gateway: both
- Checkout: both
- Account: N/A (creation only)
- Plugins: free-only

## Preconditions
- SLT-SETUP-01 complete.
- All four variations use the `day` period on purpose: the window-wide rule reserves `week` for SLT-PROD-13 and `month` for SLT-PROD-12 only, so variety here comes from interval, price, fee and trial.
- UI contract: the **Subscription [ArraySubs]** product-data TAB is registered `show_if_simple` only. For a variable product you tick the header checkbox **Subscription [ArraySubs]** (which syncs `_is_subscription=yes` onto every variation on save) and then configure each variation in its own expanded variation panel.

## Test data
| Item | Value |
|---|---|
| Product | SLT Variable Daily / slug `slt-variable-daily`, attribute `SLT Tier` |
| Account | N/A |
| Coupon | N/A |
| Card | N/A |
| Amounts | see the variation table |

| Variation (SLT Tier) | Regular price | Billing period | Interval | Length | Trial | Signup fee | Expected charge today |
|---|---|---|---|---|---|---|---|
| Starter | 6.00 | Day | 1 | 0 | 0 | — | $6.00 |
| Plus | 11.00 | Day | 2 | 0 | 0 | 4.00 | $15.00 |
| Trialist | 9.00 | Day | 1 | 0 | 3 day | — | $0.00 |
| Zero Probe | 0.00 | Day | 1 | 0 | 0 | — | $0.00 (probe) |

## Steps
1. Capture `mailpit-agent latest-id`.
2. `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin/post-new.php?post_type=product"` -> `snapshot -i`.
3. **Product title**: `SLT Variable Daily`. **Description**: `SLT window product. Variable subscription, four daily tiers. Delete on 2026-08-11.`
4. Set the product type dropdown to **Variable product**; tick **Virtual**; tick the header checkbox **Subscription [ArraySubs]**.
5. **Attributes** tab: **Add new** custom attribute, Name = `SLT Tier`, Value(s) = `Starter | Plus | Trialist | Zero Probe`, tick **Visible on the product page** and **Used for variations**. Save attributes.
6. **Variations** tab: **Generate variations** (or add four manually) so all four `SLT Tier` values exist.
7. Expand the **Starter** variation and set: **Regular price ($)** `6.00`; in the ArraySubs variation block set **Billing Period** `Day`, **Billing Interval** `1`, **Subscription Length** `0`, **Trial Length** `0`, **Trial Period** `Day`, **Sign-up Fee ($)** empty, **Different Renewal Price** unticked, **Flexible Renewal Sync** unticked.
8. **Plus**: Regular price `11.00`, Billing Period `Day`, Interval `2`, Length `0`, Trial `0`, **Sign-up Fee ($)** `4.00`.
9. **Trialist**: Regular price `9.00`, Billing Period `Day`, Interval `1`, Length `0`, **Trial Length** `3`, **Trial Period** `Day`, no signup fee. Confirm the variation's Flexible Renewal Sync block is hidden by the trial.
10. **Zero Probe**: Regular price `0.00`, Billing Period `Day`, Interval `1`, Length `0`, Trial `0`, no fee. Save variations.
11. Reload the edit screen and check whether the `0.00` price survived. If WooCommerce or ArraySubs rejected it, record the exact message and the resulting stored price — that result IS the finding; do not force it with WP-CLI.
12. Slug `slt-variable-daily`. Publish.
13. `wp post meta list <VARIATION_ID> --keys=_is_subscription,_subscription_period,_subscription_interval,_trial_length,_signup_fee,_regular_price --allow-root` for each of the four variation IDs.
14. As `--session guest`, open the product page, switch the `SLT Tier` dropdown across all four values and screenshot the per-variation subscription summary each time (rendered from `addVariationSubscriptionData()`).
15. Append the parent ID and all four variation IDs to the registry.

## Expected results
1. Parent published as `variable`, virtual, slug `slt-variable-daily`, with `_is_subscription=yes` on the parent and on all four variations.
2. Starter: period day, interval 1, price 6.00, no trial, no fee.
3. Plus: period day, interval 2, price 11.00, `_signup_fee=4`.
4. Trialist: period day, interval 1, price 9.00, `_trial_length=3`, `_trial_period=day`; its flex block is hidden.
5. Zero Probe: either `_regular_price=0` is stored (variation-level saves are unvalidated) — record it as an asymmetry against the simple-product rule — or the save was rejected, in which case the exact rejection text and the surviving price are recorded.
6. The storefront updates price and subscription summary correctly for each of the four tier selections.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Product publish and variation saves | — | — | `mailpit-agent latest-id` unchanged from step 1 |

## Evidence to capture
- Screenshots: `SLT-PROD-08-01-attributes.png`, `SLT-PROD-08-02-variation-starter.png`, `SLT-PROD-08-03-variation-plus.png`, `SLT-PROD-08-04-variation-trialist-flex-hidden.png`, `SLT-PROD-08-05-variation-zero-probe.png`, `SLT-PROD-08-06-frontend-tier-switching.png`.
- Parent ID + four variation IDs; four meta dumps; any validation text for the zero-price variation; console/AJAX errors during **Save changes** on the Variations tab.

## Pass criteria
- [ ] Parent variable + subscription with attribute SLT Tier used for variations
- [ ] Four variations exist with the exact price/interval/trial/fee matrix
- [ ] `_is_subscription=yes` propagated to all four variations
- [ ] Zero Probe outcome recorded either way with evidence
- [ ] Front-end summary changes per tier
- [ ] Zero mail

## Isolation / teardown
- State handoff: buy variations as `slt-core` except Trialist, which belongs to `slt-trial`. Plan-switching tasks may use variation IDs as switch targets — `getAvailableSwitchOptions()` reads `_variation_id` first, and the Linked Products search action is `woocommerce_json_search_products_and_variations`, so variations are legitimate targets.
- Restores: nothing. Parent and variations deleted by SLT-SETUP-99.


## SLT-PROD-09 Create SLT Grouped Set, a grouped product with two subscription children

*day D00 · priority medium · estimate 45m*

## Objective
Provide the grouped product and pin the real behaviour: ArraySubs has NO grouped-product handling at all — the header **Subscription [ArraySubs]** checkbox is registered `show_if_simple show_if_variable`, so a grouped parent can never itself be a subscription. Its children are ordinary simple subscription products added to the cart individually, which means the grouped page is also the cleanest way to exercise `multiple_subscriptions.allow_multiple_in_cart = false` (baseline, unchanged), where adding two subscription children at once must be refused.

## Scope
- Gateway: both
- Checkout: both
- Account: N/A (creation only)
- Plugins: free-only

## Preconditions
- SLT-SETUP-01, SLT-PROD-01 (`SLT Daily Core`) and SLT-PROD-04 (`SLT Signup Fee Daily`) complete.
- This task also creates one plain non-subscription child so the mixed-cart rule (`allow_mixed_cart = true`, unchanged) is exercisable.

## Test data
| Item | Value |
|---|---|
| Product | SLT Grouped Set / slug `slt-grouped-set`; child `SLT Grouped Extra` / slug `slt-grouped-extra` ($3.00, non-subscription) |
| Account | N/A |
| Coupon | N/A |
| Card | N/A |
| Amounts | children: $10.00/day, $9.00/day + $15.00 fee, $3.00 one-off |

## Steps
1. Capture `mailpit-agent latest-id`.
2. Create the plain child first: `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin/post-new.php?post_type=product"`; title `SLT Grouped Extra`; **Simple product**; tick **Virtual**; do NOT tick **Subscription [ArraySubs]**; **Regular price ($)** `3.00`; slug `slt-grouped-extra`; Publish.
3. New product: title `SLT Grouped Set`. **Description**: `SLT window product. Grouped parent with subscription children. Delete on 2026-08-11.`
4. Set the product type dropdown to **Grouped product**. Confirm in the snapshot that the **Subscription [ArraySubs]** header checkbox and the Subscription tab are now HIDDEN — grouped parents are out of scope for the engine by design. Screenshot.
5. **Linked Products** tab -> **Grouped products** field: search and add `SLT Daily Core`, `SLT Signup Fee Daily`, `SLT Grouped Extra` (in that order).
6. Slug `slt-grouped-set`. Publish. Reload and confirm all three children persisted.
7. `wp post meta list <GROUPED_ID> --keys=_children --allow-root` and `wp post list --post_type=product --name=slt-grouped-set --field=ID --allow-root`.
8. As `--session guest`, open `https://mirror-help.arrayhash.com/slt-grouped-set` -> `snapshot -i`. Confirm each subscription child shows its own recurring price summary in the grouped table.
9. Add-to-cart probe A: set quantity 1 on `SLT Daily Core` only, submit, snapshot the cart. Empty the cart.
10. Add-to-cart probe B: set quantity 1 on BOTH `SLT Daily Core` and `SLT Signup Fee Daily`, submit, snapshot. With `allow_multiple_in_cart=false` the second subscription must be refused by `SubscriptionCheckout\Services\CartValidation`; record the exact notice text and which item won. Empty the cart.
11. Add-to-cart probe C: `SLT Daily Core` + `SLT Grouped Extra` (mixed cart) — permitted by `allow_mixed_cart=true`. Snapshot totals, then empty the cart.
12. Append the grouped ID and the extra child ID to the registry.

## Expected results
1. `SLT Grouped Extra` published as a plain simple product, `_is_subscription` absent, price $3.00.
2. `SLT Grouped Set` published as type `grouped` with `_children` containing exactly the three child IDs.
3. The grouped parent offers no subscription checkbox and no Subscription tab.
4. The grouped storefront table renders per-child recurring summaries for the two subscription children and a plain price for the extra.
5. Probe A: cart holds one subscription line, total $10.00 plus no fee (fee belongs to the other child).
6. Probe B: the cart ends up with only ONE subscription line and a WooCommerce notice explaining that multiple subscriptions are not allowed; record the verbatim text.
7. Probe C: cart holds one subscription line ($10.00) and one regular line ($3.00), total $13.00, no error.
8. Cart empty at the end; no order created.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Product publish and all three cart probes | — | — | `mailpit-agent latest-id` unchanged from step 1 |

## Evidence to capture
- Screenshots: `SLT-PROD-09-01-grouped-no-subscription-controls.png`, `SLT-PROD-09-02-frontend-grouped-table.png`, `SLT-PROD-09-03-probe-b-multiple-refused.png`, `SLT-PROD-09-04-probe-c-mixed-cart.png`.
- Grouped ID, extra child ID, `_children` meta; verbatim refusal notice.

## Pass criteria
- [ ] Grouped parent published with exactly three children
- [ ] No subscription controls on the grouped parent (documented)
- [ ] Probe A single-subscription add works
- [ ] Probe B two-subscription add is refused with a captured notice
- [ ] Probe C mixed cart totals $13.00
- [ ] Zero mail, cart left empty

## Isolation / teardown
- State handoff: the refusal notice text from probe B is the reference string for any later multi-subscription-cart test. Do NOT flip `allow_multiple_in_cart` to change this — it is deliberately left at the site default so the refusal path stays testable all window.
- Restores: cart emptied. Grouped parent and `SLT Grouped Extra` deleted by SLT-SETUP-99; the two subscription children are owned by SLT-PROD-01/04.


## SLT-PROD-10 Create SLT Box Daily (pro Subscription Box) plus its three eligible children

*day D00 · priority high · estimate 1h 30m*

## Objective
Build the pro Subscription Box end to end through the Configure Box modal, honouring the eligibility rules enforced by `BoxConfig::isEligibleChildProduct()`: children must be SIMPLE products; non-subscription children are always eligible; subscription children must match the box period AND interval exactly and must not use a different renewal price. The box itself is priced dynamically — the saver clears `_regular_price`/`_sale_price`/`_price`, sets `_sold_individually=yes`, forces `_trial_length=0` and `_signup_fee=0`, and deletes `_enable_renewal_price`.

## Scope
- Gateway: both
- Checkout: both
- Account: N/A (creation only)
- Plugins: pro-required

## Preconditions
- SLT-SETUP-01 complete. `arraysubs_has_module('SubscriptionBox') === 1` verified.
- Box schedule day/2 is chosen so the box renews twice inside the window (2026-08-03, 2026-08-05, 2026-08-07 ...) with no time travel, and so a matching day/2 subscription child is possible.

## Test data
| Item | Value |
|---|---|
| Product | SLT Box Daily / slug `slt-box-daily` (type Subscription Box [ArraySubs]) |
| Children | SLT Box Item A ($4.00, non-sub), SLT Box Item B ($6.00, non-sub), SLT Box Sub Item ($5.00, day/2 subscription) |
| Account | N/A |
| Coupon | N/A |
| Card | N/A |
| Amounts | box total is dynamic; a 2-item selection of A+B = $10.00 recurring every 2 days before discounts |

## Steps
1. Capture `mailpit-agent latest-id`.
2. Create `SLT Box Item A`: new product, **Simple product**, **Virtual**, do NOT tick Subscription, **Regular price ($)** `4.00`, slug `slt-box-item-a`, Publish.
3. Create `SLT Box Item B` the same way at `6.00`, slug `slt-box-item-b`.
4. Create `SLT Box Sub Item`: **Simple product**, **Virtual**, tick **Subscription [ArraySubs]**, **Regular price ($)** `5.00`, Subscription tab: **Billing Period** `Day`, **Billing Interval** `2`, **Subscription Length** `0`, **Trial Length** `0`, no signup fee, **Different Renewal Price** unticked. Slug `slt-box-sub-item`. Publish. (Interval 2 is mandatory — a mismatch makes it invisible to the box product search.)
5. New product: title `SLT Box Daily`. **Description**: `SLT window product. Pro subscription box, bills every 2 days. Delete on 2026-08-11.`
6. Set the product type dropdown to **Subscription Box [ArraySubs]**. The General tab now shows the **Subscription Box Details** panel.
7. Click **Configure Box** to open the `Configure Subscription Box` modal.
8. In **Box Schedule**: **Billing Period** = `Day`; **Billing Interval** = `2`; **Subscription Length** = `0`; leave **Keep signup fees** UNCHECKED (none of the children carry a fee, and unchecked keeps the recurring total clean).
9. Move to **Box Steps**: **Add Step**, set the step title to `Pick your items`. **Add Element** of type product and select `SLT Box Item A`; add a second product element for `SLT Box Item B`; add a third product element and search for `SLT Box Sub Item` — it must appear because its day/2 cycle matches. As a negative probe, search for `SLT Daily Core` (day/1) and `SLT Renewal Price Step` (different renewal price) and confirm neither is offered; screenshot the empty result.
10. Move to **Discounts & Freebies**: leave the discount type at `none` for the baseline box (later tasks may clone it if range pricing needs coverage).
11. Move to **Flexible Renewal Sync**: leave it DISABLED for this box. A day/2 nominal cycle is below `SegmentPlan::MIN_CYCLE_DAYS = 3`, so `syncRenewalSyncMeta()` would write a plan that `getConfig()` then rejects; keeping it off avoids a meaningless half-state.
12. **Save Configuration**, then **Publish** the product with slug `slt-box-daily`.
13. Reload and confirm the read-only summary shows `Billing: every 2 days · until cancelled`, `Signup fees: Not charged`, `Flexible renewal sync: Store default`, and 1 step / 3 elements.
14. `wp post meta list <BOX_ID> --keys=_arraysubs_subscription_box,_is_subscription,_subscription_period,_subscription_interval,_subscription_length,_trial_length,_signup_fee,_sold_individually,_regular_price,_price,_arraysubs_box_config --allow-root`.
15. As `--session guest`, open `https://mirror-help.arrayhash.com/slt-box-daily` -> `snapshot -i` -> click the box launcher button and step through the overlay wizard, selecting A x1 and B x1; read the computed total; then close the overlay WITHOUT adding to cart.
16. Append the box ID and three child IDs to the registry.

## Expected results
1. Three children published: A ($4.00) and B ($6.00) as plain simple products, Sub Item as a day/2 simple subscription at $5.00.
2. `SLT Box Daily` published with type `arraysubs_subscription_box`, `_arraysubs_subscription_box=yes`, `_is_subscription=yes`.
3. Engine meta mirrored from the modal: `_subscription_period=day`, `_subscription_interval=2`, `_subscription_length=0`; forced values `_trial_length=0`, `_signup_fee=0`, `_sold_individually=yes`; `_regular_price` and `_price` EMPTY; `_enable_renewal_price` absent.
4. `_arraysubs_box_config` holds valid JSON with one step and three elements.
5. `_arraysubs_flex_sync_enabled` is absent (sync left off in the modal).
6. The product search inside the modal offered `SLT Box Sub Item` but NOT `SLT Daily Core` (cycle mismatch) and NOT `SLT Renewal Price Step` (different renewal price).
7. The storefront wizard opens, accepts A+B and shows a recurring total of `$10.00` every 2 days; no cart item is created.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | All four product publishes and the wizard preview | — | — | `mailpit-agent latest-id` unchanged from step 1 |

## Evidence to capture
- Screenshots: `SLT-PROD-10-01-modal-schedule.png`, `SLT-PROD-10-02-modal-steps.png`, `SLT-PROD-10-03-ineligible-search-empty.png`, `SLT-PROD-10-04-admin-summary.png`, `SLT-PROD-10-05-frontend-wizard-total.png`.
- Box ID + three child IDs; full meta dump; the `_arraysubs_box_config` JSON; REST errors from `arraysubs/v1/` during the modal (network tab).

## Pass criteria
- [ ] Three eligible children published with the exact prices/cycle
- [ ] Box published with type arraysubs_subscription_box and 1 step / 3 elements
- [ ] Engine meta mirrored and forced values correct (no stored price, sold individually)
- [ ] Ineligible products absent from the modal search (both reasons)
- [ ] Wizard computes $10.00 every 2 days for A+B
- [ ] Zero mail, nothing added to cart

## Isolation / teardown
- State handoff: buy as `slt-core`. Behaviour later tasks must assume: adding a box EMPTIES the cart first; contents are added to the order at zero cost while the box line carries the whole recurring total; free trials are switched off for everything inside a box; the frozen contents live on `_arraysubs_box_contents`.
- Restores: overlay closed, cart untouched. Box and all three children deleted by SLT-SETUP-99.


## SLT-PROD-11 Create the four-product plan ladder and wire upgrade/downgrade/crossgrade links

*day D00 · priority high · estimate 1h 30m*

## Objective
Build the switching ladder as four daily subscription products and link them through the WooCommerce **Linked Products** tab, which is where `PlanSwitching\Services\Hooks::addSwitchingFields()` renders **Upgrade to**, **Downgrade to**, **Crossgrade to** and **Auto-downgrade to**. Targets are stored as ID arrays in `_arraysubs_upgrade_products`, `_arraysubs_downgrade_products`, `_arraysubs_crossgrade_products` and `_arraysubs_auto_downgrade_product`, and `ProrationCalculator::getAvailableSwitchOptions()` reads them from the SOURCE product only — so the links must be set on every rung, in both directions.

## Scope
- Gateway: Stripe test
- Checkout: both
- Account: N/A (creation only)
- Plugins: free-only

## Preconditions
- SLT-SETUP-01 complete.
- Baseline `plan_switching`: enabled, upgrades/downgrades/crossgrades all allowed, `proration_type = prorate_immediately`, `allow_customer_switch = true`, `auto_downgrade_timing = on_expire` — all unchanged by this window.
- All four rungs are day/1 so a switch and its prorated order are observable the same day, and so proration maths uses a 1-day cycle (credit/charge is dominated by the price delta, not by elapsed time).

## Test data
| Item | Value |
|---|---|
| Products | SLT Plan Basic `slt-plan-basic` $5.00; SLT Plan Pro `slt-plan-pro` $15.00; SLT Plan Enterprise `slt-plan-enterprise` $30.00; SLT Plan Peer `slt-plan-peer` $15.00 |
| Account | N/A |
| Coupon | N/A |
| Card | N/A |
| Amounts | all day/1, no trial, no fee, no length limit |

Link matrix (set on each source product's Linked Products tab):

| Source | Upgrade to | Downgrade to | Crossgrade to | Auto-downgrade to |
|---|---|---|---|---|
| SLT Plan Basic | SLT Plan Pro, SLT Plan Enterprise | (none) | (none) | (none) |
| SLT Plan Pro | SLT Plan Enterprise | SLT Plan Basic | SLT Plan Peer | SLT Plan Basic |
| SLT Plan Enterprise | (none) | SLT Plan Pro, SLT Plan Basic | (none) | SLT Plan Basic |
| SLT Plan Peer | SLT Plan Enterprise | SLT Plan Basic | SLT Plan Pro | (none) |

## Steps
1. Capture `mailpit-agent latest-id`.
2. For each of the four products: `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin/post-new.php?post_type=product"`; set the title; **Description** `SLT window product. Plan-switching ladder rung. Delete on 2026-08-11.`; **Simple product**; tick **Virtual**; tick **Subscription [ArraySubs]**; **General** tab **Regular price ($)** per the table; **Subscription [ArraySubs]** tab: **Billing Period** `Day`, **Billing Interval** `1`, **Subscription Length** `0`, **Trial Length** `0`, no signup fee, **Different Renewal Price** unticked, **Flexible Renewal Sync** unticked; set the slug; Publish.
3. Record all four product IDs before wiring links.
4. Re-open each product and go to the **Linked Products** tab. Confirm the **Subscription Plan Switching** block is visible (it is display:none unless `_is_subscription=yes`).
5. Fill **Upgrade to**, **Downgrade to**, **Crossgrade to** and **Auto-downgrade to** exactly per the link matrix using the product-search selects (`data-action=woocommerce_json_search_products_and_variations`). Update.
6. Reload each product and confirm the selects still show the chosen products (proves `saveSwitchingFields()` persisted arrays, not strings).
7. Verify: `wp post meta get <ID> _arraysubs_upgrade_products --format=json --allow-root` (and the downgrade/crossgrade/auto keys) for all four.
8. Append the four IDs to the registry along with the link matrix.

## Expected results
1. Four products published, all simple + virtual + subscription, day/1, no trial, no fee, prices $5.00 / $15.00 / $30.00 / $15.00.
2. `_arraysubs_upgrade_products` on Basic is a 2-element array containing the Pro and Enterprise IDs.
3. Pro carries all four keys: upgrade `[Enterprise]`, downgrade `[Basic]`, crossgrade `[Peer]`, auto-downgrade `Basic`.
4. Enterprise carries downgrade `[Pro, Basic]` and auto-downgrade `Basic`, with an empty upgrade array.
5. Peer carries upgrade `[Enterprise]`, downgrade `[Basic]`, crossgrade `[Pro]`.
6. Pro <-> Peer is a genuine crossgrade (identical $15.00 price) so `ProrationCalculator` classifies it laterally and applies no proration or credit.
7. All link selects survive a page reload.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Four publishes and four link saves | — | — | `mailpit-agent latest-id` unchanged from step 1 |

## Evidence to capture
- Screenshots: `SLT-PROD-11-01-basic-subscription-tab.png`, `SLT-PROD-11-02-pro-linked-products.png`, `SLT-PROD-11-03-enterprise-linked-products.png`, `SLT-PROD-11-04-peer-linked-products.png`.
- Four product IDs; the four meta JSON dumps; any select2 AJAX errors.

## Pass criteria
- [ ] Four rungs published at the exact prices, all day/1
- [ ] Link matrix stored as ID arrays on all four sources
- [ ] Links survive reload
- [ ] Pro and Peer are equal-priced (true crossgrade)
- [ ] Zero mail

## Isolation / teardown
- State handoff: buy and switch ONLY as `slt-switch`. Switching requires the subscription to be in `arraysubs-active` or `arraysubs-trial` — `SwitchController` rejects any other status with "Plan switching is only available for active subscriptions". Auto-downgrade fires on expiry (`auto_downgrade_timing = on_expire`), which needs a length-limited or time-travelled subscription; the ladder rungs are length 0, so an auto-downgrade test must set `_end_date` by hand rather than expect it naturally.
- Restores: nothing global. All four deleted by SLT-SETUP-99.


## SLT-PROD-12 Create SLT Flex Month Segments, the single month-interval flexible-sync product

*day D00 · priority high · estimate 1h*

## Objective
Create the ONE month-interval product in the whole plan. It is the only artifact where calendar date math and all three flexible-sync segment modes are simultaneously observable, because for a month cycle `arraysubs_calculate_renewal_sync_cycle_dates()` anchors `cycle_start` to the first of the month — so the day-in-cycle varies with the purchase date, which it never does for a `day` period (where cycle_start is always the purchase day itself and day-in-cycle is permanently 1).

## Scope
- Gateway: Stripe test (Paddle is hidden on sync-eligible carts by design)
- Checkout: both
- Account: N/A (creation only)
- Plugins: pro-required

## Preconditions
- SLT-SETUP-01 and SLT-SETUP-02 complete. Global sync is OFF, so this product syncs ONLY because `FlexibleRenewalSync\Services\Hooks::filterSupportsRenewalSync()` grants it per-product — that is a deliberate part of what this product proves.
- Verified live against the site helper: for a purchase at `2026-08-01 09:00:00` site time, month cycle_start = `2026-07-31 18:00:00` UTC (= 2026-08-01 00:00 site) and next_payment = `2026-08-31 18:00:00` UTC (= 2026-09-01 00:00 site).
- Segment boundaries are chosen so all three modes are reachable by purchase date inside D0..D9.

## Test data
| Item | Value |
|---|---|
| Product | SLT Flex Month Segments / slug `slt-flex-month-segments` |
| Account | N/A |
| Coupon | N/A |
| Card | N/A |
| Amounts | Regular price $30.00/month; segment-dependent first charge (see Expected results) |

Segment plan: nominal cycle 30 days, all three segments ACTIVE, boundaries seg1_end = `2`, seg2_end = `6`. Partition: days 1-2 = segment 1 (full), days 3-6 = segment 2 (prorate), days 7-30(+31st overflow) = segment 3 (charge full for next billing cycle).

## Steps
1. Capture `mailpit-agent latest-id`.
2. `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin/post-new.php?post_type=product"` -> `snapshot -i`.
3. **Product title**: `SLT Flex Month Segments`. **Description**: `SLT window product. Monthly, flexible renewal sync, 3 active segments. Delete on 2026-08-11.`
4. **Simple product**; tick **Virtual**; tick **Subscription [ArraySubs]**.
5. **General** tab: **Regular price ($)** = `30.00`.
6. **Subscription [ArraySubs]** tab: **Billing Period** = `Month`; **Billing Interval** = `1`; **Subscription Length** = `0`; **Trial Length** = `0`; **Sign-up Fee ($)** empty; **Different Renewal Price** UNTICKED (ticking it would hide the flex section outright).
7. Tick **Flexible Renewal Sync to Next Billing Cycle**. The segment slider appears with `data-cycle-days="30"`.
8. Leave all three legend toggles ON (**Full amount**, **Prorate amount**, **Charge full for next billing cycle**).
9. Drag the slider handles until the legend reads segment 1 = `1 - 2`, segment 2 = `3 - 6`, segment 3 = `7 - 30`. Screenshot the legend.
10. Slug `slt-flex-month-segments`. Publish. Reload and confirm the slider redraws with the same ranges.
11. `wp post meta list <ID> --keys=_is_subscription,_subscription_period,_subscription_interval,_arraysubs_flex_sync_enabled,_arraysubs_flex_sync_seg1_end,_arraysubs_flex_sync_seg2_end,_arraysubs_flex_sync_seg1_active,_arraysubs_flex_sync_seg2_active,_arraysubs_flex_sync_seg3_active,_regular_price --allow-root`.
12. As `--session guest`, add to cart and read the cart's subscription meta rows WITHOUT checking out, then empty the cart. On D0 the purchase falls on day 1, so no "First billing cycle" bonus-access note should appear (that note is segment-3 only).
13. Append the ID to the registry together with the purchase-date-to-segment table.

## Expected results
1. Published simple + virtual + subscription, slug `slt-flex-month-segments`, `_subscription_period=month`, `_subscription_interval=1`, `_regular_price=30.00`.
2. `_arraysubs_flex_sync_enabled=yes`, `_arraysubs_flex_sync_seg1_end=2`, `_arraysubs_flex_sync_seg2_end=6`, and all three `_active` metas = `yes`.
3. The slider legend after reload reads 1-2 / 3-6 / 7-30.
4. Purchase-date contract for downstream tasks (site-local dates, price $30.00):
   - Bought 2026-08-01 (day 1) -> segment 1, mode `full`, charge $30.00, next payment 2026-09-01 00:00 site.
   - Bought 2026-08-04 (day 4) -> segment 2, mode `prorate`, cycle 2026-08-01..2026-09-01 = 31 days, remaining days = 27, ratio 27/31, charge `round(30 * 27/31, 2)` = $26.13, next payment 2026-09-01 00:00 site.
   - Bought 2026-08-08 (day 8) -> segment 3, mode `next_cycle`, charge $30.00 in full, `cycle_start_date` moves to 2026-09-01 and next payment is pushed to 2026-10-01 00:00 site; the cart shows the "Today's payment covers the full billing cycle starting 1 September, 2026" note.
5. Guest cart on D0 shows a $30.00 first charge and NO bonus-access note.
6. Because the next payment is always outside the window, every renewal on this product is reached by editing `_next_payment_date` and draining Action Scheduler — never by waiting.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Product publish and cart preview | — | — | `mailpit-agent latest-id` unchanged from step 1 |

## Evidence to capture
- Screenshots: `SLT-PROD-12-01-subscription-tab.png`, `SLT-PROD-12-02-segment-slider-legend.png`, `SLT-PROD-12-03-after-reload.png`, `SLT-PROD-12-04-cart-day1.png`.
- Product ID; full flex meta dump; console errors from the slider bundle `flexible-renewal-sync.js`.

## Pass criteria
- [ ] Published as month/1 at $30.00 with flex sync enabled
- [ ] seg1_end=2, seg2_end=6, all three segments active
- [ ] Legend survives reload as 1-2 / 3-6 / 7-30
- [ ] Day-1 cart charges $30.00 with no next-cycle note
- [ ] Purchase-date-to-segment table recorded in the registry
- [ ] Zero mail, cart left empty

## Isolation / teardown
- State handoff: buy ONLY as `slt-flex`, and buy it three separate times on D0, D3 (2026-08-04) and D7 (2026-08-08) to hit segments 1, 2 and 3 — that is the only way to cover all three modes with real day-in-cycle values. This is the ONLY month-interval product in the plan; no other task may create one.
- Restores: cart emptied. Product deleted by SLT-SETUP-99.


## SLT-PROD-13 Create SLT Flex Week Segments, the single week-interval flexible-sync product

*day D00 · priority high · estimate 1h*

## Objective
Create the ONE week-interval product. It covers the week-boundary branch of `arraysubs_calculate_renewal_sync_cycle_dates()`, which snaps the cycle start to the store's start-of-week — and this store's `start_of_week` is **6 (Saturday)**, which happens to be D0 itself. That makes the week cycle start on 2026-08-01 and end on 2026-08-08, so unlike the month product this one also produces a REAL renewal inside the window.

## Scope
- Gateway: Stripe test (Paddle hidden on sync-eligible carts)
- Checkout: both
- Account: N/A (creation only)
- Plugins: pro-required

## Preconditions
- SLT-SETUP-01 and SLT-SETUP-02 complete (global sync off; this product syncs via the per-product pro override).
- Verified live against the site helper: purchase at `2026-08-01 09:00:00` site -> cycle_start `2026-07-31 18:00:00` UTC (2026-08-01 00:00 site), next_payment `2026-08-07 18:00:00` UTC (**2026-08-08 00:00 site**).
- 2026-08-01 is a Saturday and `start_of_week=6`, so day-in-cycle on D0 is 1.

## Test data
| Item | Value |
|---|---|
| Product | SLT Flex Week Segments / slug `slt-flex-week-segments` |
| Account | N/A |
| Coupon | N/A |
| Card | N/A |
| Amounts | Regular price $14.00/week; segment-dependent first charge (see Expected results) |

Segment plan: nominal cycle 7 days, all three segments ACTIVE, boundaries seg1_end = `2`, seg2_end = `5`. Partition: days 1-2 = segment 1 (full), days 3-5 = segment 2 (prorate), days 6-7 = segment 3 (next cycle).

## Steps
1. Capture `mailpit-agent latest-id`.
2. `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin/post-new.php?post_type=product"` -> `snapshot -i`.
3. **Product title**: `SLT Flex Week Segments`. **Description**: `SLT window product. Weekly, flexible renewal sync, 3 active segments. Delete on 2026-08-11.`
4. **Simple product**; tick **Virtual**; tick **Subscription [ArraySubs]**.
5. **General** tab: **Regular price ($)** = `14.00`.
6. **Subscription [ArraySubs]** tab: **Billing Period** = `Week`; **Billing Interval** = `1`; **Subscription Length** = `0`; **Trial Length** = `0`; **Sign-up Fee ($)** empty; **Different Renewal Price** unticked.
7. Tick **Flexible Renewal Sync to Next Billing Cycle**; the slider appears with `data-cycle-days="7"`.
8. Leave all three legend toggles ON and drag the handles until the legend reads 1 = `1 - 2`, 2 = `3 - 5`, 3 = `6 - 7`. Screenshot.
9. Slug `slt-flex-week-segments`. Publish. Reload and confirm the ranges redraw identically.
10. `wp post meta list <ID> --keys=_is_subscription,_subscription_period,_subscription_interval,_arraysubs_flex_sync_enabled,_arraysubs_flex_sync_seg1_end,_arraysubs_flex_sync_seg2_end,_arraysubs_flex_sync_seg1_active,_arraysubs_flex_sync_seg2_active,_arraysubs_flex_sync_seg3_active,_regular_price --allow-root`.
11. As `--session guest`, add to cart, read the subscription meta rows and the first-charge amount, then empty the cart.
12. Append the ID plus the purchase-date-to-segment table to the registry.

## Expected results
1. Published simple + virtual + subscription, slug `slt-flex-week-segments`, `_subscription_period=week`, `_subscription_interval=1`, `_regular_price=14.00`.
2. `_arraysubs_flex_sync_enabled=yes`, `_arraysubs_flex_sync_seg1_end=2`, `_arraysubs_flex_sync_seg2_end=5`, all three `_active` = `yes`.
3. Legend after reload: 1-2 / 3-5 / 6-7.
4. Purchase-date contract (site-local, price $14.00, week cycle 2026-08-01..2026-08-08):
   - Bought 2026-08-01 (Sat, day 1) -> segment 1, mode `full`, charge $14.00, next payment 2026-08-08 00:00 site — a REAL renewal on D7, no time travel needed.
   - Bought 2026-08-04 (Tue, day 4) -> segment 2, mode `prorate`; cycle_days 7, days_until_next 4, remaining = max(1, 4-1) = 3, ratio 3/7, charge `round(14 * 3/7, 2)` = $6.00; next payment 2026-08-08 00:00 site.
   - Bought 2026-08-06 (Thu, day 6) -> segment 3, mode `next_cycle`, charge $14.00 in full covering the cycle starting 2026-08-08, next payment pushed to 2026-08-15 00:00 site (outside the window -> time-travel), and the cart shows the "covers the full billing cycle starting 8 August, 2026" note.
5. Guest cart on D0 shows a $14.00 first charge, no next-cycle note.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Product publish and cart preview | — | — | `mailpit-agent latest-id` unchanged from step 1 |

## Evidence to capture
- Screenshots: `SLT-PROD-13-01-subscription-tab.png`, `SLT-PROD-13-02-segment-slider-legend.png`, `SLT-PROD-13-03-after-reload.png`, `SLT-PROD-13-04-cart-day1.png`.
- Product ID; full flex meta dump; slider console errors.

## Pass criteria
- [ ] Published as week/1 at $14.00 with flex sync enabled
- [ ] seg1_end=2, seg2_end=5, all three segments active
- [ ] Legend survives reload as 1-2 / 3-5 / 6-7
- [ ] Day-1 cart charges $14.00 and the D0 purchase is dated to renew 2026-08-08
- [ ] Purchase-date-to-segment table recorded
- [ ] Zero mail, cart left empty

## Isolation / teardown
- State handoff: buy ONLY as `slt-flex`, three separate times — D0 (segment 1, and let the 2026-08-08 renewal fire for real), 2026-08-04 (segment 2, the ONLY place in the whole plan where a genuinely prorated first charge is observable) and 2026-08-06 (segment 3). This is the ONLY week-interval product in the plan.
- Restores: cart emptied. Product deleted by SLT-SETUP-99.


## SLT-PROD-14 Create the two daily flex-sync partition products (2-active and 1-active)

*day D00 · priority high · estimate 1h*

## Objective
Cover the two partition shapes the calendar products cannot reach and get segment-3 `next_cycle` behaviour onto a real, unattended daily schedule. Both products use `day` with interval 3 — the smallest cycle at or above `SegmentPlan::MIN_CYCLE_DAYS = 3` that still renews twice inside the window. A crucial code-verified consequence: for the `day` period `cycle_start` is the purchase day itself, so day-in-cycle is ALWAYS 1, which means the FIRST ACTIVE segment always wins and segment selection is controlled purely by which toggles are off.

## Scope
- Gateway: Stripe test (Paddle hidden on sync-eligible carts — this pair is the gateway-gating negative)
- Checkout: both
- Account: N/A (creation only)
- Plugins: pro-required

## Preconditions
- SLT-SETUP-01 and SLT-SETUP-02 complete. Global sync is OFF, so both products sync only because of the per-product pro override — they are the proof of that override.
- Verified live: purchase at `2026-08-01 09:00:00` site with day/3 gives cycle_start `2026-07-31 18:00:00` UTC and next_payment `2026-08-03 18:00:00` UTC (= 2026-08-04 00:00 site).

## Test data
| Item | Value |
|---|---|
| Product A | SLT Flex Daily Two Seg / slug `slt-flex-daily-two-seg`, $9.00, day/3, segments 2+3 active (segment 1 OFF), boundary seg1_end = 1 |
| Product B | SLT Flex Daily Next Cycle / slug `slt-flex-daily-next-cycle`, $9.00, day/3, ONLY segment 3 active |
| Account | N/A |
| Coupon | N/A |
| Card | N/A |
| Amounts | A: $9.00 today, renew 2026-08-04 00:00 site. B: $9.00 today, renew 2026-08-07 00:00 site |

## Steps
1. Capture `mailpit-agent latest-id`.
2. Create Product A: new product, title `SLT Flex Daily Two Seg`, description `SLT window product. Day/3, 2-active segment partition. Delete on 2026-08-11.`, **Simple product**, **Virtual**, tick **Subscription [ArraySubs]**, **Regular price ($)** `9.00`.
3. **Subscription [ArraySubs]** tab for A: **Billing Period** `Day`, **Billing Interval** `3`, **Subscription Length** `0`, **Trial Length** `0`, no signup fee, **Different Renewal Price** unticked.
4. Tick **Flexible Renewal Sync to Next Billing Cycle** (slider `data-cycle-days="3"`). Turn the **Full amount** (segment 1) legend toggle OFF; leave **Prorate amount** and **Charge full for next billing cycle** ON. Drag the single remaining handle so the legend reads segment 2 = `1`, segment 3 = `2 - 3`. Screenshot. Attempt to also turn segment 2 off and confirm the UI refuses with "At least one segment must stay active." then restore it.
5. Slug `slt-flex-daily-two-seg`. Publish. Reload and confirm the 2-row legend redraws.
6. Create Product B: title `SLT Flex Daily Next Cycle`, description `SLT window product. Day/3, single-active segment 3. Delete on 2026-08-11.`, same type/virtual/subscription flags, **Regular price ($)** `9.00`, **Billing Period** `Day`, **Billing Interval** `3`, length 0, trial 0, no fee.
7. Tick **Flexible Renewal Sync**; turn BOTH **Full amount** and **Prorate amount** toggles OFF, leaving only **Charge full for next billing cycle** ON. The legend must collapse to a single row covering `1 - 3` with no boundary handle. Screenshot.
8. Slug `slt-flex-daily-next-cycle`. Publish. Reload and confirm.
9. Verify metas for both: `wp post meta list <ID> --keys=_subscription_period,_subscription_interval,_arraysubs_flex_sync_enabled,_arraysubs_flex_sync_seg1_end,_arraysubs_flex_sync_seg2_end,_arraysubs_flex_sync_seg1_active,_arraysubs_flex_sync_seg2_active,_arraysubs_flex_sync_seg3_active,_regular_price --allow-root`.
10. As `--session guest`, add Product B to the cart and confirm the checkout gateway list omits Paddle (the gating negative) and that the cart shows the "Today's payment covers the full billing cycle starting 4 August, 2026" note. Empty the cart. Repeat the gateway check with Product A.
11. Append both IDs to the registry.

## Expected results
1. Both products published, simple + virtual + subscription, `_subscription_period=day`, `_subscription_interval=3`, `_regular_price=9.00`.
2. Product A: `_arraysubs_flex_sync_enabled=yes`, `_arraysubs_flex_sync_seg1_active=no`, `seg2_active=yes`, `seg3_active=yes`, `_arraysubs_flex_sync_seg1_end=1`. Legend is two rows: `1` / `2 - 3`.
3. Product B: `seg1_active=no`, `seg2_active=no`, `seg3_active=yes`; the legend is one row `1 - 3` and no boundary is used (`getConfig()` returns an empty boundaries array for a 1-active plan).
4. The UI refuses to leave zero segments active with the exact message "At least one segment must stay active."
5. Purchase contract for A (bought 2026-08-01): day-in-cycle 1 -> first active segment is 2 -> mode `prorate`. Because `start <= cycle_start` for a day period, `arraysubs_calculate_renewal_sync_prorated_amount()` returns ratio 1.0, so the charge is the FULL $9.00. Next payment 2026-08-04 00:00 site, then 2026-08-07, then 2026-08-10 — all inside the window. Prorate mode being indistinguishable from full on a `day` period is expected, not a bug; genuine proration lives on SLT-PROD-13.
6. Purchase contract for B (bought 2026-08-01): day-in-cycle 1 -> segment 3 -> mode `next_cycle`; charge the full $9.00 today, `flex_covered_cycle_start` = 2026-08-04 00:00 site, and the next payment is PUSHED one whole cycle to **2026-08-07 00:00 site** — a visibly different date from A, which is the observable proof of segment-3 behaviour.
7. Paddle is absent from the payment options for both products; Stripe is present.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Two publishes and the cart previews | — | — | `mailpit-agent latest-id` unchanged from step 1 |

## Evidence to capture
- Screenshots: `SLT-PROD-14-01-two-seg-legend.png`, `SLT-PROD-14-02-last-active-refusal.png`, `SLT-PROD-14-03-next-cycle-single-legend.png`, `SLT-PROD-14-04-cart-next-cycle-note.png`, `SLT-PROD-14-05-paddle-absent.png`.
- Both product IDs; both meta dumps; slider console errors.

## Pass criteria
- [ ] Product A saved with a 2-active partition (seg1 off) and legend 1 / 2-3
- [ ] Product B saved with a 1-active partition (segment 3 only) and legend 1-3
- [ ] Last-active-segment refusal captured verbatim
- [ ] Cart on B shows the next-cycle bonus-access note naming 4 August, 2026
- [ ] Paddle hidden, Stripe offered, for both
- [ ] Zero mail, cart left empty

## Isolation / teardown
- State handoff: buy both on D0 as `slt-flex`, one at a time (baseline `allow_multiple_in_cart=false` forbids putting both in one cart). The pair's whole value is the diverging next-payment dates: A renews 2026-08-04 / 08-07 / 08-10, B renews 2026-08-07 / 08-10 — both unattended, both inside the watch window.
- Restores: cart emptied. Both deleted by SLT-SETUP-99.


## SLT-PROD-15 Create SLT Flex Variable Daily with per-variation flexible-sync configuration

*day D00 · priority medium · estimate 1h*

## Objective
Cover the variation-level flexible-sync configuration path, which is a separate code path from the simple-product one: the pro feature renders through `arraysubs_subscription_variation_fields_before_shipping` and saves through `saveVariationMeta()` with `$_POST[META][$loop]` array indexing. Three variations on one identical day/3 schedule differ ONLY in their segment plan, so any difference in first charge or next-payment date is attributable to the plan alone.

## Scope
- Gateway: Stripe test
- Checkout: both
- Account: N/A (creation only)
- Plugins: pro-required

## Preconditions
- SLT-SETUP-01, SLT-SETUP-02 and SLT-PROD-14 complete (PROD-14 establishes the expected simple-product behaviour this task compares against).
- `filterSupportsRenewalSync()` and `filterRenewalSyncContext()` both key off `subscription_data['product_id']`; for a variation purchase that resolves to the VARIATION id, so the plan must be stored on the variation, not the parent.

## Test data
| Item | Value |
|---|---|
| Product | SLT Flex Variable Daily / slug `slt-flex-variable-daily`, attribute `SLT Sync Mode` |
| Account | N/A |
| Coupon | N/A |
| Card | N/A |
| Amounts | all variations $12.00, day/3 |

| Variation (SLT Sync Mode) | Price | Period/Interval | Flex sync | Segments active | Boundaries | Expected next payment if bought 2026-08-01 |
|---|---|---|---|---|---|---|
| Full | 12.00 | Day / 3 | ON | 1, 2, 3 | seg1_end 1, seg2_end 2 | 2026-08-04 00:00 site, charge $12.00 |
| Next Cycle | 12.00 | Day / 3 | ON | 3 only | none | 2026-08-07 00:00 site, charge $12.00 |
| No Sync | 12.00 | Day / 3 | OFF | — | — | anniversary: checkout time + 3 days, charge $12.00 |

## Steps
1. Capture `mailpit-agent latest-id`.
2. `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin/post-new.php?post_type=product"` -> `snapshot -i`.
3. **Product title**: `SLT Flex Variable Daily`. **Description**: `SLT window product. Variation-level flexible renewal sync. Delete on 2026-08-11.`
4. Product type **Variable product**; tick **Virtual**; tick the header checkbox **Subscription [ArraySubs]**.
5. **Attributes** tab: custom attribute Name `SLT Sync Mode`, Values `Full | Next Cycle | No Sync`, tick **Visible on the product page** and **Used for variations**. Save attributes.
6. **Variations** tab: generate the three variations.
7. **Full** variation: **Regular price ($)** `12.00`; ArraySubs block: **Billing Period** `Day`, **Billing Interval** `3`, **Subscription Length** `0`, **Trial Length** `0`, no signup fee, **Different Renewal Price** unticked; tick **Flexible Renewal Sync to Next Billing Cycle**; leave all three legend toggles ON; set the handles so the legend reads `1` / `2` / `3`.
8. **Next Cycle** variation: same price and schedule; tick flex sync; turn segment 1 and segment 2 toggles OFF so only **Charge full for next billing cycle** remains, legend `1 - 3`.
9. **No Sync** variation: same price and schedule; leave **Flexible Renewal Sync** UNTICKED.
10. Save variations, reload the Variations tab and expand all three to confirm each legend/toggle state survived the AJAX save.
11. Slug `slt-flex-variable-daily`. Publish.
12. For each variation id: `wp post meta list <VARIATION_ID> --keys=_is_subscription,_subscription_period,_subscription_interval,_arraysubs_flex_sync_enabled,_arraysubs_flex_sync_seg1_active,_arraysubs_flex_sync_seg2_active,_arraysubs_flex_sync_seg3_active,_arraysubs_flex_sync_seg1_end,_arraysubs_flex_sync_seg2_end,_regular_price --allow-root`.
13. As `--session guest`, open the product page, select each `SLT Sync Mode` in turn, add to cart, read the subscription meta rows (only **Next Cycle** may show the bonus-access note), empty the cart between selections.
14. Append the parent ID and all three variation IDs to the registry.

## Expected results
1. Parent published as `variable`, virtual, `_is_subscription=yes` propagated to all three variations, slug `slt-flex-variable-daily`.
2. **Full**: `_arraysubs_flex_sync_enabled=yes`, all three `_active=yes`, `seg1_end=1`, `seg2_end=2`.
3. **Next Cycle**: `_arraysubs_flex_sync_enabled=yes`, `seg1_active=no`, `seg2_active=no`, `seg3_active=yes`.
4. **No Sync**: `_arraysubs_flex_sync_enabled` ABSENT (the saver deletes it when the box is unticked, while preserving any previously submitted boundary values).
5. All three legends survive the variation AJAX save and a full page reload.
6. In the cart, only **Next Cycle** shows the "Today's payment covers the full billing cycle starting 4 August, 2026" note; **Full** and **No Sync** do not.
7. Buying contract: **Full** renews 2026-08-04 00:00 site; **Next Cycle** renews 2026-08-07 00:00 site; **No Sync** renews at checkout time + 3 days (an anniversary time, not midnight) — the three-way divergence is the deliverable.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Publish and cart previews | — | — | `mailpit-agent latest-id` unchanged from step 1 |

## Evidence to capture
- Screenshots: `SLT-PROD-15-01-variation-full-legend.png`, `SLT-PROD-15-02-variation-next-cycle-legend.png`, `SLT-PROD-15-03-variation-no-sync-unticked.png`, `SLT-PROD-15-04-cart-next-cycle-note.png`.
- Parent + three variation IDs; three meta dumps; any AJAX error from **Save changes** on the Variations tab.

## Pass criteria
- [ ] Three variations saved with distinct segment plans on an identical day/3 schedule
- [ ] No Sync variation has the flex meta deleted, not set to 'no'
- [ ] Legends survive AJAX save and reload
- [ ] Only Next Cycle shows the bonus-access cart note
- [ ] Divergent next-payment contract recorded in the registry
- [ ] Zero mail, cart left empty

## Isolation / teardown
- State handoff: buy as `slt-flex`, one variation at a time on D0 (baseline forbids multiple subscriptions per cart). If the Full and Next Cycle variations produce identical next-payment dates, the variation-level plan is not being read — file that as a defect against `filterRenewalSyncContext()` resolving `product_id` to the parent instead of the variation.
- Restores: cart emptied. Parent and variations deleted by SLT-SETUP-99.


## SLT-PROD-16 Create SLT Retry Daily and SLT Paddle Daily, the two gateway-path products

*day D00 · priority critical · estimate 45m*

## Objective
Create the two products whose only distinguishing feature is the gateway they are bought with: one wired to Stripe's always-declines-off-session card so the failure / on-hold / cancel dunning ladder runs on a real daily schedule, and one reserved exclusively for Paddle sandbox. Both are plain day/1 subscriptions with no trial, no fee and no flex sync, so any behavioural difference is attributable to the gateway alone.

## Scope
- Gateway: both
- Checkout: both
- Account: N/A (creation only)
- Plugins: both

## Preconditions
- SLT-SETUP-01 and SLT-SETUP-02 complete. Global sync OFF is what makes Paddle selectable at all; SLT-SETUP-05 verifies that after this task.
- Neither product may enable flexible sync — a sync-eligible cart would hide Paddle again and break the Paddle product's whole purpose.
- Dunning timing that these products must produce, from the unchanged baseline: renewal due -> stays `arraysubs-active` for `grace_days_before_on_hold = 1` day -> `arraysubs-on-hold` -> `grace_days_before_cancel = 3` days -> `arraysubs-cancelled`. The renewal invoice is generated `invoice_before_due_value = 6` hours before due.

## Test data
| Item | Value |
|---|---|
| Product A | SLT Retry Daily / slug `slt-retry-daily`, $13.00, day/1 |
| Product B | SLT Paddle Daily / slug `slt-paddle-daily`, $11.00, day/1 |
| Account | A -> `slt-fail`; B -> `slt-paddle` |
| Coupon | N/A |
| Card | A: `4000 0000 0000 0341` (attaches fine, declines every off-session renewal); B: Paddle sandbox `4242 4242 4242 4242`, any future expiry |
| Amounts | A $13.00 first charge then failing $13.00 renewals; B $11.00 first charge then $11.00 renewals |

## Steps
1. Capture `mailpit-agent latest-id`.
2. Create Product A: `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin/post-new.php?post_type=product"`; title `SLT Retry Daily`; description `SLT window product. Stripe failing-card dunning path. Delete on 2026-08-11.`; **Simple product**; tick **Virtual**; tick **Subscription [ArraySubs]**; **General** tab **Regular price ($)** `13.00`.
3. Product A **Subscription [ArraySubs]** tab: **Billing Period** `Day`; **Billing Interval** `1`; **Subscription Length** `0`; **Trial Length** `0`; **Sign-up Fee ($)** empty; **Different Renewal Price** unticked; **Flexible Renewal Sync** UNTICKED. Slug `slt-retry-daily`. Publish.
4. Create Product B identically but title `SLT Paddle Daily`, description `SLT window product. Paddle sandbox only. Delete on 2026-08-11.`, **Regular price ($)** `11.00`, slug `slt-paddle-daily`. Publish.
5. Reload both and confirm the subscription fields persisted.
6. `wp post meta list <ID> --keys=_is_subscription,_subscription_period,_subscription_interval,_subscription_length,_trial_length,_signup_fee,_enable_renewal_price,_arraysubs_flex_sync_enabled,_regular_price --allow-root` for both.
7. As `--session guest`, open both product pages and confirm each renders a plain daily recurring summary with no trial and no fee.
8. Do NOT purchase in this task — the Paddle catalogue sync check and the gateway list check belong to SLT-SETUP-05, which depends on this task.
9. Append both IDs to the registry, tagging A as `stripe-decline-only` and B as `paddle-only`.

## Expected results
1. Both published simple + virtual + subscription, day/1, length 0, trial 0, no signup fee, no different renewal price, no flex sync meta.
2. `SLT Retry Daily` `_regular_price=13.00`; `SLT Paddle Daily` `_regular_price=11.00`.
3. Neither product carries `_arraysubs_flex_sync_enabled` (mandatory — otherwise Paddle would be hidden).
4. Both storefront pages show a plain "every day" recurring summary.
5. Dunning contract for A when bought on D0 with card `4000 0000 0000 0341`: parent order paid $13.00; the D1 renewal attempt fails; `payment_failed` mail to customer and admin; the subscription stays `arraysubs-active` for 1 day, moves to `arraysubs-on-hold`, and is `arraysubs-cancelled` 3 days after that — all inside the window.
6. Contract for B: bought with Paddle sandbox by `slt-paddle` only; Paddle owns the schedule via `next_billed_at`, so the Renew Early button must stay hidden even though `allow_early_renew` is on (`early_renewal: false`), and SCA/3DS is not applicable (`sca: false`).

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Both publishes | — | — | `mailpit-agent latest-id` unchanged from step 1 |

## Evidence to capture
- Screenshots: `SLT-PROD-16-01-retry-subscription-tab.png`, `SLT-PROD-16-02-paddle-subscription-tab.png`, `SLT-PROD-16-03-frontends.png`.
- Both product IDs; both meta dumps.

## Pass criteria
- [ ] Both published as plain day/1 subscriptions at $13.00 and $11.00
- [ ] Neither has flex sync, trial, fee or different renewal price
- [ ] Both storefront summaries show a plain daily schedule
- [ ] Registry tags each product with its exclusive gateway
- [ ] Zero mail, nothing purchased

## Isolation / teardown
- State handoff: `SLT Retry Daily` may ONLY be bought by `slt-fail` with card `4000 0000 0000 0341`, and must be bought on D0 or D1 so the full active -> on-hold -> cancelled ladder completes before D9. A second copy of the ladder using `4000 0000 0000 9995` (insufficient funds) may reuse the same product on a different day, but never on the same account concurrently. `SLT Paddle Daily` may ONLY be bought by `slt-paddle` with the Paddle sandbox card, and never with Stripe.
- Restores: nothing. Both deleted by SLT-SETUP-99.


---

# Flexible-renewal-sync audits and control products


## SLT-SYN-01 Audit simple-product Flexible Renewal Sync UI, validation and meta keys

*day D00 · priority critical · estimate 2h*

## Objective
Prove that the pro Flexible Renewal Sync control block on a SIMPLE subscription product exposes every documented control, that the segment slider/legend, the two POSITIONAL boundary hidden inputs and the three per-segment active toggles round-trip through a save, that the UI refuses to leave zero segments active, that out-of-range boundaries are clamped by `SegmentPlan::sanitizeBoundaries()` / `sanitizeSingleBoundary()` instead of being stored raw, and that exactly six meta keys are written and no others.

## Scope
- Gateway: N/A
- Checkout: N/A
- Account: N/A
- Plugins: pro-required

## Preconditions
- SLT-SETUP-01 (evidence root, registry page) and SLT-SETUP-02 (window baseline, global sync OFF) complete.
- SLT-PROD-12 (`SLT Flex Month Segments`, month/1 $30.00, seg1_end=2 seg2_end=6, all three active), SLT-PROD-13 (`SLT Flex Week Segments`, week/1 $14.00, seg1_end=2 seg2_end=5), SLT-PROD-14 (`SLT Flex Daily Two Seg` day/3 $9.00 seg1 OFF seg1_end=1; `SLT Flex Daily Next Cycle` day/3 $9.00 seg3 only) all created.
- Code facts (verified, do not re-derive): the panel is rendered by `arraysubspro/src/Features/FlexibleRenewalSync/views/simple-product-fields.php`; the six meta keys are `_arraysubs_flex_sync_enabled`, `_arraysubs_flex_sync_seg1_end`, `_arraysubs_flex_sync_seg2_end`, `_arraysubs_flex_sync_seg1_active`, `_arraysubs_flex_sync_seg2_active`, `_arraysubs_flex_sync_seg3_active`; the saver is `Hooks::persistFlexSyncMeta()` on `woocommerce_process_product_meta` priority 15; `SegmentPlan::MIN_CYCLE_DAYS = 3`; `getDefaultBoundaries(30)` returns `[10, 20]`; anything other than the literal string `no` in an `_active` meta counts as ACTIVE.
- This task MUST leave SLT-PROD-12/13/14 with their catalog-declared boundaries. Every probe below is followed by an explicit restore step.

## Test data
| Item | Value |
|---|---|
| Product | SLT Flex Month Segments (month/1, $30.00), SLT Flex Week Segments (week/1, $14.00), SLT Flex Daily Two Seg (day/3, $9.00), SLT Flex Daily Next Cycle (day/3, $9.00), SLT Fixed Three Cycles (day/2, $7.00 — sub-minimum control) |
| Account | admin / @GuDw(0$K7M9t8ehjqDb4Vwj |
| Coupon | N/A |
| Card | N/A |
| Amounts | none charged — admin-only task |

## Steps
1. Capture the mail baseline: `PREV=$(/usr/local/bin/mailpit-agent latest-id)` and record the value.
2. Record the pre-task meta of all four flex products so the restores are provable. From WP root `cd /home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public` run, for each product ID: `wp post meta list <ID> --keys=_arraysubs_flex_sync_enabled,_arraysubs_flex_sync_seg1_end,_arraysubs_flex_sync_seg2_end,_arraysubs_flex_sync_seg1_active,_arraysubs_flex_sync_seg2_active,_arraysubs_flex_sync_seg3_active --format=csv --allow-root` and tee to `/home/server-manager/slt-evidence/SLT-SYN-01-flex-meta-before.csv`.
3. `agent-browser skills get core`, then `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin/post.php?post=<SLT Flex Month Segments ID>&action=edit"` -> `agent-browser --session admin snapshot -i`.
4. Open the **Subscription [ArraySubs]** tab. Inventory every control in the **Flexible Renewal Sync to Next Billing Cycle** block and record its exact label: the master checkbox **Flexible Renewal Sync to Next Billing Cycle**; the description line "Align renewals to the billing-cycle boundary and pick how the first payment is charged based on the day of the cycle the customer signs up."; the slider container (`data-cycle-days`); the three legend rows with toggles and range text; the three segment labels **Full amount**, **Prorate amount**, **Charge full for next billing cycle**. Screenshot as `SLT-SYN-01-01-month-panel-inventory.png`.
5. Read `data-cycle-days` off the config container and confirm it is `30` for month/1. Re-check on the week product later (must be `7`) and on the day/3 products (must be `3`).
6. Confirm the two boundary inputs are HIDDEN inputs named `_arraysubs_flex_sync_seg1_end` and `_arraysubs_flex_sync_seg2_end` (they are driven by the slider, not typed) and that their current values are `2` and `6`.
7. Boundary-ordering probe A (inverted pair): drag the slider so the legend would read seg1 = `1 - 8`, seg2 = `9 - 12`, then use the browser to set the two hidden inputs directly to an INVERTED pair — `agent-browser --session admin eval "document.querySelector('.arraysubs-flex-sync-seg1-end').value='20';document.querySelector('.arraysubs-flex-sync-seg2-end').value='5';"` — and click **Update**. Re-open and read the stored metas.
8. Boundary-ordering probe B (out of cycle): repeat step 7 with `seg1_end='0'` and `seg2_end='45'` (both outside 1..29 for a 30-day nominal cycle), click **Update**, re-open and read the stored metas.
9. Boundary-ordering probe C (collapsing pair): repeat with `seg1_end='29'` and `seg2_end='29'`, **Update**, re-read.
10. RESTORE the month product: drag/set `seg1_end=2`, `seg2_end=6`, all three toggles ON, click **Update**, and confirm the legend reads `1 - 2` / `3 - 6` / `7 - 30`. Screenshot `SLT-SYN-01-02-month-restored.png`.
11. Last-active-segment refusal: on the month product turn the **Full amount** toggle OFF, then **Prorate amount** OFF, then attempt to turn **Charge full for next billing cycle** OFF. Capture the verbatim inline notice (expected: `At least one segment must stay active.`) and screenshot `SLT-SYN-01-03-last-active-refusal.png`. Do NOT save; navigate away with **Discard**/browser back and re-open to confirm the product is still 3-active.
12. Zero-active server-side fallback probe (defensive path, no UI): on `SLT Flex Month Segments` run `wp post meta update <ID> _arraysubs_flex_sync_seg1_active no --allow-root`, same for seg2 and seg3, then `wp eval 'print_r(\ArraySubsPro\Features\FlexibleRenewalSync\Services\SegmentPlan::getConfig(<ID>));' --allow-root`. Record the returned `actives` array. Immediately restore: `wp post meta update <ID> _arraysubs_flex_sync_seg1_active yes --allow-root` (and seg2, seg3).
13. Non-`no` string probe: `wp post meta update <ID> _arraysubs_flex_sync_seg1_active 0 --allow-root`, re-run the `getConfig()` eval, record whether segment 1 is still counted active, then restore to `yes`.
14. Two-active positional check on `SLT Flex Daily Two Seg`: open its edit screen, confirm the legend shows only TWO rows (`1` for **Prorate amount**, `2 - 3` for **Charge full for next billing cycle**) and that `_arraysubs_flex_sync_seg1_end` is `1` — i.e. the meta names the end of the FIRST ACTIVE segment, which is segment 2 here, NOT segment 1. Screenshot `SLT-SYN-01-04-two-active-positional.png`.
15. One-active check on `SLT Flex Daily Next Cycle`: confirm the legend collapses to a single row `1 - 3`, that no boundary handle is draggable, and run `wp eval 'print_r(\ArraySubsPro\Features\FlexibleRenewalSync\Services\SegmentPlan::getConfig(<ID>));' --allow-root` to confirm `boundaries` is an EMPTY array. Screenshot `SLT-SYN-01-05-one-active.png`.
16. Sub-minimum-cycle check: open `SLT Fixed Three Cycles` (day/2 = nominal 2 days). Confirm the Flexible Renewal Sync block is present but that ticking it and saving yields `getConfig()` = `null`: `wp eval 'var_dump(\ArraySubsPro\Features\FlexibleRenewalSync\Services\SegmentPlan::getConfig(<ID>));' --allow-root`. Do NOT save the tick — untick it and leave the product exactly as SLT-PROD-06 created it (verify `_arraysubs_flex_sync_enabled` is absent afterwards).
17. Disable-retains-boundaries probe: on `SLT Flex Week Segments` untick the master checkbox and **Update**. Read the metas. Then re-tick and **Update**, and confirm the legend redraws `1 - 2` / `3 - 5` / `6 - 7` without re-entering the boundaries. Screenshot `SLT-SYN-01-06-week-reenabled.png`.
18. Final verification dump: repeat step 2's command for all four products into `/home/server-manager/slt-evidence/SLT-SYN-01-flex-meta-after.csv` and `diff` it against the before file.
19. `/usr/local/bin/mailpit-agent latest-id` — must equal `$PREV`.
20. `agent-browser close --all`.

## Expected results
1. The month product's config container carries `data-cycle-days="30"`; the week product `7`; both day/3 products `3`.
2. Probe A (seg1_end=20, seg2_end=5, inverted): the stored pair is re-derived/clamped — `sanitizeBoundaries(20,5,30)` is entered because `seg2_end <= seg1_end`, yielding `_arraysubs_flex_sync_seg1_end=20` and `_arraysubs_flex_sync_seg2_end=21`. Neither value is stored raw as submitted, and `seg2_end > seg1_end` always holds.
3. Probe B (0 and 45): both are out of range, so `getDefaultBoundaries(30)` supplies `_arraysubs_flex_sync_seg1_end=10` and `_arraysubs_flex_sync_seg2_end=20`.
4. Probe C (29 and 29): clamped to `seg1_end=28`, `seg2_end=29` — every one of the three partitions retains at least one day and `seg2_end <= cycle_days - 1 = 29`.
5. After step 10 the month product is back to `seg1_end=2`, `seg2_end=6`, all three `_active` metas `yes`, and the legend reads `1 - 2` / `3 - 6` / `7 - 30`.
6. Turning off the last remaining active segment is refused in the UI with the verbatim string `At least one segment must stay active.` and no save occurs.
7. Step 12: with all three `_active` metas set to `no`, `SegmentPlan::getConfig()` returns `actives => [1, 2, 3]` (defensive fallback) rather than null or an empty array.
8. Step 13: `_arraysubs_flex_sync_seg1_active = 0` still counts as ACTIVE, because only the literal string `no` deactivates a segment. Record this as a documented sharp edge.
9. `SLT Flex Daily Two Seg` shows exactly two legend rows, `1` (Prorate amount) and `2 - 3` (Charge full for next billing cycle), and `_arraysubs_flex_sync_seg1_end = 1` is the end of the first ACTIVE segment (segment 2) — positional, not segment-named.
10. `SLT Flex Daily Next Cycle` shows one legend row `1 - 3` and `getConfig()['boundaries']` is `[]`.
11. `SLT Fixed Three Cycles` (nominal 2 days < MIN_CYCLE_DAYS 3) yields `getConfig() === null` even with the checkbox ticked, and is left with `_arraysubs_flex_sync_enabled` ABSENT.
12. Unticking the master checkbox DELETES `_arraysubs_flex_sync_enabled` but RETAINS `_arraysubs_flex_sync_seg1_end`/`seg2_end`; re-ticking restores the same legend with no re-entry.
13. The step-18 diff against step 2 is empty for all four products — every probe was restored.
14. Exactly the six documented meta keys exist on each flex product; no additional `_arraysubs_flex_*` key appears.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Every product save and every WP-CLI meta write in this task | — | — | `/usr/local/bin/mailpit-agent latest-id` at step 19 must equal `$PREV` captured at step 1; if it moved, `mailpit-agent show latest` and record which save leaked mail |

## Evidence to capture
- Screenshots: `SLT-SYN-01-01-month-panel-inventory.png`, `SLT-SYN-01-02-month-restored.png`, `SLT-SYN-01-03-last-active-refusal.png`, `SLT-SYN-01-04-two-active-positional.png`, `SLT-SYN-01-05-one-active.png`, `SLT-SYN-01-06-week-reenabled.png`.
- `/home/server-manager/slt-evidence/SLT-SYN-01-flex-meta-before.csv` and `-after.csv` plus the diff output (expected empty).
- The three probe meta readbacks (A/B/C) verbatim, the `getConfig()` print_r output for steps 12/13/15/16, the verbatim last-active notice string.
- Console errors from `flexibleRenewalSyncAdmin.js` while dragging the slider.
- `$PREV` value.

## Pass criteria
- [ ] All controls inventoried with exact labels and `data-cycle-days` correct per product
- [ ] Inverted boundary pair is clamped, not stored raw
- [ ] Out-of-range pair falls back to getDefaultBoundaries(30) = 10 / 20
- [ ] Collapsing pair is clamped so every partition keeps >= 1 day
- [ ] Last-active-segment refusal captured verbatim
- [ ] Zero-active meta state resolves to actives [1,2,3]
- [ ] Non-`no` value counts as active (documented)
- [ ] Two-active product proves META_SEG1_END is POSITIONAL
- [ ] One-active product has empty boundaries array
- [ ] Sub-3-day cycle yields getConfig() === null
- [ ] Disable retains boundaries; re-enable restores the legend
- [ ] Before/after meta diff is empty; zero mail

## Isolation / teardown
- State handoff: the confirmed, restored segment plans for SLT-PROD-12/13/14 are the baseline every later SLT-SYN purchase task asserts against. The positional-meta finding from step 14 is binding on SLT-SYN-07.
- Restores: all four flex products returned to their SLT-PROD-declared configuration (proved by the empty diff); `SLT Fixed Three Cycles` left with no flex meta. No global setting touched. Nothing deleted.


## SLT-SYN-02 Audit variation-level Flexible Renewal Sync UI, [$loop] meta and per-variation independence

*day D00 · priority critical · estimate 1h 30m*

## Objective
Prove that the variation-level Flexible Renewal Sync block is a genuinely separate code path from the simple-product one — rendered on `arraysubs_subscription_variation_fields_before_shipping`, submitted as `META[$loop]` arrays, saved on `woocommerce_save_product_variation` priority 15 — and that three variations of ONE variable product hold three INDEPENDENT segment plans that survive the variation AJAX save, a page reload, and a reorder of the variation list. Also prove the parent product carries no flex meta of its own, so nothing can silently fall back to a parent plan.

## Scope
- Gateway: N/A
- Checkout: N/A
- Account: N/A
- Plugins: pro-required

## Preconditions
- SLT-SETUP-01, SLT-SETUP-02 and SLT-PROD-15 (`SLT Flex Variable Daily`, attribute `SLT Sync Mode`, variations **Full** / **Next Cycle** / **No Sync**, all day/3 at $12.00) complete.
- SLT-SYN-01 complete — its findings on positional metas and the `no`-only deactivation rule apply identically here.
- Code facts (verified): the view is `arraysubspro/src/Features/FlexibleRenewalSync/views/variation-fields.php`; field names are `_arraysubs_flex_sync_enabled[<loop>]`, `_arraysubs_flex_sync_seg1_end[<loop>]`, `_arraysubs_flex_sync_seg2_end[<loop>]`, `_arraysubs_flex_sync_seg{1,2,3}_active[<loop>]`; unticking the master box DELETES `_arraysubs_flex_sync_enabled` on that variation rather than writing `no`; `filterSupportsRenewalSync()` and `filterRenewalSyncContext()` both resolve `subscription_data['product_id']` to the VARIATION id for a variation purchase.
- SLT-PROD-15 declared: **Full** = all three active, seg1_end 1, seg2_end 2; **Next Cycle** = segment 3 only; **No Sync** = flex unticked. This task must leave exactly that state.

## Test data
| Item | Value |
|---|---|
| Product | SLT Flex Variable Daily (parent) + variations Full / Next Cycle / No Sync, all day/3 $12.00 |
| Account | admin / @GuDw(0$K7M9t8ehjqDb4Vwj |
| Coupon | N/A |
| Card | N/A |
| Amounts | none charged — admin-only task |

## Steps
1. `PREV=$(/usr/local/bin/mailpit-agent latest-id)`; record it.
2. From WP root `cd /home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public` capture the before-state for the parent and all three variations: `wp post meta list <ID> --keys=_arraysubs_flex_sync_enabled,_arraysubs_flex_sync_seg1_end,_arraysubs_flex_sync_seg2_end,_arraysubs_flex_sync_seg1_active,_arraysubs_flex_sync_seg2_active,_arraysubs_flex_sync_seg3_active,_subscription_period,_subscription_interval --format=csv --allow-root`, tee all four into `/home/server-manager/slt-evidence/SLT-SYN-02-variation-meta-before.csv`.
3. `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin/post.php?post=<SLT Flex Variable Daily PARENT ID>&action=edit"` -> `agent-browser --session admin snapshot -i` -> open the **Variations** tab and expand all three variations.
4. Confirm the Flexible Renewal Sync block appears INSIDE each variation panel, positioned after that variation's **Different Renewal Price** section, and that it does NOT appear anywhere in the parent-level **Subscription [ArraySubs]** area. Screenshot `SLT-SYN-02-01-three-variations-expanded.png`.
5. Read the rendered field names for the **Full** variation with `agent-browser --session admin eval "Array.from(document.querySelectorAll('[name*=arraysubs_flex_sync]')).map(e=>e.name+'='+e.value).join('\n')"` and record them. Confirm every name carries a `[<loop>]` index and that the three variations use three DISTINCT loop indices.
6. Confirm each variation's config container carries `data-cycle-days="3"` (day/3 nominal), and that the **Full** legend reads `1` / `2` / `3`, the **Next Cycle** legend reads `1 - 3` with a single row, and the **No Sync** variation shows the master checkbox UNTICKED with the config block hidden. Screenshot `SLT-SYN-02-02-legends.png`.
7. Independence probe: on the **Full** variation ONLY, turn the **Prorate amount** toggle OFF (leaving segments 1 and 3 active) and click **Save changes** on the Variations tab. Wait for the AJAX save to settle, then reload the edit screen and re-expand all three.
8. Read all three variations' metas again via the step-2 command. Confirm ONLY the **Full** variation changed and that `Next Cycle` and `No Sync` are byte-identical to the before file.
9. RESTORE: turn the **Prorate amount** toggle on the **Full** variation back ON, set the boundaries so the legend reads `1` / `2` / `3`, click **Save changes**, reload, and confirm.
10. Cross-write probe: set the **Next Cycle** variation's segment-3 toggle OFF and its segment-1 toggle ON (so it becomes 1-active on segment 1), **Save changes**, reload, and read the metas of all three. Then RESTORE **Next Cycle** to segment-3-only (seg1 OFF, seg2 OFF, seg3 ON), **Save changes**, reload, verify.
11. No-Sync deletion semantics: on the **No Sync** variation tick the master checkbox, **Save changes**, reload and read `_arraysubs_flex_sync_enabled` (expect `yes`). Then untick it, **Save changes**, reload and read again. Record whether the key is DELETED or set to a value.
12. Parent-leakage check: `wp post meta list <PARENT ID> --allow-root | grep arraysubs_flex` — expect no output.
13. Variation-id resolution proof (no purchase): for each of the three variation IDs run `wp eval 'use ArraySubsPro\Features\FlexibleRenewalSync\Services\SegmentPlan; $id=<VARIATION ID>; var_dump(SegmentPlan::isEnabled($id)); print_r(SegmentPlan::getConfig($id)); print_r(SegmentPlan::getPartition(SegmentPlan::getConfig($id) ?: ["cycle_days"=>3,"actives"=>[],"boundaries"=>[]]));' --allow-root` and record the output.
14. Segment resolution matrix per variation: `wp eval 'use ArraySubsPro\Features\FlexibleRenewalSync\Services\SegmentPlan; $c=SegmentPlan::getConfig(<VARIATION ID>); if(!$c){echo "null\n"; return;} foreach([1,2,3] as $d) echo "day $d -> segment ".SegmentPlan::resolveSegment($d,$c)." mode ".SegmentPlan::getSegmentMode(SegmentPlan::resolveSegment($d,$c))."\n";' --allow-root` for all three variations.
15. Final dump into `/home/server-manager/slt-evidence/SLT-SYN-02-variation-meta-after.csv` and `diff` against the before file.
16. `/usr/local/bin/mailpit-agent latest-id` must equal `$PREV`. `agent-browser close --all`.

## Expected results
1. The Flexible Renewal Sync block renders once per variation, inside the variation panel, and is absent from the parent Subscription tab.
2. Every submitted field name is loop-indexed: `_arraysubs_flex_sync_enabled[0]`, `_arraysubs_flex_sync_seg1_end[0]`, `_arraysubs_flex_sync_seg2_end[0]`, `_arraysubs_flex_sync_seg1_active[0]`, `_arraysubs_flex_sync_seg2_active[0]`, `_arraysubs_flex_sync_seg3_active[0]` (and the same with indices 1 and 2 for the other two variations). Three distinct indices are present.
3. All three config containers report `data-cycle-days="3"`.
4. Legends: **Full** = `1` / `2` / `3` (three rows); **Next Cycle** = `1 - 3` (one row); **No Sync** = master checkbox unticked, config block hidden.
5. Step 7-8: turning off segment 2 on **Full** changes ONLY that variation's metas — `Next Cycle` and `No Sync` rows are byte-identical to the before file. This proves `$_POST[META][$loop]` indexing does not bleed across variations.
6. Step 10: reconfiguring **Next Cycle** to segment-1-only likewise leaves **Full** and **No Sync** untouched.
7. Step 11: after unticking, `_arraysubs_flex_sync_enabled` is DELETED from the **No Sync** variation (the key is absent from `wp post meta list`), not stored as `no`; any previously submitted `seg1_end`/`seg2_end` values are retained.
8. Step 12: the PARENT product carries no `_arraysubs_flex_*` meta at all.
9. Step 13: `SegmentPlan::getConfig()` returns a non-null config for **Full** (`actives [1,2,3]`, `boundaries [1,2]`, `cycle_days 3`) and for **Next Cycle** (`actives [3]`, `boundaries []`, `cycle_days 3`), and returns `null` for **No Sync**.
10. Step 14 matrix — **Full**: day 1 -> segment 1 mode `full`; day 2 -> segment 2 mode `prorate`; day 3 -> segment 3 mode `next_cycle`. **Next Cycle**: days 1, 2 and 3 all -> segment 3 mode `next_cycle`. **No Sync**: `null`.
11. The step-15 diff against the before file is EMPTY — every probe was restored.
12. No AJAX/console error appears during any **Save changes** click.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Every variation AJAX save and every WP-CLI read in this task | — | — | `/usr/local/bin/mailpit-agent latest-id` at step 16 must equal `$PREV` from step 1 |

## Evidence to capture
- Screenshots: `SLT-SYN-02-01-three-variations-expanded.png`, `SLT-SYN-02-02-legends.png`, `SLT-SYN-02-03-full-seg2-off-independent.png`, `SLT-SYN-02-04-nosync-key-deleted.png`.
- The step-5 field-name dump verbatim (proving the `[<loop>]` indexing and three distinct indices).
- `SLT-SYN-02-variation-meta-before.csv`, `-after.csv`, and the diff (expected empty).
- The `getConfig()` / `getPartition()` / `resolveSegment()` output for all three variations.
- Parent ID, three variation IDs, `$PREV`, and any AJAX errors from the Variations tab network log.

## Pass criteria
- [ ] Flex block renders per variation and never on the parent
- [ ] All field names are `[<loop>]`-indexed with three distinct indices
- [ ] data-cycle-days is 3 on all three variations
- [ ] Editing one variation leaves the other two byte-identical (both directions probed)
- [ ] Unticking deletes `_arraysubs_flex_sync_enabled` and retains the boundaries
- [ ] Parent carries no flex meta
- [ ] getConfig() non-null for Full and Next Cycle, null for No Sync
- [ ] Segment/mode matrix matches Full 1/2/3 and Next Cycle all-3
- [ ] Before/after diff empty; zero mail; zero AJAX errors

## Isolation / teardown
- State handoff: the three verified variation configs are the contract SLT-SYN-08 buys against. If SLT-SYN-08 later observes identical next-payment dates for **Full** and **Next Cycle**, this task's evidence is what proves the fault is in `filterRenewalSyncContext()` product_id resolution and not in the stored configuration.
- Restores: all three variations returned to SLT-PROD-15's declared configuration (proved by the empty diff). No global setting touched. Nothing purchased, nothing deleted.


## SLT-SYN-03 Create the two sync-group control products: SLT Sync Global Daily and SLT Sync Excl Probe

*day D00 · priority critical · estimate 45m*

## Objective
Create the two NEW products this dimension owns, because the canonical catalog deliberately reserves its only month product and its only week product for flexible sync and therefore leaves two gaps: (a) there is no NON-flex product with a cycle of 3+ days, which is required to isolate what PLAIN global `sync_to_billing_cycle` does on its own, and (b) there is no product carrying a flex-exclusivity trigger that this group is allowed to buy, since `SLT Renewal Price Step` belongs to `slt-core`. Both products are marked NEW here and created with explicit steps; no existing catalog product is modified.

## Scope
- Gateway: Stripe test
- Checkout: N/A (creation only)
- Account: N/A
- Plugins: free-only

## Preconditions
- SLT-SETUP-01 (conventions: `SLT <Name>` title, `slt-<name>` slug, Virtual, stock management off) and SLT-SETUP-02 (global sync OFF baseline) complete.
- SLT-PROD-14 complete — `SLT Sync Global Daily` deliberately mirrors its day/3 cycle so it is an exact non-flex control for `SLT Flex Daily Two Seg` and `SLT Flex Daily Next Cycle`.
- Both products are declared NEW by this task. No other group may buy them.
- Code facts (verified): `SegmentPlan::getNominalCycleDays('day', 3) = 3`, which is exactly `MIN_CYCLE_DAYS`, so a day/3 product IS segmentable; `SegmentPlan::getConfig()` returns `null` whenever `_enable_renewal_price === 'yes'`, which is what makes the second product a valid exclusivity canvas.

## Test data
| Item | Value |
|---|---|
| Product A | SLT Sync Global Daily / slug `slt-sync-global-daily` — Simple, Virtual, subscription, day/3, length 0, trial 0, no signup fee, NO flex sync |
| Product B | SLT Sync Excl Probe / slug `slt-sync-excl-probe` — Simple, Virtual, subscription, day/3, length 0, trial 0, no signup fee, **Different Renewal Price** ON |
| Account | admin / @GuDw(0$K7M9t8ehjqDb4Vwj |
| Coupon | N/A |
| Card | N/A |
| Amounts | A: regular price $18.00, expected first charge $18.00, renewal $18.00 every 3 days. B: regular price $16.00, renewal price $24.00 after 2 billing periods, expected first charge $16.00 |

## Steps
1. `PREV=$(/usr/local/bin/mailpit-agent latest-id)`; record it.
2. `agent-browser skills get core` if not already loaded this session, then `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin/post-new.php?post_type=product"` -> `agent-browser --session admin snapshot -i`.
3. **Product title**: `SLT Sync Global Daily`. **Description**: `SLT window product. Non-flex day/3 control for global renewal sync. Owned by SLT-SYN. Delete on 2026-08-11.`
4. Product type **Simple product**; tick **Virtual**; leave **Downloadable** unticked; tick the header checkbox **Subscription [ArraySubs]**.
5. **General** tab: **Regular price ($)** = `18.00`; leave **Sale price** empty.
6. **Subscription [ArraySubs]** tab: **Billing Period** = `Day`; **Billing Interval** = `3`; **Subscription Length** = `0`; **Trial Length** = `0`; **Trial Period** = `Day`; **Sign-up Fee ($)** empty; **Different Renewal Price** UNTICKED.
7. Confirm the **Flexible Renewal Sync to Next Billing Cycle** checkbox IS offered (day/3 = 3 nominal days = exactly `MIN_CYCLE_DAYS`) and leave it UNTICKED. Screenshot `SLT-SYN-03-01-global-daily-flex-offered-unticked.png` — this is the evidence that its absence of sync in later tasks is a configuration choice, not a UI limitation.
8. **Inventory** tab: leave **Manage stock?** unticked, **Stock status** = In stock.
9. Set the URL slug to `slt-sync-global-daily`. **Publish**. Reload the edit screen and re-verify every field.
10. New product: **Product title** `SLT Sync Excl Probe`. **Description**: `SLT window product. Day/3 with Different Renewal Price — flex-sync exclusivity canvas. Owned by SLT-SYN. Delete on 2026-08-11.`
11. **Simple product**; tick **Virtual**; tick **Subscription [ArraySubs]**. **General** tab **Regular price ($)** = `16.00`.
12. **Subscription [ArraySubs]** tab: **Billing Period** = `Day`; **Billing Interval** = `3`; **Subscription Length** = `0`; **Trial Length** = `0`; **Sign-up Fee ($)** empty.
13. BEFORE ticking the renewal-price box, screenshot the panel showing the **Flexible Renewal Sync to Next Billing Cycle** checkbox present: `SLT-SYN-03-02-excl-probe-flex-visible-before.png`.
14. Tick **Different Renewal Price**; in the revealed block set **Renewal Price ($)** = `24.00` and **Apply Renewal Price After** = `2`.
15. Re-snapshot and screenshot: the **Flexible Renewal Sync** section must now be HIDDEN. Save as `SLT-SYN-03-03-excl-probe-flex-hidden-after.png`.
16. Slug `slt-sync-excl-probe`. **Publish**. Reload and re-verify.
17. Verify metas from WP root `cd /home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public`:
   - `wp post meta list <SLT Sync Global Daily ID> --keys=_is_subscription,_subscription_period,_subscription_interval,_subscription_length,_trial_length,_signup_fee,_enable_renewal_price,_arraysubs_flex_sync_enabled,_regular_price --allow-root`
   - `wp post meta list <SLT Sync Excl Probe ID> --keys=_is_subscription,_subscription_period,_subscription_interval,_subscription_length,_trial_length,_enable_renewal_price,_renewal_price,_renewal_price_after,_arraysubs_flex_sync_enabled,_regular_price --allow-root`
18. Confirm the segment-plan resolver agrees: `wp eval 'use ArraySubsPro\Features\FlexibleRenewalSync\Services\SegmentPlan; var_dump(SegmentPlan::getNominalCycleDays("day",3)); var_dump(SegmentPlan::getConfig(<SLT Sync Global Daily ID>)); var_dump(SegmentPlan::getConfig(<SLT Sync Excl Probe ID>));' --allow-root`.
19. As `--session guest`, open `https://mirror-help.arrayhash.com/slt-sync-global-daily` and `https://mirror-help.arrayhash.com/slt-sync-excl-probe`, confirm each renders a recurring "every 3 days" schedule summary, and do NOT add anything to the cart.
20. Append both product IDs to the `slt-catalog-registry` page, tagging A as `sync-group non-flex control` and B as `sync-group exclusivity canvas`.
21. `/usr/local/bin/mailpit-agent latest-id` must equal `$PREV`. `agent-browser close --all`.

## Expected results
1. `SLT Sync Global Daily` published: type `simple`, virtual, slug exactly `slt-sync-global-daily`, `_is_subscription=yes`, `_subscription_period=day`, `_subscription_interval=3`, `_subscription_length=0`, `_trial_length=0`, `_signup_fee` absent or `0`, `_enable_renewal_price` absent, `_regular_price=18.00`, and `_arraysubs_flex_sync_enabled` ABSENT.
2. The Flexible Renewal Sync checkbox is visibly OFFERED on product A and deliberately left unticked (screenshot captured).
3. `SLT Sync Excl Probe` published: slug `slt-sync-excl-probe`, `_subscription_period=day`, `_subscription_interval=3`, `_enable_renewal_price=yes`, `_renewal_price=24`, `_renewal_price_after=2`, `_regular_price=16.00`, `_arraysubs_flex_sync_enabled` ABSENT.
4. On product B the Flexible Renewal Sync section visibly disappears the moment **Different Renewal Price** is ticked.
5. `SegmentPlan::getNominalCycleDays('day', 3)` returns `3`.
6. `SegmentPlan::getConfig()` returns `NULL` for BOTH products — for A because the feature was never enabled, for B because of the renewal-price exclusivity. Both nulls are expected and are recorded with their distinct reasons.
7. Both storefront pages render an "every 3 days" recurring summary; product B additionally advertises the stepped renewal price.
8. Neither product was published with an admin error notice; both are status `publish`.
9. Baseline billing contract for later tasks, with global sync OFF (SLT-SETUP-02 baseline): product A bought at checkout time T renews at `T + 3 days` (anniversary time, NOT site-local midnight). With global sync ON, the same purchase on 2026-08-01 instead yields `_renewal_sync_cycle_start_date = 2026-07-31 18:00:00` UTC and `_next_payment_date = 2026-08-03 18:00:00` UTC (= 2026-08-04 00:00 site).

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Both product publishes and the storefront views (nothing added to cart, no order placed) | — | — | `/usr/local/bin/mailpit-agent latest-id` at step 21 must equal `$PREV` from step 1 |

## Evidence to capture
- Screenshots: `SLT-SYN-03-01-global-daily-flex-offered-unticked.png`, `SLT-SYN-03-02-excl-probe-flex-visible-before.png`, `SLT-SYN-03-03-excl-probe-flex-hidden-after.png`, `SLT-SYN-03-04-frontends.png`.
- Both product IDs; both `wp post meta list` outputs; the step-18 `wp eval` output showing `3` and two `NULL`s.
- Registry page rows for both products; `$PREV`.

## Pass criteria
- [ ] SLT Sync Global Daily published day/3 at $18.00 with no flex meta and no renewal-price meta
- [ ] Flex checkbox visibly offered on product A and left unticked (evidence captured)
- [ ] SLT Sync Excl Probe published day/3 at $16.00 with renewal price 24.00 after 2
- [ ] Flex section visibly hidden by Different Renewal Price on product B
- [ ] getNominalCycleDays('day',3) === 3 and getConfig() NULL for both products
- [ ] Both front ends show "every 3 days"
- [ ] Both IDs appended to slt-catalog-registry with owner tags
- [ ] Zero mail; nothing added to cart; no existing product touched

## Isolation / teardown
- State handoff: `SLT Sync Global Daily` is bought exactly ONCE, by SLT-SYN-04, as `slt-flex`, while global sync is temporarily ON. `SLT Sync Excl Probe` is bought exactly ONCE, by SLT-SYN-09, as `slt-flex`, after its flex meta has been force-set by WP-CLI — proving the checkout pipeline refuses a plan the admin UI would never have let you create. No other task may purchase either product.
- Cross-purpose note recorded deliberately: both products are bought by `slt-flex` even though they are not flexible-sync products, because they exist solely to serve this dimension's controls and `slt-core` is reserved for the checkout group's workhorse purchases. This is the only account-purpose deviation in the sync dimension and is declared here once.
- Restores: nothing global changed. Both products are deleted by SLT-SETUP-99 (they match the `SLT ` title prefix, so the existing product-search teardown already covers them).


## SLT-SYN-04 Prove global sync_to_billing_cycle=true + first_charge_mode=full, and that flex overrides it

*day D00 · priority critical · estimate 2h*

## Objective
Prove what PLAIN global renewal sync does on its own — with `renewals.sync_to_billing_cycle = true` and `renewals.sync_first_charge_mode = "full"`, a subscription's first renewal is snapped to the site-local calendar boundary instead of the checkout anniversary, and the first charge is the FULL recurring amount regardless of how far into the cycle the customer buys — and then prove that a per-product flexible segment plan OVERRIDES that global mode, so a segment-2 purchase prorates even while the global mode says `full`. This is the only task in the window permitted to turn global sync on, and it restores it in the same task.

## Scope
- Gateway: Stripe test
- Checkout: block
- Account: existing (slt-flex)
- Plugins: both

## Preconditions
- SLT-SETUP-02 complete; the window baseline is `renewals.sync_to_billing_cycle = false` and `renewals.sync_first_charge_mode = "full"`. This task temporarily flips ONLY the first of those and restores it before finishing.
- SLT-SETUP-03 complete (account `slt-flex` / `slt-flex@example.test` / `SltQa!2026#Pass`, billing address populated).
- SLT-SETUP-05 complete (gateway capability matrix recorded).
- SLT-SYN-03 complete (`SLT Sync Global Daily`, day/3, $18.00, no flex).
- SLT-PROD-12 complete (`SLT Flex Month Segments`, month/1, $30.00, seg1_end=2 seg2_end=6).
- MANDATORY ordering: this task runs FIRST among the day-0 sync purchase tasks. SLT-SYN-05 through SLT-SYN-08 depend on it precisely so that global sync is proven back OFF before any flex purchase is made.
- Verified date facts (do NOT re-derive): site is UTC+6; site-local midnight 2026-08-01 == `2026-07-31 18:00:00` UTC. For a day/3 purchase on 2026-08-01, global sync yields `cycle_start_date = 2026-07-31 18:00:00` UTC and `next_payment_date = 2026-08-03 18:00:00` UTC (= 2026-08-04 00:00 site).
- Verified gateway fact: turning global sync ON HIDES Paddle from every sync-eligible cart (`arraysubs_is_renewal_sync_supported_gateway('arraysubs_paddle')` is hard-coded false). That is expected here and is re-confirmed as a checkpoint, not a defect.

## Test data
| Item | Value |
|---|---|
| Product | SLT Sync Global Daily (day/3, $18.00, no flex); SLT Flex Month Segments (month/1, $30.00) — probe only, not purchased here |
| Account | slt-flex / slt-flex@example.test / `SltQa!2026#Pass` |
| Coupon | N/A |
| Card | 4242 4242 4242 4242, any future expiry, any CVC, any postcode |
| Amounts | expected charge today $18.00 (FULL, not prorated); expected renewal $18.00 every 3 days on the site-local midnight boundary |

## Steps
1. Record the prior value in this task's Notes and on disk: `cd /home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public && wp option get arraysubs_settings --format=json --allow-root > /home/server-manager/slt-evidence/SLT-SYN-04-settings-before.json`. Confirm `renewals.sync_to_billing_cycle` is currently `false` and `renewals.sync_first_charge_mode` is `"full"`.
2. `PREV=$(/usr/local/bin/mailpit-agent latest-id)`; record it.
3. `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/settings/general"` -> `agent-browser --session admin snapshot -i`. In the **Renewal Sync** card switch **Sync Renewals to Next Billing Cycle** ON. Confirm the **First Charge** select reappears reading `Charge the full recurring amount` and do NOT change it. Save; screenshot `SLT-SYN-04-01-global-sync-on.png`.
4. Verify from WP root: `wp option get arraysubs_settings --allow-root | grep -o 'sync_to_billing_cycle";b:[01]'` (expect `b:1`) and `wp eval 'var_dump(arraysubs_is_renewal_sync_enabled(), arraysubs_get_renewal_sync_first_charge_mode());' --allow-root` (expect `true` and `"full"`).
5. PROBE BLOCK — flex overrides the global mode, no purchase required. Run:
   `wp eval '$d=["product_id"=><SLT Flex Month Segments ID>,"period"=>"month","interval"=>1,"trial_length"=>0,"price"=>30.0,"signup_fee"=>0]; foreach(["2026-08-01 03:00:00","2026-08-04 03:00:00","2026-08-08 03:00:00"] as $s){$c=arraysubs_get_renewal_sync_context($d,1,$s,"stripe"); printf("%s applies=%s mode=%-10s seg=%s day=%s unit=%.2f line=%.2f cs=%s np=%s\n",$s,var_export($c["applies"],true),$c["mode"],$c["flex_segment"]??"-",$c["flex_day_in_cycle"]??"-",$c["initial_unit_amount"],$c["initial_line_amount"],$c["cycle_start_date"],$c["next_payment_date"]);}' --allow-root`
   Tee the output to `/home/server-manager/slt-evidence/SLT-SYN-04-flex-override-globalON.txt`.
6. Run the SAME probe against a hypothetical NON-flex month product by passing `"product_id"=>0` (which makes `filterRenewalSyncContext()` bail immediately) and tee to `/home/server-manager/slt-evidence/SLT-SYN-04-plain-global-globalON.txt`. This is the side-by-side that isolates the override.
7. Gateway checkpoint: `agent-browser --session guest open "https://mirror-help.arrayhash.com/checkout/?add-to-cart=<SLT Sync Global Daily ID>"` -> `agent-browser --session guest snapshot -i`. Record which gateways the payment accordion offers. Empty the cart afterwards via `https://mirror-help.arrayhash.com/cart/`.
8. Log in as the customer: `agent-browser --session customer open "https://mirror-help.arrayhash.com/my-account"` -> `snapshot -i` -> sign in as `slt-flex` / `SltQa!2026#Pass`.
9. Snapshot the mail id again immediately before the purchase: `PREBUY=$(/usr/local/bin/mailpit-agent latest-id)`.
10. `agent-browser --session customer open "https://mirror-help.arrayhash.com/checkout/?add-to-cart=<SLT Sync Global Daily ID>"` -> `snapshot -i`. Read and screenshot the order summary BEFORE paying: `SLT-SYN-04-02-checkout-summary-global.png`. Record the exact "total due today" string and any subscription schedule line.
11. Select **Stripe** in the payment accordion, enter card `4242 4242 4242 4242`, and place the order. Re-snapshot the order-received page and record the order number.
12. `/usr/local/bin/mailpit-agent wait-new "$PREBUY" 60 "is active"` and then `/usr/local/bin/mailpit-agent list 15` — record every message id produced by the purchase.
13. Find the subscription: `wp post list --post_type=arraysubs_data --format=csv --fields=ID,post_title,post_status --allow-root | tail -20` and identify the new subscription for `slt-flex`. Record `SUBID_GLOBAL`.
14. Dump its sync meta: `wp post meta list <SUBID_GLOBAL> --keys=_renewal_sync_enabled,_renewal_sync_first_charge_mode,_renewal_sync_cycle_start_date,_renewal_sync_first_full_renewal_date,_renewal_sync_initial_recurring_amount,_next_payment_date,_recurring_amount,_billing_period,_billing_interval,_payment_gateway --allow-root`.
15. Dump the ORDER ITEM mirror of the same data: open the order at `https://mirror-help.arrayhash.com/wp-admin/admin.php?page=wc-orders&action=edit&id=<ORDER ID>` and screenshot the line-item meta rows: `SLT-SYN-04-03-order-item-sync-meta.png`.
16. RESTORE THE BASELINE NOW, before anything else: `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/settings/general"`, switch **Sync Renewals to Next Billing Cycle** back OFF, Save, and screenshot `SLT-SYN-04-04-global-sync-restored-off.png`.
17. Prove the restore: `wp option get arraysubs_settings --format=json --allow-root > /home/server-manager/slt-evidence/SLT-SYN-04-settings-after.json` then `diff <(jq -S . /home/server-manager/slt-evidence/SLT-SYN-04-settings-before.json) <(jq -S . /home/server-manager/slt-evidence/SLT-SYN-04-settings-after.json)`.
18. Re-confirm the gateway list is back to both after the restore: repeat step 7 with a guest cart and record the accordion contents, then empty the cart.
19. `agent-browser close --all`.

## Expected results
1. With global sync ON, `arraysubs_is_renewal_sync_enabled()` is `true` and `arraysubs_get_renewal_sync_first_charge_mode()` is the string `full`.
2. Step 6 (plain global, `product_id = 0`, month/1 $30.00): ALL THREE start dates produce `applies=true`, `mode=full`, `initial_unit_amount=30.00`, `cycle_start_date=2026-07-31 18:00:00`, `next_payment_date=2026-08-31 18:00:00`. Global sync alone charges the full amount whether you buy on day 1, day 4 or day 8, and always lands the first renewal on 2026-09-01 00:00 site.
3. Step 5 (same dates, but `product_id` = the flex month product): the flexible plan OVERRIDES the global mode —
   - `2026-08-01 03:00:00` -> `flex_day_in_cycle=1`, `flex_segment=1`, `mode=full`, unit `$30.00`, `next_payment_date=2026-08-31 18:00:00`.
   - `2026-08-04 03:00:00` -> `flex_day_in_cycle=4`, `flex_segment=2`, `mode=prorate`, unit `$26.13`, `next_payment_date=2026-08-31 18:00:00`.
   - `2026-08-08 03:00:00` -> `flex_day_in_cycle=8`, `flex_segment=3`, `mode=next_cycle`, unit `$30.00`, `cycle_start_date` rewritten to `2026-08-31 18:00:00` and `next_payment_date=2026-09-30 18:00:00`.
   The day-4 row is the headline: the global mode says `full` and the product still prorates to `$26.13`.
4. Step 7 gateway checkpoint with global sync ON: the payment accordion offers **Stripe** and does NOT offer **Paddle** — expected by design, recorded as a checkpoint, not filed as a defect.
5. Checkout summary for `SLT Sync Global Daily` shows a total due today of exactly `$18.00` — the FULL recurring amount, not a prorated fraction — because the global first-charge mode is `full`.
6. The parent order totals `$18.00` and reaches status `processing` or `completed`.
7. On `SUBID_GLOBAL`: `_renewal_sync_enabled=yes`, `_renewal_sync_first_charge_mode=full`, `_renewal_sync_cycle_start_date=2026-07-31 18:00:00`, `_renewal_sync_first_full_renewal_date=2026-08-03 18:00:00`, `_renewal_sync_initial_recurring_amount=18.00`, `_next_payment_date=2026-08-03 18:00:00` (= 2026-08-04 00:00 site), `_recurring_amount=18.00`, `_billing_period=day`, `_billing_interval=3`, `_payment_gateway=stripe`, post status `arraysubs-active`.
8. The renewal date is a site-local MIDNIGHT boundary, NOT the checkout anniversary. Concretely: `_next_payment_date` ends in `18:00:00` UTC regardless of what time of day the order was placed. Record the actual checkout timestamp alongside it to make the contrast explicit.
9. The same five `_renewal_sync_*` keys are mirrored onto the order line item with identical values.
10. After the restore, the jq diff between `SLT-SYN-04-settings-before.json` and `-after.json` is EMPTY — `sync_to_billing_cycle` is `false` again, `sync_first_charge_mode` is still the string `full`, and no other key moved.
11. After the restore, step 18's guest checkout for `SLT Sync Global Daily` offers BOTH Stripe and Paddle again (the product is no longer sync-eligible with the global switch off and no per-product plan).

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | new_subscription | Subscription activated by the paid parent order (step 11) | slt-flex@example.test | `Your subscription #` … `is active` | `mailpit-agent wait-new "$PREBUY" 60 "is active"` then `mailpit-agent text latest` |
| 2 | admin_new_subscription | Same activation | site admin address | `New subscription #` … `from` | `mailpit-agent list 15` — locate by subject, record the id |
| 3 | WooCommerce order emails (customer "order is now processing"/"completed", admin "New order") | Parent order status change | customer + admin | order number | `mailpit-agent list 15` — count these as expected side effects; do not assert their body content |
| 4 | NONE EXPECTED for renewal_invoice | No renewal has been generated yet in this task | — | — | No message whose subject contains `Invoice for subscription` may appear; confirm with `mailpit-agent list 15` |
| 5 | NONE EXPECTED from the two settings saves (steps 3 and 16) | Settings save | — | — | `mailpit-agent latest-id` immediately before and after each save must be unchanged |

## Evidence to capture
- Screenshots: `SLT-SYN-04-01-global-sync-on.png`, `SLT-SYN-04-02-checkout-summary-global.png`, `SLT-SYN-04-03-order-item-sync-meta.png`, `SLT-SYN-04-04-global-sync-restored-off.png`, plus a guest-checkout gateway screenshot with sync ON and one after the restore.
- `SLT-SYN-04-settings-before.json`, `SLT-SYN-04-settings-after.json`, the jq diff (expected empty).
- `SLT-SYN-04-flex-override-globalON.txt` and `SLT-SYN-04-plain-global-globalON.txt` — the two side-by-side probe dumps.
- `SUBID_GLOBAL`, the parent order ID, the full `wp post meta list` dump, the exact checkout timestamp, the checkout total string.
- Mailpit ids for every message produced; `$PREV` and `$PREBUY`.
- Any console/network error from the block checkout or the Stripe UPE iframe.

## Pass criteria
- [ ] Global sync switched ON with first_charge_mode still "full"
- [ ] Plain-global probe: full $30.00 and 2026-09-01 boundary on all three purchase dates
- [ ] Flex-override probe: day 4 prorates to $26.13 while the global mode is "full"
- [ ] Flex-override probe: day 8 pushes next_payment to 2026-09-30 18:00:00 UTC
- [ ] Paddle hidden while global sync is ON; both gateways offered again after the restore
- [ ] Checkout charged exactly $18.00 (full, not prorated)
- [ ] Subscription carries all five `_renewal_sync_*` metas with the exact expected values
- [ ] `_next_payment_date` = 2026-08-03 18:00:00 UTC (site-local midnight boundary, not the anniversary)
- [ ] Order line item mirrors the same five metas
- [ ] jq settings diff after restore is EMPTY
- [ ] Only the listed emails appeared; no renewal_invoice mail

## Isolation / teardown
- State handoff: `SUBID_GLOBAL` is a LIVE globally-synced day/3 subscription that will renew unattended on 2026-08-04, 2026-08-07 and 2026-08-10 at 2026-08-0X 18:00:00 UTC (site-local midnight) plus its per-subscription renewal spread offset. SLT-SYN-13 and the daily renewal-watch tasks must include it and assert that it stays on the midnight boundary even though the global setting was turned back off — the subscription's own `_renewal_sync_enabled=yes` meta is what governs it from here, not the store setting.
- Compute and record the spread offset for it once: `php -r '$id=SUBID_GLOBAL;$h=(int)sprintf("%u",crc32("arraysubs-spread-".$id));printf("offset=%ds (%s)\n",$h%21600,gmdate("H:i:s",$h%21600));'` — every renewal-timing assertion on this subscription uses it.
- Restores: `renewals.sync_to_billing_cycle` is returned to `false` in step 16, inside this task, and the empty jq diff is the proof. No other setting is touched. The cart is emptied. The subscription, its order and the product are left in place and are removed by SLT-SETUP-99.
- Binding on later authors: no other SLT task may turn global sync on. If a later task observes sync behaviour it did not expect, check `_renewal_sync_enabled` on the subscription rather than assuming the global switch moved.
