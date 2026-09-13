---
id: 28
title: Onboarding payment link is taller than setup action buttons
status: closed
priority: low
created: 2026-09-14T00:43:09.669313+06:00
updated: 2026-09-14T00:49:20.330315+06:00
started: 2026-09-14T00:43:09.687217+06:00
completed: 2026-09-14T00:49:20.333125+06:00
tags:
    - bug
    - admin-ui
    - onboarding
class: standard
---

Active QA task ID and scheduled day: N/A (targeted verification of the requested onboarding card).
Plan markdown path: N/A.
Affected subscription/order IDs: N/A. Affected product IDs: N/A.
Affected WordPress user/customer: admin, administrator; numeric ID/email N/A.
Exact URL: http://localhost:10003/wp-admin/admin.php?page=arraysubs-mainadmin#/overview
Browser context: authenticated local administrator, agent-browser overview-onboarding, 1600x1100 viewport.
Reproduction: Load the overview with completed onboarding tasks and compare Review setup, Add another product, and Manage payments.
Expected: All three action controls have the same height and alignment.
Actual: Manage payments is taller because an anchor uses content-box sizing while the buttons use border-box sizing.
Proof: /tmp/arraysubs-onboarding-desktop.png shows the payment link extending above the adjacent buttons.
Scope/counterexamples: Only the new onboarding action link; the task panels themselves align. No subscription or order changes involved.
Fix plan: Apply border-box to the shared onboarding action style, rebuild, then compare rendered action heights and mobile wrapping.
Repro video: N/A (static layout issue).

Resolution: Added `box-sizing: border-box` to the shared onboarding action selector and rebuilt the core assets.
Verification: Real browser at 1600px and 390px viewport widths reports all three actions at 38px tall. Mobile uses one column, with no card or action overflow. Mixed completion states and the completed state both align correctly. Screenshots: /tmp/arraysubs-onboarding-mixed-desktop.png and /tmp/arraysubs-onboarding-mobile-final.png. Browser errors: none.
Related flow checks: Setup opens the existing wizard; product opens the existing quick-product modal; payment navigates to /wp-admin/admin.php?page=wc-settings&tab=checkout. Temporarily clearing the settings marker and disabling the local test Stripe gateway verified independent pending states. Both values were restored and verified against their originals. No subscription, order, product, or credential values were changed.
