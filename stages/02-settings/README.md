# Stage 02 — General & Toolkit Settings

**Goal:** Manually exercise every section of **ArraySubs → Settings → General** and **ArraySubs → Settings → Toolkit**, plus the Import / Export tools on **ArraySubs → Easy Setup**. Confirm every toggle, dropdown, and number field saves, persists across reload, and validates bad input. This stage produces the baseline configuration that every later checkout, lifecycle, and portal stage relies on.

**Prerequisites:** Stages 00 and 01 complete. Both ArraySubs and ArraySubsPro active. A fresh export of `arraysubs-settings-YYYY-MM-DD.json` taken at the end of Stage 01 is on hand.

**Run order:**
1. [01 — General Settings each section](01-general-settings-each-section.md) — Multiple Subscriptions, Checkout, Trials, Button Text, Grace Period, Email Reminder Schedule, Customer Actions, Cancellation, Auto-Renew (Pro).
2. [02 — Toolkit Settings](02-toolkit-settings.md) — Admin bar, wp-admin restriction, login page hiding, login-as-user, multi-login (Pro).
3. [03 — Import / Export Settings](03-import-export-settings.md) — Export, modify, re-import, verify revert; export with Pro active vs inactive.
4. [04 — Validation and error handling](04-validation-and-error-handling.md) — Negative numbers, empty required fields, out-of-range integers; confirm no good values are lost on validation failure.

**Exit criteria:**
- Every toggle and field on both settings pages has been flipped, saved, and confirmed to persist after reload.
- A round-trip export → modify → import correctly reverts the modified sections.
- Invalid inputs are rejected with a visible error and previously-saved good values are not wiped.
- A clean export of the post-stage configuration is saved as `post-stage-02.json` for use by later stages.
