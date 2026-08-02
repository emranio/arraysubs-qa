---
id: 20
title: SLT-PROD-05 Create SLT Renewal Price Step with a different renewal price after 2 cycles
status: todo
priority: high
created: 2026-08-02T03:43:04.742050405+02:00
updated: 2026-08-02T03:43:14.903535357+02:00
tags:
    - setup
    - products
    - day-01
    - has-conflicts
due: "2026-08-03"
estimate: 40m
depends_on:
    - 10
class: standard
---

> **SLT-PROD-05** · group `catalog` · scheduled **D01** (2026-08-03)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`unrated` · impossible-timing** — with `SLT-SETUP-01`, `SLT-SETUP-02`, `SLT-SETUP-03`, `SLT-SETUP-04`, `SLT-SETUP-05`, `SLT-PROD-01`

- *Problem:* 25 of the 26 authored tasks are stamped day 0. Sixteen of them are multi-step browser-driven product builds (each: open post-new, set 6-15 fields across 3 tabs, publish, reload, verify, meta-dump, guest front-end check, registry append), two are long audit tasks with 15-20 probe steps each (SLT-SYN-01, SLT-SYN-02), one is a live Stripe purchase with a settings flip and restore (SLT-SYN-04), and on top of that D0 is also where the plan's isolation notes demand the D0 purchases of SLT-PROD-01/02/03/06/07/10/12/13/14/15/16. There is a hard serial chain inside the day as well: SETUP-01 -> SETUP-02 -> PROD-16 -> SETUP-05 -> SYN-04, and SETUP-01 -> SETUP-02 -> PROD-14 -> SYN-03 -> SYN-04. This is not executable in one day, and it wastes D4-D9 which sit empty.
- *Required fix:* Adopt the rebalanced D0-D10 calendar in schedule_notes. Only products with a genuinely date-bound purchase stay on D0 (SLT-PROD-01, 06, 07, 13 plus the three foundation tasks); everything else is spread D1-D5 with its purchase deadline recomputed so it still fits the window. Peak day drops from 25 tasks (~30h) to 7 tasks (~7h) and the median day is ~4.5h, inside the 1.6x band.

**`unrated` · duplicate-coverage** — with `SLT-SYN-03`, `SLT-PROD-15`

- *Problem:* SLT-SYN-03 creates two products that are already covered. (a) SLT Sync Global Daily (day/3, non-flex) is functionally identical as a control to the 'No Sync' variation of SLT Flex Variable Daily (day/3, flex unticked) created by SLT-PROD-15 - both exist to show anniversary scheduling on a 3-day cycle. (b) SLT Sync Excl Probe exists only to demonstrate that Different Renewal Price hides the Flexible Renewal Sync section, which SLT-PROD-05 steps 7-9 already capture verbatim with three screenshots, and its declared purchaser SLT-SYN-09 does not exist. Two product-creation slots on the most overloaded day are spent on coverage the catalog already holds.
- *Required fix:* Keep SLT Sync Global Daily - SLT-SYN-04 needs a simple (not variation) product so the five _renewal_sync_* metas read cleanly - but drop SLT Sync Excl Probe entirely and delete its half of SLT-SYN-03 (steps 10-16, screenshots 02/03). Point SLT-SYN-03's exclusivity claim at SLT-PROD-05's evidence ids instead. Conversely, do not spend a checkout on the SLT-PROD-15 'No Sync' variation as a scheduling control; assert it by meta + SegmentPlan::getConfig()===null only, and let SLT Sync Global Daily carry the purchased control.

**`high` · dependency-inversion (product creation after first consumer)** — with `SLT-PROD-04`, `SLT-PROD-08`, `SLT-PROD-09`, `SLT-PROD-10`, `SLT-PROD-11`, `SLT-PROD-15`

- *Problem:* The corrected calendar in plan-audit places several catalog tasks later than the first new-index task that depends on them. SLT-SETUP-04 (coupons) is D3 but SLT-CPN-01/02 need it on D1 18:00-19:00. SLT-PROD-05 is D3 but SLT-LIFE-05 buys it on D1. SLT-PROD-16 is D1 but SLT-DUN-01 (corrected to D2 13:00) and SLT-CHK-04 (D2) need it, and SLT-MYA-05 needs it on D2 morning. SLT-PROD-09 is D5 but SLT-CPN-04 (D3) and SLT-CHK-12 (D5) depend on it. SLT-PROD-10 and SLT-PROD-11 are D4 but SLT-CHK-13 (D4), SLT-CHK-10 (D5) and SLT-SW-09 (D4, which explicitly says PROD-11 must be done 'before this task starts on D4') need them earlier in the day or before. SLT-PROD-08 is D5 but SLT-CHK-11 buys its variations on D5. SLT-PROD-15 is D2 and SLT-SYN-13 buys its variations on D2 - correct only if SYN-02's audit sits strictly between them.
- *Required fix:* Adopt the rebalanced calendar in this report: SETUP-04 and PROD-05 to D1 morning; PROD-16 to D1 morning (ahead of SETUP-05, which also gains PROD-14 as a dependency per audit C03); PROD-02/03/09/15 and SYN-02 to D2 morning; PROD-04/10/11 to D3 after the SYN-04 bracket closes; PROD-08 to D4 morning. Add an explicit intra-day ordering line to every day's calendar row ('creations and audits before 12:00, purchases after 12:00') and make it a pass criterion that each consuming task quotes the creating task's registry entry.

**`low` · duplicate-coverage** — with `SLT-EML-15`, `SLT-REN-02`, `SLT-ADM-06`, `SLT-EML-03`, `SLT-EML-06`, `SLT-CHK-01`

- *Problem:* Six overlapping clusters, each spending an execution slot on a code path another task already proves. (a) SLT-EML-15, SLT-REN-02 and SLT-ADM-06 all read the same SUB_CORE renewal cycle: EML-15 reconciles the mail set, REN-02 asserts the schedule re-arm, ADM-06 asserts the order typing/linkage - three tasks, one cycle, three separate evidence sets. (b) SLT-EML-03's Stripe leg re-asserts REN-02's payment_successful and its Paddle leg re-asserts SLT-REN-04's. (c) SLT-EML-06 re-proves new_subscription + admin_new_subscription at a Stripe block checkout, which is SLT-CHK-01's email rows 3 and 4 verbatim, on a new account. (d) SLT-EML-07 and SLT-SW-10 both drive pending-cancellation -> cancelled -> reactivation and both assert the same four emails. (e) SLT-EML-04's four payment_failed pairs are exactly SLT-DUN-01 ER8 plus SLT-DUN-02 ER9. (f) SLT-SYN-11's 'flex section hidden for a Different Renewal Price product' repeats SLT-PROD-05 steps 7-9 and SLT-SYN-01's probe. (g) SLT-LIFE-02's Paddle 'no Renew Early control' negative repeats SLT-CHK-04 ER7 and SLT-PROD-16.
- *Required fix:* Keep one owner per assertion and make the others cite it. (a) EML-15 owns the reconciled mail set for the cycle and publishes it to the registry; REN-02 keeps only the schedule/offset/no-drift assertions; ADM-06 keeps only the HPOS meta assertions and drops its Related-Orders screenshot in favour of ADM-02's. (b) EML-03 keeps only the content assertions (amount, method row, UTC+6 next date, the Paddle ordering hazard) and cites REN-02/REN-04 for 'the renewal fired'. (c) EML-06 keeps only the gating-key and subject-string proof (emails.new_subscription.enabled, admin recipient resolution, the B4 dead-setting verdict) and cites CHK-01 for the checkout. (d) EML-07 owns the email set; SW-10 owns the reason-required / offers-declined / scheduled-cancel-timestamp / reactivation-scheduling-bug half and cites EML-07's mailpit ids. (e) EML-04 places no purchase and becomes the mail-content rider on the DUN ladder (attempt-number visibility, Pay Now link resolution, To: headers) - see the DUN re-day entry. (f) SYN-11 keeps only the force-set-meta half (isEnabled() true, getConfig() null, zero _renewal_sync_* on the subscription) and cites PROD-05 for the UI-absence screenshots. (g) LIFE-02 cites CHK-04's screenshot rather than re-driving the Paddle portal.

---
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
