---
id: 6
title: SLT-PROD-06 Create SLT2 Fixed Three Cycles, a day/2 subscription whose final renewal expires it on 2026-08-27
status: blocked
priority: high
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-23T03:02:37.820891551+02:00
started: 2026-08-22T22:10:31.839572662+02:00
tags:
    - cycle-2
    - granular
    - setup
    - products
    - day-00
due: "2026-08-23"
estimate: 30m
depends_on:
    - 10
    - 11
blocked: true
block_reason: 'Shared issue #2: out-of-phase D00 mutation and missing authoritative registry publication'
class: standard
---

> **SLT-PROD-06** · group `catalog` · scheduled **D00** (2026-08-23)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Provide the limited-cycle product whose entire life — signup, two renewals, expiry — fits inside the window. The live lifecycle contract is charge-count based: the D0 purchase is payment 1, renewal #1 on 2026-08-25 is payment 2, and renewal #2 on 2026-08-27 is payment 3. That final payment stamps `_end_date`, clears `_next_payment_date`, and changes the status to `arraysubs-expired` in the same operation. The unused `arraysubs_calculate_end_date_from_length()` helper must not be used to predict an extra fourth interval.

## Scope
- Gateway: N/A
- Checkout: N/A (creation and storefront verification only)
- Account: N/A (creation only)
- Plugins: free-only

## Preconditions
- SLT-SETUP-01 complete.
- SLT-SETUP-02 baseline (global sync off) means the renewal times are anniversary-based: checkout time + 2 days, not midnight.

## Test data
| Item | Value |
|---|---|
| Product | SLT2 Fixed Three Cycles / slug `slt2-fixed-three-cycles` |
| Account | N/A |
| Coupon | N/A |
| Card | N/A |
| Amounts | Regular price $7.00; charge today $7.00; two renewals of $7.00; total lifetime spend $21.00 |

## Steps
1. Capture `mailpit-agent latest-id`.
2. `agent-browser --session admin-SLT-PROD-06 open "https://mirror-help.arrayhash.com/wp-admin/post-new.php?post_type=product"` -> `snapshot -i`.
3. **Product title**: `SLT2 Fixed Three Cycles`. **Description**: `SLT2 window product. Bills every 2 days for 3 cycles, then expires. Delete on 2026-09-05.`
4. Type **Simple product**; tick **Virtual**; tick **Subscription [ArraySubs]**.
5. **General** tab: **Regular price ($)** = `7.00`.
6. **Subscription [ArraySubs]** tab: **Billing Period** = `Day`; **Billing Interval** = `2`; **Subscription Length** = `3`; **Trial Length** = `0`; **Sign-up Fee ($)** empty; **Different Renewal Price** unticked; **Flexible Renewal Sync** left UNTICKED (a 2-day nominal cycle is below `MIN_CYCLE_DAYS = 3`, so the plan could never resolve).
7. Slug `slt2-fixed-three-cycles`. Publish. Reload and re-verify.
8. `wp post meta list <ID> --keys=_is_subscription,_subscription_period,_subscription_interval,_subscription_length,_trial_length,_regular_price --allow-root`.
8a. Preserve the full-store Member Access rule and append only this product ID to its `exclusion_product_ids` through **Member Access → Shop Access**, so the declared guest storefront and later checkout checks are not intercepted by the unrelated members-only store fixture.
9. As `--session guest-SLT-PROD-06`, open the product page and confirm the summary states the limited number of cycles (`getSubscriptionDurationSummary()`).
10. Append the ID to the registry.

## Expected results
1. Published simple + virtual + subscription, slug `slt2-fixed-three-cycles`.
2. `_subscription_period=day`, `_subscription_interval=2`, `_subscription_length=3`, `_trial_length=0`, `_regular_price=7.00`.
3. Product page shows a bounded duration (e.g. "for 3 cycles"), not "until cancelled".
4. Date contract for the buying task, bought on 2026-08-23: `_next_payment_date` = 2026-08-25 (same clock time); renewal #1 advances it to 2026-08-27; renewal #2 is the third completed payment and stamps `_end_date` at the charge moment on 2026-08-27, with final status `arraysubs-expired`.
5. The buying task owns the short-horizon `expiring_soon` edge: require the current scheduler to handle a 7-day lead that is already inside/past at signup, send once before expiry, dedupe it, and also send the single `has expired` mail at the final renewal.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Product publish | — | — | Complete delta after the step-1 baseline; zero task-attributable mail, while unrelated/background mail is allowed and classified |

## Evidence to capture
- Screenshots: `SLT-PROD-06-01-subscription-tab.png`, `SLT-PROD-06-02-frontend-duration.png`.
- Product ID; meta list output.

## Pass criteria
- [ ] Published with day/2 and length 3, price 7.00
- [ ] Front end shows the bounded cycle count
- [ ] Metas exactly as listed
- [ ] Flex sync left off (sub-minimum cycle documented)
- [ ] Zero mail

## SLT2 execution — SUPERSEDED / BLOCKED (site date 2026-08-23)

- Browser-published product `31347` as simple/virtual, slug `slt2-fixed-three-cycles`, day/2, length `3`, trial `0`, price `7.00`; reload and WP-CLI meta matched. Renewal-price and flex-sync flags are absent.
- Guest storefront rendered `$7.00 / 2 days`, the explicit bounded duration `3 billing cycles`, and `Subscribe Now`. The target product was accessible after appending only `31347` to the existing Shop Access exclusion list, which now contains `[31340,31347]`.
- Registry page `31301` records the ID and catalog contract. Mailpit baseline/latest remained `1dKG8mscVMI2jlnj8Pzk3k`; admin and guest browser errors were empty. Evidence: `/home/server-manager/slt-evidence/SLT-PROD-06-*`.
- No checkout, order, subscription, or payment occurred. Publishing did create registered Paddle sandbox catalogue product `pro_01m0nhqad1kxs86czedp5gmyfy` and price `pri_01m0nhqbcjykweg9hv4qj9gmjy`; shared issue #2 owns the invalid phase/registry result. The two-renewal/final-expiry assertions remain owned by lifecycle task 4 after all blockers are resolved.

## Isolation / teardown
- State handoff: MUST be purchased on D0 (2026-08-23) as `slt2-core` so both renewals and the final 2026-08-27 expiry remain observable. It is also eligible as a day/2 subscription child of the Subscription Box (matching period AND interval), though SLT-PROD-10 creates its own dedicated child instead to avoid coupling.
- Restores: nothing. `SLT-SETUP-99A` does not delete anything; this product and its expired subscription are deleted by `SLT-SETUP-99B` on 2026-09-05.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.

[[2026-08-23]] Sun 02:39

## D00 early-watcher phase-integrity correction — 2026-08-23

- Product 31347 was published at 02:13:46 site and auto-created Paddle sandbox objects `pro_01m0nhqad1kxs86czedp5gmyfy` / `pri_01m0nhqbcjykweg9hv4qj9gmjy`.
- D00 watch ownership assigns this card to afternoon at approximately 16:10 site, but its browser mutation occurred roughly 13.5-14.5 hours early. Its prior PASS therefore cannot stand under the binding phase rule.
- The authoritative TSV also omitted these identities at completion. The watcher backfilled only exact proven identity/provider rows with `cleanup_approved=no`; this containment does not waive timing or proof defects.
- Shared issue #2 owns the blocker. Do not delete, recreate, rename, or duplicate the fixture. The afternoon owner must use an approved non-duplicating revalidation protocol and rerun every mandatory assertion before unblocking this card.

[[2026-08-23]] Sun 03:02

## Closure-audit normalization

Stale PASS heading/checkmarks were reset, issue #2 linkage was made explicit, and provider-side catalogue wording was corrected where applicable. The lifecycle start timestamp now matches the original `todo -> in-progress` activity event. Status remains blocked; this note is tracking normalization, not fresh test proof.
