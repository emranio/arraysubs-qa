# Stage 12 — Store Credit (Pro)

**Goal:** Verify the entire Store Credit Pro module — settings persistence, manual admin adjustments, the global credit history with source/type filtering, the Store Credit purchase product type (with bonus percentage), credit consumption at checkout and on auto-applied renewals, refund-to-credit conversion, and the four customer-facing credit emails (Added, Used, Expiring, Expired) including expiration time-travel.

**Prerequisites:**
- Stages 00–11 complete.
- ArraySubsPro is active.
- A baseline subscription product (`Standard Weekly` from Stage 03, $19.99/week) exists, plus `Basic Monthly` ($29.99/month) for any monthly-specific check.
- `cust1@test.local` and `cust3@test.local` exist and can log in. `cust3@test.local` is reserved for the credit / refund scenarios so that earlier customers retain their state.
- Stripe is already configured (test mode) and is the primary gateway under test. BACS / Cash on Delivery is available as the manual fallback.
- Time-travel method available — either editing `_next_payment_date` post meta, running `wp action-scheduler run --hooks=arraysubs_*`, or using a date-mocking plugin.

**Run order:**
1. [01 — Store credit settings](01-store-credit-settings.md) — Enable the system; configure auto-apply, allow at checkout, minimum order amount, and a 365-day expiration; verify all settings persist.
2. [02 — Manual adjustments](02-manual-adjustments.md) — Search a customer, add $50 with a note, verify balance, transaction history, and customer portal display.
3. [03 — Credit history filters](03-credit-history-filters.md) — Filter the global Credit History by every source and by credit/debit type and verify counts.
4. [04 — Buy credits product](04-buy-credits-product.md) — Create a Store Credit product (fixed and custom amount with a 10% bonus). Customer purchases credit, verify balance increases by purchase + bonus.
5. [05 — Purchase with credit](05-purchase-with-credit.md) — Customer pays for a `Standard Weekly` ($19.99) subscription using their existing credit at checkout. Verify $19.99 deducted. Test minimum-order-amount block.
6. [06 — Auto-apply on renewal](06-auto-apply-on-renewal.md) — Time-travel through five weekly renewals of a `Standard Weekly` ($19.99/week) subscription while the customer holds $100 credit. Verify each renewal is zeroed out and balance drops to ~$0 after the fifth cycle.
7. [07 — Refund to credit](07-refund-to-credit.md) — Refund a paid order as store credit. Verify balance increases, transaction source = `refund`, refund history on the order page.
8. [08 — Store credit emails and expiration](08-store-credit-emails-and-expiration.md) — Trigger Credit Added, Credit Used, Credit Expiring (time-travel), and Credit Expired. Confirm expired amount is deducted from balance.

**Exit criteria:**
- Every Store Credit setting saves and reloads with the persisted value.
- Manual admin add/deduct mutates the balance correctly and produces a Credit History row.
- Credit History filters return the right rows for each source and each type.
- A Store Credit product type can be created and purchased; balance increases by base + bonus exactly.
- Credit auto-applies to weekly renewals that meet the minimum order amount across five consecutive cycles, and is blocked when the order is below the minimum.
- Refund-to-credit converts a refund into customer credit without firing a gateway refund and is logged with source = `refund`.
- All four Store Credit emails (Credit Added, Credit Used, Credit Expiring, Credit Expired) arrive at `cust3@test.local`'s inbox with correct placeholders rendered, and an expired credit is removed from the customer's balance.
