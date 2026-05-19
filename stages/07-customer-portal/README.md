# Stage 07 — Customer Portal Self-Service

**Goal:** Walk through every action a logged-in subscriber can take from the WooCommerce **My Account → Subscriptions** area. This stage validates the My Subscriptions list, the View Subscription detail page, all status-driven action buttons (Cancel immediately, Cancel at end of period, Undo Cancellation, Skip Renewal, Pause/Resume, Change Plan with both proration modes, Update Payment Method, Auto-Renew toggle, Update Shipping Address) and confirms the action availability matrix matches the manual.

**Prerequisites:**
- Stages 00–02 complete (plugins active, settings configured).
- Stage 03 catalog available — Basic Monthly, Pro Monthly, Enterprise Monthly with linked upgrade/downgrade products, and at least one shipped (physical) subscription product.
- Stage 04 cart rules and Stage 05/06 subscriptions seeded so that test customer `cust1@example.com` owns at least:
  - 1 Active Basic Monthly subscription with linked Pro upgrade
  - 1 Trial subscription
  - 1 On-Hold subscription
  - 1 Cancelled subscription
  - 1 Expired subscription
  - 1 Active subscription on a physical/shipped product
  - 1 Active subscription on a Pro automatic gateway (Stripe sandbox) for the auto-renew toggle test
- ArraySubs Pro active and Stripe (or other automatic gateway) test mode connected for tasks that require gateway-specific checks.
- General Settings → Customer Actions: `Allow Cancellation`, `Allow Plan Switching`, `Allow Reactivation`, `Allow Auto-Renew Toggle` all enabled.
- Skip & Renewal and Pause Subscription enabled, "Customer Can Skip" and "Customer Can Pause" both on.

**Run order:**
1. [01 — My Subscriptions list](01-my-subscriptions-list.md) — Verify the list table, columns, badges, pagination, empty state.
2. [02 — View Subscription detail](02-view-subscription-detail.md) — Verify overview table, retention discount display, coupon discount display, action button rendering.
3. [03 — Cancel immediately](03-cancel-immediate.md) — Cancel Immediately enabled: full immediate-cancel flow with reason and success toast.
4. [04 — Cancel at end of period](04-cancel-end-of-period.md) — Cancel Immediately disabled: scheduled cancellation flow + Action Scheduler check.
5. [05 — Undo scheduled cancel](05-undo-scheduled-cancel.md) — Reverse the EOP cancellation and confirm metadata cleared.
6. [06 — Skip next renewal](06-skip-next-renewal.md) — Choose 1–3 cycles, verify "Skipping X cycle(s)" indicator, then Undo Skip.
7. [07 — Pause and resume](07-pause-and-resume.md) — Vacation mode 30 days, auto-resume scheduling, manual Resume Now early.
8. [08 — Change plan, immediate proration](08-change-plan-immediate-prorate.md) — Basic → Pro with proration preview, checkout, post-switch verification.
9. [09 — Change plan, apply at renewal](09-change-plan-apply-at-renewal.md) — Same flow with deferred switch, pending-switch banner, customer-side cancel pending switch.
10. [10 — Payment method, auto-renew, shipping](10-payment-method-and-shipping.md) — Pro: Manage payment methods link, auto-renew toggle off/on, shipping update inside and within the 3-day cutoff.
11. [11 — Action availability by status](11-action-availability-by-status.md) — Walk every status (Active, Trial, On-Hold, Pending, Cancelled, Expired) and verify the action matrix exactly matches the manual reference table.

**Exit criteria:**
- All 11 task files signed PASS, or failures recorded with screenshots and reproduction steps.
- The exact UI strings recorded verbatim during testing match the manual:
  - "Subscription cancelled successfully."
  - "Subscription scheduled for cancellation at the end of your current billing period."
  - "Scheduled cancellation removed successfully. Your subscription will continue renewing normally."
  - "Successfully skipped X renewal cycle(s)."
  - "Subscription paused. Will auto-resume on [date]."
  - "Skipping X cycle(s)" / "Subscription Paused" indicators
  - "Existing subscription updated from checkout migration" order note
- Action availability matrix from `self-service-actions.md` reproduced 1:1 by direct UI observation.
- All test subscriptions returned to a clean state (cancellations undone, plans reverted) before moving on to Stage 08.
