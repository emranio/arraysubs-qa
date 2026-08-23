# SLT-REF-07 — early and late renewal invariants

Fresh-cycle guide updated 2026-08-22.

## Early renewal

- Verify the gateway advertises support before showing the action.
- Charge the exact full renewal total unless the active discount contract says otherwise.
- Advance from the original due/scheduled-cycle anchor, not the button-click time.
- Replace obsolete invoice/charge actions and prove one order/charge/mail.

## Late renewal

- A past-due owned cycle remains processable.
- Resolve the renewal by scheduled-cycle and reverse relationship, never recency.
- Advance from `_renewal_scheduled_date` or the immutable sync anchor.
- If catch-up leaves another due date in the past, record whether the current code queues or safely
  withholds the next pair; any mismatch against the live contract is an issue, not a waiver.

Sources: `arraysubspro/src/Features/EarlyRenew/`,
`arraysubs/src/Features/RecurringBilling/Services/RenewalProcessor.php`, and
`arraysubs/src/Features/Subscriptions/Services/OrderIntegration.php`.
