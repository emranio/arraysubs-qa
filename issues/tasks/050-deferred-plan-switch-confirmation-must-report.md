---
id: 50
title: Deferred plan-switch confirmation must report scheduled result
status: closed
priority: medium
created: 2026-10-18T17:22:02.387326+06:00
updated: 2026-10-18T17:23:13.881457+06:00
started: 2026-10-18T17:22:02.448201+06:00
completed: 2026-10-18T17:23:13.881457+06:00
tags:
    - bug
    - developer-api
    - browser
class: standard
---

Active QA task: Task 7. Scheduled day: 2026-10-18 (Asia/Dhaka).
Plan: qa/developer-support-2026-10-18/plan.md.
Affected subscription: 12485; source order: 15681; customer ID256, developer-qa-1792321048, customer role, example.test email. Local disposable fixtures.
URL: http://localhost:10013/my-account/as-view-subscription/12485/; isolated developer-customer browser session.
Reproduction: disable renewal credit auto-apply, preview a downgrade producing excess credit, confirm the deferred switch.
Expected: success feedback says the plan switch is scheduled for renewal.
Actual: request persists the correct pending switch and leaves product12483 active, but the client success toast is hardcoded to say the plan has changed successfully.
Proof: customerPortal.js confirm() unconditionally selects switchSuccess; backend response has deferred=true and message Plan switch scheduled for next renewal. switch-deferred-preview.png and switch-deferred-scheduled.png show the correct surrounding UI.
Scope: immediate successful changes still need changed-success feedback. No payment/provider flow involved.
Fix plan: use the normalized result deferred flag and translated scheduled-success copy, preserving existing loading/reload behavior; rebuild and repeat browser confirmation.

[[2026-10-18]] Sun 17:23
Verified fixed in the actual customer browser after rebuilding core: confirmation displays Processing… while pending, the success toast says Plan switch scheduled for next renewal., and reload shows the pending-switch banner while the current product12483 and recurring10 stay unchanged. No _store_credit balance was created. Evidence: qa/developer-support-2026-10-18/switch-scheduled-toast.png and switch-deferred-scheduled.png. No browser errors observed.
