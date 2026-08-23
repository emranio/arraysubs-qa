---
id: 7
title: SLT-PROD-07 Create SLT2 Lifetime One Time, the never-renews negative control
status: blocked
priority: high
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-23T03:02:39.040071441+02:00
started: 2026-08-22T22:18:02.785216761+02:00
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
blocked: true
block_reason: 'Shared issue #2: out-of-phase D00 mutation and missing authoritative registry publication'
class: standard
---

> **SLT-PROD-07** · group `catalog` · scheduled **D00** (2026-08-23)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Provide the negative control that must NEVER produce a renewal, a renewal invoice, a renewal reminder, or a next-payment date. `_subscription_period = lifetime` forces `_subscription_interval=1` and `_subscription_length=0` on save, `arraysubs_calculate_next_payment_from_date()` returns an empty string, `arraysubs_calculate_end_date_from_length()` returns null, and both the core and pro sync paths bail on lifetime.

## Scope
- Gateway: N/A
- Checkout: N/A (creation and storefront verification only)
- Account: N/A (creation only)
- Plugins: both (pro view supplies the flex negative)

## Preconditions
- SLT-SETUP-01 complete.
- Validation note: the billing-interval range check is skipped for lifetime, but the regular-price > 0 rule still applies.

## Test data
| Item | Value |
|---|---|
| Product | SLT2 Lifetime One Time / slug `slt2-lifetime-one-time` |
| Account | N/A |
| Coupon | N/A |
| Card | N/A |
| Amounts | Regular price $49.00; charge today $49.00; expected renewals: none, ever |

## Steps
1. Capture `mailpit-agent latest-id`.
2. `agent-browser --session admin-SLT-PROD-07 open "https://mirror-help.arrayhash.com/wp-admin/post-new.php?post_type=product"` -> `snapshot -i`.
3. **Product title**: `SLT2 Lifetime One Time`. **Description**: `SLT2 window product. Lifetime deal, must never renew. Delete on 2026-09-05.` The post-watch date is binding; this control must remain present through the D12 report on 2026-09-04.
4. Type **Simple product**; tick **Virtual**; tick **Subscription [ArraySubs]**.
5. **General** tab: **Regular price ($)** = `49.00`.
6. **Subscription [ArraySubs]** tab: **Billing Period** = `Lifetime Deal`. Leave **Billing Interval** and **Subscription Length** as displayed — the saver overwrites them to 1 and 0. **Trial Length** = `0`; **Sign-up Fee ($)** empty; **Different Renewal Price** unticked.
7. Screenshot the panel: the **Flexible Renewal Sync to Next Billing Cycle** section must be hidden for the lifetime period (`$arraysubs_flex_section_hidden = ... || 'lifetime' === $arraysubs_flex_period`). This is the third exclusivity negative required by the catalog.
8. Slug `slt2-lifetime-one-time`. Publish. Reload and re-verify.
9. `wp post meta list <ID> --keys=_is_subscription,_subscription_period,_subscription_interval,_subscription_length,_trial_length,_regular_price,_arraysubs_flex_sync_enabled --allow-root`.
9a. Preserve the existing full-store Member Access rule and append only this product ID to `exclusion_product_ids` through **Member Access → Shop Access**, so the guest negative control and later lifetime checkout are not intercepted by an unrelated members-only fixture.
10. As `--session guest-SLT-PROD-07`, open the product page and confirm the summary shows a one-time/lifetime purchase, not a recurring schedule.
11. Append the ID to the registry.

## Expected results
1. Published simple + virtual + subscription, slug `slt2-lifetime-one-time`.
2. `_subscription_period=lifetime`, `_subscription_interval=1` (force-written), `_subscription_length=0` (force-written), `_regular_price=49.00`, `_trial_length=0`.
3. `_arraysubs_flex_sync_enabled` absent and the flex section hidden in the UI.
4. Product page shows a lifetime/one-time summary with no "every N days" phrasing.
5. Contract for the buying task: after checkout the subscription must have an EMPTY `_next_payment_date`, no `_end_date`, no scheduled `arraysubs_generate_renewal_invoice` or `arraysubs_process_renewal` action in Scheduled Actions, and no renewal-related mail for the whole 12-day watch.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Product publish | — | — | Complete delta after the step-1 baseline; zero task-attributable mail, while unrelated/background mail is allowed and classified |

## Evidence to capture
- Screenshots: `SLT-PROD-07-01-subscription-tab-lifetime.png`, `SLT-PROD-07-02-flex-hidden-by-lifetime.png`, `SLT-PROD-07-03-frontend.png`.
- Product ID; meta list output showing the forced interval/length.

## Pass criteria
- [ ] Published with period lifetime and price 49.00
- [ ] Interval forced to 1 and length forced to 0 on save
- [ ] Flex sync hidden by lifetime
- [ ] Front end shows no recurring schedule
- [ ] Zero mail

## SLT2 execution — SUPERSEDED / BLOCKED (site date 2026-08-23)

- Browser-published product `31357` as simple/virtual, slug `slt2-lifetime-one-time`, period `lifetime`, price `49.00`. Reloaded meta proves the saver forced interval `1` and length `0`; trial is `0` and flex-sync/renewal-price flags are absent.
- In the live subscription panel, `Lifetime Deal` disabled the interval at `1` and removed the Flexible Renewal Sync control. The guest storefront showed only `$49.00` plus `Subscribe Now`, with no recurring day/week/month/year schedule and no members-only block on this target.
- Added only `31357` to the existing Shop Access exclusions, now `[31340,31347,31357]`, and appended the lifetime handoff to registry page `31301`.
- Mailpit baseline/latest remained `1dKG8mscVMI2jlnj8Pzk3k`; admin and guest browser errors were empty. Evidence: `/home/server-manager/slt-evidence/SLT-PROD-07-*`.
- The whole-window no-next-payment/no-action/no-renewal-order/no-mail contract remains owned by checkout/watch task 3 after Stripe issue #1 is resolved; this catalog setup created no order, subscription or gateway operation.

## Isolation / teardown
- State handoff: buy as `slt2-core` on D0 and then leave it alone. Every daily renewal-watch task from D1 to D12 must re-assert that this subscription still has no next-payment date, no renewal order, and no renewal mail. Because lifetime products are never sync-eligible, this is also a valid Paddle target if a second Paddle case is needed.
- Restores: nothing. Deleted by SLT-SETUP-99B.

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

- Product 31357 was published at 02:21:23 site; it is the verified no-Paddle-binding control.
- D00 watch ownership assigns this card to afternoon at approximately 16:10 site, but its browser mutation occurred roughly 13.5-14.5 hours early. Its prior PASS therefore cannot stand under the binding phase rule.
- The authoritative TSV also omitted these identities at completion. The watcher backfilled only exact proven identity/provider rows with `cleanup_approved=no`; this containment does not waive timing or proof defects.
- Shared issue #2 owns the blocker. Do not delete, recreate, rename, or duplicate the fixture. The afternoon owner must use an approved non-duplicating revalidation protocol and rerun every mandatory assertion before unblocking this card.

[[2026-08-23]] Sun 03:02

## Closure-audit normalization

Stale PASS heading/checkmarks were reset, issue #2 linkage was made explicit, and provider-side catalogue wording was corrected where applicable. The lifecycle start timestamp now matches the original `todo -> in-progress` activity event. Status remains blocked; this note is tracking normalization, not fresh test proof.
