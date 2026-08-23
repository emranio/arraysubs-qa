# SLT-REF-10 — current-cycle safety landmines

Fresh-cycle guide updated 2026-08-22.

- Never select an order/subscription/action by recency; use exact registry IDs and bidirectional
  relationships.
- Never assume the spread offset, gateway capability, settings default, email enablement, action ID,
  provider date or non-SLT2 count.
- Never drain a hook/group. Natural cards wait for cron/provider events.
- D8 is the only date-meta bracket; tasks 112 and 99 execute only their exact allowlisted action IDs.
- D10 is the only Pro-off bracket and must restore activation before diagnosing a failure.
- D11 restores settings and cancels only the signed cohort; D12 is read-only; D13 is the only deletion.
- Stripe/Paddle secrets, full card values and raw webhook secrets never enter evidence.
- The browser must be re-snapshotted after navigation or DOM mutation; refs are never reused.
- A missing source fixture or failed assertion blocks the card and creates/updates the shared
  `qa/issues/` kanban card. There is no unverified-done path.

Re-read `README.md`, `calendar.md`, `watch-schedule.md`, the active card and current source before
each mutation bracket.
