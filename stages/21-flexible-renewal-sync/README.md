# Stage 21 — Flexible Renewal Sync to Next Billing Cycle (Pro)

End-to-end QA for the **per-product** ArraySubsPro feature *Flexible Renewal Sync to Next Billing Cycle*. The billing cycle is split into up to three day segments; the day-of-cycle the customer buys on decides how the first payment is charged. Every task in this stage runs a **complete checkout** and then verifies the required conditions (charge amount, checkout summary rows, subscription meta, renewal schedule) on **all supported gateways**.

Run tasks in numeric order. Tasks 02–04 create the subscriptions that Task 07 (renewal execution) depends on.

---

## Feature contract (what "correct" means)

A monthly product with segment boundaries `b1 / b2` (configured on a nominal 30-day scale; year = 365, week = 7, day = 1, × billing interval) behaves by purchase day `D` (site-timezone day of the current calendar cycle):

| Segment | Condition | Today's charge | First renewal |
|---|---|---|---|
| 1 — Full amount | `D ≤ b1` | Full recurring price | Next cycle boundary, full price |
| 2 — Prorate amount | `b1 < D ≤ b2` | `round(price × (L − D) / L, 2)` | Next cycle boundary, full price |
| 3 — Charge full for next billing cycle | `D > b2` | Full recurring price | **Boundary + 1 cycle** (today's payment covers the cycle that starts at the next boundary; no invoice is generated at the first boundary) |

Where `L` = **actual** calendar length of the current cycle in days (July = 31, February = 28/29) and `D` = day-of-cycle in the **site timezone** (for monthly products this is the calendar day of the month). Calendar days beyond the nominal length always belong to the **last active segment**.

**Partition rule:** active segments always cover the whole cycle. Disabling a segment (toggle) removes it from the picker and the remaining active segments expand to cover its days. At least one segment must stay active. Boundary metas are positional: `seg1_end` ends the *first active* segment, `seg2_end` the *second* (used only when all three are active).

**Cycle boundaries** align to the calendar in the site timezone: months start on the 1st, weeks on the store's start-of-week day, years on January 1 — always at site-local midnight.

**Exclusivity:** the feature is hidden in the product editor and inert at runtime when any of these is true for the product/variation:
- **Different Renewal Price** is enabled,
- **Trial** length > 0,
- billing period is **Lifetime**.

**Gateway support matrix:**

| Gateway | Supported | First charge | Renewal charge |
|---|---|---|---|
| Manual (Direct bank transfer, cheque, …) | Yes | WC order total | Pending renewal order + "pay invoice" email |
| Stripe (via WooCommerce Stripe Gateway, test mode) | Yes | WC order total via Payment Element | Off-session PaymentIntent for the renewal order total |
| Paddle / PayPal (ArraySubsPro gateways) | **No — by design** | Hidden from checkout when a flex-sync product is in the cart; forced submits are rejected with a validation error | n/a |
| Cash on delivery | n/a | Always hidden for subscription carts (pre-existing rule) | n/a |

---

## Forcing a segment on "today"

Purchases always happen "today", so testers steer the outcome by moving the **boundaries** around today's day-of-cycle `D` (for monthly: today's day of the month in the site timezone — check **Settings → General → Timezone** first):

- Force **segment 1**: set `b1 ≥ D` (e.g. D=8 → boundaries 10 / 20).
- Force **segment 2**: set `b1 < D ≤ b2` (e.g. D=8 → boundaries 5 / 20).
- Force **segment 3**: set `b2 < D` (e.g. D=8 → boundaries 3 / 6).

If `D` is 1 or 2, segment-2/3 runs need boundaries like 1/2 — still valid. Note expected amounts in the sign-off block using the formulas above **before** placing the order.

## Where to verify each condition

- **Checkout summary rows** — classic checkout order review table: *Renewals*, *Today's charge*, *Next charge*, and (segment 3 only) *First billing cycle*.
- **Order** — WP-Admin → WooCommerce → Orders → the new order: line total, order total, order item meta `_renewal_sync_enabled`, `_renewal_sync_first_charge_mode`, `_renewal_sync_first_full_renewal_date`, `_renewal_sync_initial_recurring_amount`.
- **Subscription** — the order page links *Subscription: #ID*; the ArraySubs admin detail page shows **Next Payment**, **Recurring Amount**, **Completed Payments**, **Last Payment**. Post meta to verify via WP-CLI (`wp post meta list <sub_id> --keys=_next_payment_date,_renewal_sync_enabled,_renewal_sync_first_charge_mode,_renewal_sync_cycle_start_date,_renewal_sync_first_full_renewal_date,_recurring_amount`):
  - `_renewal_sync_enabled = yes`
  - `_renewal_sync_first_charge_mode = full | prorate | next_cycle` (must match the segment)
  - `_next_payment_date` = expected boundary at **site-local midnight stored as UTC**
- **Scheduled jobs** — WP-Admin → Tools → Scheduled Actions: filter by hook `arraysubs_process_renewal` / `arraysubs_generate_renewal_invoice` with the subscription ID as args. Invoice job runs ~6 h before the renewal job.
- **Product meta** — `_arraysubs_flex_sync_enabled`, `_arraysubs_flex_sync_seg1_end`, `_arraysubs_flex_sync_seg2_end`, `_arraysubs_flex_sync_seg1_active/2/3`.
- **Logs** — keep DevTools console open; check `wp-content/debug.log` after every task. Any new PHP notice/warning/error = FAIL.

## Time-traveling a renewal (Task 07)

Manual **Next Payment Date** edits are rejected by the admin/REST on purpose. To execute a renewal without waiting:

1. WP-CLI: `wp post meta update <sub_id> _next_payment_date "<UTC datetime in the past, e.g. yesterday 00:00:00>"`.
2. WP-Admin → Tools → Scheduled Actions → run the pending `arraysubs_generate_renewal_invoice` then `arraysubs_process_renewal` actions for that subscription (or `wp action-scheduler run`).
3. **Expected quirk (by design):** for the *first* synced renewal (completed payments ≤ 1), after payment the next date re-anchors from `_renewal_sync_first_full_renewal_date` — not from the time-traveled value.

## Environment prerequisites (gate for the whole stage)

- ArraySubs + ArraySubsPro builds that include the `FlexibleRenewalSync` feature are deployed to the target site (the section must appear in the product editor — Task 01 verifies this and gates the stage).
- Direct bank transfer enabled; Stripe gateway connected in **test mode**.
- ArraySubs → Settings: global **Sync renewals to next billing cycle** OFF (this stage tests the per-product feature; one cross-check in Task 09 covers the interplay).
- A clean test customer per gateway run (or reuse `sync-*@example.test` customers from Stage 05).
- Note the site timezone and today's day-of-cycle `D` in every sign-off block.
