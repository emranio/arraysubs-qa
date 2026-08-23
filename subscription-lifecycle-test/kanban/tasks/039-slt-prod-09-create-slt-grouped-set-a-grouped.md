---
id: 39
title: SLT-PROD-09 Create SLT2 Grouped Set, a grouped product with two subscription children
status: todo
priority: medium
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - setup
    - products
    - day-03
due: "2026-08-26"
estimate: 45m
depends_on:
    - 10
    - 5
    - 58
    - 61
class: standard
---

> **SLT-PROD-09** · group `catalog` · scheduled **D03** (2026-08-26)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Provide the grouped-product fixture and revalidate the current behavior without assuming the previous implementation. Determine whether the grouped parent can carry subscription configuration, whether its subscription children render and add correctly, and how `multiple_subscriptions.allow_multiple_in_cart = false` is enforced when two subscription children are selected together. A visible refusal is mandatory when the cart contract rejects the combination.

## Scope
- Gateway: N/A
- Checkout: N/A (creation and storefront verification only)
- Account: N/A (creation only)
- Plugins: free-only

## Preconditions
- SLT-SETUP-01, SLT-PROD-01 (`SLT2 Daily Core`) and SLT-PROD-04 (`SLT2 Signup Fee Daily`) complete.
- Run after the D3 SLT-SYN-04 global-settings bracket has closed and immediately after SLT-PROD-04, before the 18:00–19:00 SLT-CPN-04 slot.
- This task also creates one plain non-subscription child so the mixed-cart rule (`allow_mixed_cart = true`, unchanged) is exercisable.
- Sessions `admin-SLT-PROD-09` and `guest-SLT-PROD-09` are exclusive to this task.

## Test data
| Item | Value |
|---|---|
| Product | SLT2 Grouped Set / slug `slt2-grouped-set`; child `SLT2 Grouped Extra` / slug `slt2-grouped-extra` ($3.00, non-subscription) |
| Account | N/A |
| Coupon | N/A |
| Card | N/A |
| Amounts | children: $10.00/day, $9.00/day + $15.00 fee, $3.00 one-off |

## Steps
1. Capture `mailpit-agent latest-id`.
2. Create the plain child first: `agent-browser --session admin-SLT-PROD-09 open "https://mirror-help.arrayhash.com/wp-admin/post-new.php?post_type=product"`; title `SLT2 Grouped Extra`; **Simple product**; tick **Virtual**; do NOT tick **Subscription [ArraySubs]**; **Regular price ($)** `3.00`; slug `slt2-grouped-extra`; Publish.
3. New product: title `SLT2 Grouped Set`. **Description**: `SLT2 window product. Grouped parent with subscription children. Delete on 2026-09-05.`
4. Set the product type dropdown to **Grouped product**. Confirm in the snapshot that the **Subscription [ArraySubs]** header checkbox and the Subscription tab are now HIDDEN — grouped parents are out of scope for the engine by design. Capture `SLT-PROD-09-01-grouped-no-subscription-controls.png`.
5. **Linked Products** tab -> **Grouped products** field: search and add `SLT2 Daily Core`, `SLT2 Signup Fee Daily`, `SLT2 Grouped Extra` (in that order).
6. Slug `slt2-grouped-set`. Publish. Reload and confirm all three children persisted.
7. `wp post meta list <GROUPED_ID> --keys=_children --allow-root` and `wp post list --post_type=product --name=slt2-grouped-set --field=ID --allow-root`.
8. Before any storefront/cart access, append only `<GROUPED_ID>` and `<EXTRA_ID>` to Shop Access rule `<D0_SHOP_ACCESS_RULE_ID>` under `exclusion_product_ids` through **Member Access → Shop Access**. Preserve every other field and every prior exclusion; re-read the raw option and require each new ID exactly once. The two existing subscription children must already be present from their owning product tasks.
9. As `--session guest-SLT-PROD-09`, open `https://mirror-help.arrayhash.com/product/slt2-grouped-set/?slt2-cache-bust=<timestamp>` -> `snapshot -i`. Confirm each subscription child shows its own recurring price summary in the grouped table and capture `SLT-PROD-09-02-frontend-grouped-table.png`.
10. Add-to-cart probe A: set quantity 1 on `SLT2 Daily Core` only and submit. If one-click redirects to block checkout, record it and explicitly reopen `/cart/`; snapshot the exact one-line cart, then empty it.
11. Add-to-cart probe B: set quantity 1 on BOTH `SLT2 Daily Core` and `SLT2 Signup Fee Daily`, submit, and follow the resulting destination. Explicitly reopen `/cart/` if one-click redirected. With `allow_multiple_in_cart=false` the second subscription must be refused by `SubscriptionCheckout\Services\CartValidation`; record the exact notice text and which item won, then capture `SLT-PROD-09-03-probe-b-multiple-refused.png`. Empty the cart.
12. Add-to-cart probe C: `SLT2 Daily Core` + `SLT2 Grouped Extra` (mixed cart) — permitted by `allow_mixed_cart=true`. Handle any one-click redirect, explicitly reopen `/cart/`, capture the totals as `SLT-PROD-09-04-probe-c-mixed-cart.png`, then empty the cart.
13. Inspect the complete Mailpit delta after step 1 and require zero task-attributable mail, append the grouped ID, extra child ID, and verified Shop Access exclusions to the registry, and close only `admin-SLT-PROD-09` and `guest-SLT-PROD-09`.

## Expected results
1. `SLT2 Grouped Extra` published as a plain simple product, `_is_subscription` absent, price $3.00.
2. `SLT2 Grouped Set` published as type `grouped` with `_children` containing exactly the three child IDs.
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
- [ ] Grouped parent published with exactly three children
- [ ] No subscription controls on the grouped parent (documented)
- [ ] Probe A single-subscription add works
- [ ] Probe B retains only the permitted subscription line and shows a clear refusal notice for the rejected second subscription
- [ ] Probe C mixed cart totals $13.00
- [ ] Grouped and extra parent product IDs are each present exactly once in the preserved Shop Access exclusion list
- [ ] Zero mail, cart left empty

## Isolation / teardown
- State handoff: the refusal notice text from probe B is the reference string for any later multi-subscription-cart test. Do NOT flip `allow_multiple_in_cart` to change this — it is deliberately left at the site default so the refusal path stays testable all window.
- Restores: cart emptied; SLT-SETUP-99A restores the exact pre-window Shop Access rule snapshot. Grouped parent and `SLT2 Grouped Extra` are deleted by SLT-SETUP-99B; the two subscription children are owned by SLT-PROD-01/04.

---

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
