---
id: 95
title: Elementor logged-in users control renders as a checkbox instead of a switch
status: closed
priority: low
created: 2026-10-24T03:12:26.867697+06:00
updated: 2026-10-24T03:13:17.761885+06:00
started: 2026-10-24T03:12:30.226026+06:00
completed: 2026-10-24T03:13:14.213171+06:00
tags:
    - bug
    - qa
    - elementor
class: standard
---

Active QA: task 2; scheduled day 2026-10-24 (Asia/Dhaka); plan qa/unified-content-restrictions-2026-10-24/plan.md.
Affected subscriptions/order IDs: N/A.
Affected user: WordPress administrator ID 1, login admin, role administrator; customers N/A.
Fixture: disposable Elementor page ID 20008, QA unified author Elementor 20261024.
Exact URL: http://localhost:10013/wp-admin/post.php?post=20008&action=elementor; isolated admin browser session unified-author-elementor.
Steps: Open the page in Elementor. Select the Container in Structure, open Advanced, expand ArraySubs Content Restrictions, enable restriction, enter 0 in Minimum Lifetime Spend.
Expected: Logged-in users is an enabled, checked native-looking switch that becomes non-editable while spend 0 applies.
Actual: The control becomes checked and disabled correctly but displays a small square checkbox rather than the requested switch. Other native Elementor switches display normally.
Proof: qa/unified-content-restrictions-2026-10-24/evidence/elementor-spend-zero.png and elementor-spend-zero.txt show the square login checkbox beside native Enable Restriction, admin and default-message switches.
Scope: visual only; custom arraysubs_login_switcher type only. Native Elementor switches and access prerequisite runtime still work.
Fix plan: Keep the native-derived custom switcher and include Elementor native switcher CSS class; verify the real editor after reload, including spend-0 lock and subsequent clear/unlock.

[[2026-10-24]] Sat 03:13
Implemented: added native elementor-control-type-switcher class to the native-derived custom login control. Real-browser verification after publish/reload: the login control now has the same Yes/No switch shape as Enable Restriction, admin and default-message switches; spend 0 still checks/disables its real input. Clearing spend unlocks the switch, and it can be switched off. Evidence elementor-switch-native-class.png and elementor-login-unlocked.png/.json under the active QA evidence directory. No JS errors observed; no build required for PHP control metadata.
