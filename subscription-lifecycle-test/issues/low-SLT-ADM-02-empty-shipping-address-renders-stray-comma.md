# Empty shipping address renders an account-name fallback and stray comma

- **Severity:** low
- **Date found:** 2026-08-11
- **Watch day:** D09
- **Originating task:** SLT-ADM-02
- **Plan file:** `qa/subscription-lifecycle-test/kanban/tasks/113-subscription-detail-screen-every-field-dates.md`

## Affected records

- Subscription ID: `12760`
- Order IDs: N/A (no parent, `_order_ids=[]`, and no HPOS relationship orders)
- Product ID: `11927` (`SLT Daily Core`)
- WP user: `353`, `slt-admincreated` / `slt-admincreated@example.test`, role `customer`
- Gateway: none
- Checkout type: N/A; subscription was admin-created
- Non-default settings: none changed for this task; frozen early-renew/reactivation/pause settings remained enabled

## Route and context

- `/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions/detail/12760`
- Browser context: logged-in administrator, session `admin-SLT-ADM-02`

## Reproduction

1. Use customer `353` with every `shipping_*` user-meta field empty.
2. Open ArraySubs → Subscriptions in wp-admin.
3. Open exact subscription `12760`.
4. Inspect the **Shipping Address** card.

## Expected result

An empty shipping address should render as empty or use a clear `No shipping address` state. It should not invent address punctuation or present an account-name fallback as a shipping address.

## Actual result

The card shows `SLT Admincreated`, then a standalone literal comma, even though every shipping-address field is empty.

## Proof

- `/home/server-manager/slt-evidence/SLT-ADM-02-06-suba-detail.png` captures the name and stray comma in the Shipping Address card.
- Read-only `wp user meta list 353` showed blank values for `shipping_first_name`, `shipping_last_name`, `shipping_company`, `shipping_address_1`, `shipping_address_2`, `shipping_city`, `shipping_state`, `shipping_postcode`, and `shipping_country`.
- The detail endpoint returned HTTP 200 and the browser console reported no errors.

## Scope notes and counterexamples

- The Billing Address card on the same subscription correctly renders the populated `billing_*` fields.
- Subscription `11959` / user `347` has a complete shipping address and its Shipping Address card renders correctly without stray punctuation; see `/home/server-manager/slt-evidence/SLT-ADM-02-01-subscription-card.png`.
- The issue is limited to the empty-address presentation path. No record or setting was changed.

