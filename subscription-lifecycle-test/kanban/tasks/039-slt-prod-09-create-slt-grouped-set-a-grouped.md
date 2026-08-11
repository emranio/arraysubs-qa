---
id: 39
title: SLT-PROD-09 Create SLT Grouped Set, a grouped product with two subscription children
status: done
priority: medium
created: 2026-08-02T03:43:06.254717075+02:00
updated: 2026-08-05T11:17:35.178214061+02:00
started: 2026-08-05T11:17:34.944220781+02:00
completed: 2026-08-05T11:17:34.944220781+02:00
tags:
    - setup
    - products
    - day-03
due: "2026-08-05"
estimate: 45m
depends_on:
    - 10
    - 5
    - 58
    - 61
class: standard
---

> **SLT-PROD-09** · group `catalog` · scheduled **D03** (2026-08-05)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Provide the grouped product and pin the real behaviour: ArraySubs has NO grouped-product handling at all — the header **Subscription [ArraySubs]** checkbox is registered `show_if_simple show_if_variable`, so a grouped parent can never itself be a subscription. Its children are ordinary simple subscription products added to the cart individually, which means the grouped page is also the cleanest way to exercise `multiple_subscriptions.allow_multiple_in_cart = false` (baseline, unchanged), where adding two subscription children at once must be refused.

## Scope
- Gateway: N/A
- Checkout: N/A (creation and storefront verification only)
- Account: N/A (creation only)
- Plugins: free-only

## Preconditions
- SLT-SETUP-01, SLT-PROD-01 (`SLT Daily Core`) and SLT-PROD-04 (`SLT Signup Fee Daily`) complete.
- Run after the D3 SLT-SYN-04 global-settings bracket has closed and immediately after SLT-PROD-04, before the 18:00–19:00 SLT-CPN-04 slot.
- This task also creates one plain non-subscription child so the mixed-cart rule (`allow_mixed_cart = true`, unchanged) is exercisable.
- Sessions `admin-SLT-PROD-09` and `guest-SLT-PROD-09` are exclusive to this task.

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
2. Create the plain child first: `agent-browser --session admin-SLT-PROD-09 open "https://mirror-help.arrayhash.com/wp-admin/post-new.php?post_type=product"`; title `SLT Grouped Extra`; **Simple product**; tick **Virtual**; do NOT tick **Subscription [ArraySubs]**; **Regular price ($)** `3.00`; slug `slt-grouped-extra`; Publish.
3. New product: title `SLT Grouped Set`. **Description**: `SLT window product. Grouped parent with subscription children. Delete on 2026-08-15.`
4. Set the product type dropdown to **Grouped product**. Confirm in the snapshot that the **Subscription [ArraySubs]** header checkbox and the Subscription tab are now HIDDEN — grouped parents are out of scope for the engine by design. Capture `SLT-PROD-09-01-grouped-no-subscription-controls.png`.
5. **Linked Products** tab -> **Grouped products** field: search and add `SLT Daily Core`, `SLT Signup Fee Daily`, `SLT Grouped Extra` (in that order).
6. Slug `slt-grouped-set`. Publish. Reload and confirm all three children persisted.
7. `wp post meta list <GROUPED_ID> --keys=_children --allow-root` and `wp post list --post_type=product --name=slt-grouped-set --field=ID --allow-root`.
8. Before any storefront/cart access, append only `<GROUPED_ID>` and `<EXTRA_ID>` to Shop Access rule `rule_1784662676378_maa3te08s` under `exclusion_product_ids` through **Member Access → Shop Access**. Preserve every other field and every prior exclusion; re-read the raw option and require each new ID exactly once. The two existing subscription children must already be present from their owning product tasks.
9. As `--session guest-SLT-PROD-09`, open `https://mirror-help.arrayhash.com/product/slt-grouped-set/?slt-cache-bust=<timestamp>` -> `snapshot -i`. Confirm each subscription child shows its own recurring price summary in the grouped table and capture `SLT-PROD-09-02-frontend-grouped-table.png`.
10. Add-to-cart probe A: set quantity 1 on `SLT Daily Core` only and submit. If one-click redirects to block checkout, record it and explicitly reopen `/cart/`; snapshot the exact one-line cart, then empty it.
11. Add-to-cart probe B: set quantity 1 on BOTH `SLT Daily Core` and `SLT Signup Fee Daily`, submit, and follow the resulting destination. Explicitly reopen `/cart/` if one-click redirected. With `allow_multiple_in_cart=false` the second subscription must be refused by `SubscriptionCheckout\Services\CartValidation`; record the exact notice text and which item won, then capture `SLT-PROD-09-03-probe-b-multiple-refused.png`. Empty the cart.
12. Add-to-cart probe C: `SLT Daily Core` + `SLT Grouped Extra` (mixed cart) — permitted by `allow_mixed_cart=true`. Handle any one-click redirect, explicitly reopen `/cart/`, capture the totals as `SLT-PROD-09-04-probe-c-mixed-cart.png`, then empty the cart.
13. Inspect the complete Mailpit delta after step 1 and require zero task-attributable mail, append the grouped ID, extra child ID, and verified Shop Access exclusions to the registry, and close only `admin-SLT-PROD-09` and `guest-SLT-PROD-09`.

## Expected results
1. `SLT Grouped Extra` published as a plain simple product, `_is_subscription` absent, price $3.00.
2. `SLT Grouped Set` published as type `grouped` with `_children` containing exactly the three child IDs.
3. The grouped parent offers no subscription checkbox and no Subscription tab.
4. The grouped storefront table renders per-child recurring summaries for the two subscription children and a plain price for the extra.
5. Probe A: cart holds one subscription line, total $10.00 plus no fee (fee belongs to the other child).
6. Probe B: the cart ends up with only ONE subscription line and a WooCommerce notice explaining that multiple subscriptions are not allowed; record the verbatim text.
7. Probe C: cart holds one subscription line ($10.00) and one regular line ($3.00), total $13.00, no error.
8. Both newly published parent product IDs are present exactly once in the preserved Shop Access exclusion list before storefront access; cart is empty at the end and no order is created.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Product publish and all three cart probes | — | — | Complete delta after the step-1 baseline; zero task-attributable mail, while unrelated/background mail is allowed and classified |

## Evidence to capture
- Screenshots: `SLT-PROD-09-01-grouped-no-subscription-controls.png`, `SLT-PROD-09-02-frontend-grouped-table.png`, `SLT-PROD-09-03-probe-b-multiple-refused.png`, `SLT-PROD-09-04-probe-c-mixed-cart.png`.
- Grouped ID, extra child ID, `_children` meta; raw Shop Access rule showing both IDs exactly once; verbatim refusal notice.

## Pass criteria
- [x] Grouped parent published with exactly three children
- [x] No subscription controls on the grouped parent (documented)
- [x] Probe A single-subscription add works
- [x] Probe B retained one subscription line; the required refusal notice was missing and the false success feedback is captured in `issues/light-plugin-SLT-PROD-09-grouped-multi-subscription-refusal-notice-missing.md`
- [x] Probe C mixed cart totals $13.00
- [x] Grouped and extra parent product IDs are each present exactly once in the preserved Shop Access exclusion list
- [x] Zero mail, cart left empty

## Isolation / teardown
- State handoff: the refusal notice text from probe B is the reference string for any later multi-subscription-cart test. Do NOT flip `allow_multiple_in_cart` to change this — it is deliberately left at the site default so the refusal path stays testable all window.
- Restores: cart emptied; SLT-SETUP-99A restores the exact pre-window Shop Access rule snapshot. Grouped parent and `SLT Grouped Extra` are deleted by SLT-SETUP-99B; the two subscription children are owned by SLT-PROD-01/04.

---

## D03 execution result (2026-08-05)

QA COMPLETE WITH PRODUCT DEFECT FILED. Published extra child `12583` and grouped parent `12586`; `_children` contains exactly `11927`, `12583`, and `12577`, the grouped parent has no visible subscription controls, and Shop Access gained only the two new IDs. Probe A passed at USD `10.00`; Probe C passed at USD `13.00`. Probe B retained exactly one subscription (`SLT Signup Fee Daily`) but rendered no refusal notice and falsely claimed both children were added; standalone issue: `issues/light-plugin-SLT-PROD-09-grouped-multi-subscription-refusal-notice-missing.md`. Both task carts are empty, no order exists, Mailpit stayed at `56kcLytDylTWndyI4kEeYS`, and browser error buffers were empty. Full evidence: `/home/server-manager/slt-evidence/SLT-PROD-09-facts.txt`.

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
