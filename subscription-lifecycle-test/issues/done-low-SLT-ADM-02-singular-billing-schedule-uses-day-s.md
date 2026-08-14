# Subscription detail pluralizes a one-day billing interval as `day(s)`

- **Severity:** low
- **Date found:** 2026-08-11
- **Watch day:** D09
- **Originating task:** SLT-ADM-02
- **Plan file:** `qa/subscription-lifecycle-test/kanban/tasks/113-subscription-detail-screen-every-field-dates.md`

## Affected records

- Subscription IDs: `11959` (Stripe-backed) and `12760` (gateway-less admin-created)
- Order IDs: `11949, 12276, 12426, 12604, 12915, 13063, 13241, 13466, 13610` for subscription `11959`; N/A for `12760`
- Product ID: `11927` (`SLT Daily Core`)
- WP users:
  - `347`, `slt-core` / `slt-core@example.test`, role `customer`
  - `353`, `slt-admincreated` / `slt-admincreated@example.test`, role `customer`
- Gateway: Stripe test for `11959`; none for `12760`
- Checkout type: admin-detail rendering; underlying `11959` originated from classic checkout, while `12760` was admin-created
- Non-default settings: none changed for this task; frozen early-renew/reactivation/pause settings remained enabled

## Route and context

- `/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions/detail/11959`
- `/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions/detail/12760`
- Browser context: logged-in administrator, session `admin-SLT-ADM-02`

## Reproduction

1. Log in to wp-admin as an administrator.
2. Open ArraySubs → Subscriptions.
3. Search exact subscription ID `11959` and open **View Details**.
4. Read the **Billing Information** card's **Billing Schedule** value.
5. Repeat on exact subscription ID `12760`.

## Expected result

For `_billing_interval=1` and `_billing_period=day`, the authored task and subscriptions list use the singular text `Every 1 day`.

## Actual result

Both detail screens display `Every 1 day(s)`.

## Proof

- `/home/server-manager/slt-evidence/SLT-ADM-02-01-subscription-card.png` shows `Every 1 day(s)` for subscription `11959`.
- `/home/server-manager/slt-evidence/SLT-ADM-02-06-suba-detail.png` shows `Every 1 day(s)` for subscription `12760`.
- Live meta on both records resolved interval `1`, period `day`, and recurring amount `$10.00`.
- Both detail REST requests returned HTTP 200 and browser console errors were empty, so this is rendered output rather than a failed fetch.

## Scope notes and counterexamples

- Reproduces on both a connected Stripe subscription with nine paid orders and a gateway-less subscription with zero orders, isolating the issue from gateway and order history.
- The ArraySubs subscriptions list renders the same interval as `Every 1 day`, demonstrating the correct singular counterexample in the same admin product.
- No setting, subscription, order, or user was modified while reproducing.

## Resolution (2026-08-14)

Disposition: confirmed core admin-detail formatting defect; fixed through the existing translated duration helper.

- Fresh live reproduction on `12760` still rendered `Every 1 day(s)`. The raw REST fields and post meta were correct (`billing_period=day`, `billing_interval=1`), proving the defect was the JSX suffix rather than stored data or gateway behavior.
- Root cause: `SubscriptionDetail.jsx` concatenated the raw period with the literal `(s)` for every non-lifetime subscription. That string was neither grammatically correct nor a valid translation/pluralization pattern.
- The admin detail REST response now adds `billing_schedule_display`, composed server-side from `arraysubs_get_duration_string()` and the translated `Every %s` wrapper. Raw `billing_period` and `billing_interval` remain unchanged for callers.
- The detail UI now renders only that escaped display value. Singular/plural selection therefore uses WordPress `_n()` through the existing shared helper and does not introduce HTML rendering or trust client-provided markup.
- REST checks returned HTTP 200 with: `12760` and `11959` → `Every 1 day`; `12172` (day/3) → `Every 3 days`; `12786` (lifetime) → `Lifetime Deal`.
- `npm run build` completed successfully (only the existing stale Browserslist-data notice; lint/PHPCS intentionally skipped per workspace instructions).
- A fresh authenticated browser session loaded `mainadmin.js` version `27b54b30a4ab31a39438` and chunk `153.151e7040`. Both reported day/1 subscriptions rendered the singular form, the day/3 and lifetime controls rendered correctly, and browser error collection was empty.
- Screenshots: `/home/server-manager/slt-evidence/FIX-LOW-SLT-ADM-02-billing-after-singular.png`, `/home/server-manager/slt-evidence/FIX-LOW-SLT-ADM-02-billing-after-plural.png`, and `/home/server-manager/slt-evidence/FIX-LOW-SLT-ADM-02-billing-after-lifetime.png`.
- All tests were read-only. Subscription/order/user/settings/scheduler/mail state was not mutated.
