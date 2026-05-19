# Stage 19 — Refund Management

**Goal:** Verify ArraySubs' refund engine end-to-end: every option in **ArraySubs → Settings → Refunds**, the three "Refund on Cancellation" behaviours (immediate / end-of-period / none), prorated refund math, gateway refund routing, the Pro **Refund-to-Store-Credit** flow, and the subscription-state side-effects after a full refund. The pass criteria for this stage is that refund policy and refund processing match exactly what the manual at `arraysubs-usermanual/markdowns/retention-and-refunds/refund-management.md` says they will do — both in WooCommerce records and on the subscription itself.

**Prerequisites:**
- Stage 02 — Settings completed (general settings reachable).
- Stage 06 — Initial lifecycle completed (we have at least one paid subscription with a parent order).
- Stage 14 — Admin Subscriptions completed (admin subscription detail screen is verified working).
- Stage 08 — Retention completed (cancellation modal flow is already verified, so refund tests can rely on it).
- ArraySubs Pro active for Task 05 (Refund-to-Credit) — the rest run on Free.
- **Stripe is already configured** in Test mode and is the assumed gateway for "Auto gateway refund" sub-tasks (it supports the WooCommerce refund API). Only **Stripe + manual** payments are tested in this regression cycle; PayPal and Paddle are out of scope. For pure Free runs, `WooCommerce → Cheque/BACS` plus a manual refund check is acceptable for verifying the flow but will not exercise gateway refund.

**Run order:**
1. [01 — Refund settings persistence](01-refund-settings.md) — Toggle every refund setting, save, reload, and confirm persistence.
2. [02 — Prorated refund on cancellation](02-prorated-refund-on-cancellation.md) — Prorated refund math + auto gateway refund (via Stripe) on a **Standard Weekly $19.99/wk** subscription mid-cycle (≈ $10 prorated refund).
3. [03 — Refund at end of period](03-refund-at-end-of-period.md) — End-of-period refund timing: subscription stays Active, refund fires when EOP cancellation actually executes.
4. [04 — No automatic refund](04-no-automatic-refund.md) — Cancellation without any refund created, then verify manual WC refund still works.
5. [05 — Refund to credit (Pro)](05-refund-to-credit-pro.md) — Refund as Store Credit instead of gateway refund, balance update, customer-visible record.
6. [06 — Subscription state after full refund](06-subscription-state-after-full-refund.md) — Full refund of parent order auto-cancels subscription, unschedules actions, notifies customer.

**Exit criteria:**
- Every setting in **ArraySubs → Settings → Refunds** persists across save / reload.
- All three "Refund on Cancellation" modes produce the documented behaviour and are distinguishable in the WC order screen and subscription notes.
- Prorated refund formula matches `(Recurring Amount / Days in Cycle) * Unused Days` to within the documented per-interval approximation (7 days for weekly, 30 days for monthly).
- Auto gateway refund actually issues money back through the gateway (or is correctly skipped when disabled).
- Pro: refund as store credit increases the customer's balance by the refund amount, never hits the gateway, and is visible in the customer's My Account → Orders / Store Credit pages.
- A full refund of the parent order with "Allow Immediate Refund" set unschedules all future Action Scheduler jobs for that subscription and sends the **Subscription Cancelled** email.
