---
id: 35
title: Easy Setup standalone checkboxes appear below their labels
status: closed
priority: medium
created: 2026-09-15T19:19:40.613349+06:00
updated: 2026-09-15T19:25:28.363941+06:00
started: 2026-09-15T19:19:40.624334+06:00
completed: 2026-09-15T19:25:28.363941+06:00
tags:
    - bug
    - admin-ui
    - easy-setup
class: standard
---

Active QA task ID / scheduled day / plan: N/A; targeted user-reported local UI fix. Subscription IDs / order IDs: N/A. Affected WordPress user: admin, administrator; numeric ID N/A, not account-specific. Customer IDs / roles: N/A.
Test URL: http://localhost:10013/wp-admin/admin.php?page=arraysubs-mainadmin#/easy-setup. Browser context: isolated agent-browser easy-setup-checkbox-admin session, authenticated admin with Pro active.
Reproduction: open Review setup, choose Start Over, advance to Billing & Renewal Rules and Checkout & Cart Rules. Inspect Allow Early Renew and Hide First billing cycle info, Hide shipping charge info, Hide Duration info.
Expected: checkbox appears on the left, with label and optional description beside it, matching Additional Features & Tools checkbox rows.
Actual: standalone checkbox renders below its label and help text inside a vertically stacked card.
Proof: user screenshots codex-clipboard-45acfac3-0a47-4cf8-9abd-6d9b1c77a172.png and codex-clipboard-3b21e3a6-f435-46d3-80f2-f62968b7eb50.png; core CheckboxField renders the input after QuestionShell label/help inside flex-direction:column. CheckboxGroupField uses the correct choice-item row layout.
Scope: all four field:checkbox questions in the core setup wizard; these settings are Pro-only, while layout is core-owned. Existing QA issue 22 covers a separate fixed modal scroll bug and must remain preserved.
Fix plan: reuse the existing choice-item, choice-copy and choice-label-row markup for standalone checkbox fields; retain input label/help/error associations, boolean state, Pro visibility and footer scroll containment. Rebuild core admin assets. Inspect screenshots at desktop/mobile and verify label clicks, keyboard toggles and step navigation without applying store settings.

Resolution: CheckboxField now reuses the working choice-item row, choice-copy, label and description styles, with the native checkbox on the left and a non-shrinking control. Label, help and error IDs remain associated with the input. Changes are core-owned; Pro setting keys, visibility and behavior are unchanged. The existing toggle and modal scroll-containment fixes were preserved.
Validation: targeted production admin build passed; changed-file whitespace check passed. Browser screenshots inspected at 1280x577 and 390x844. Allow Early Renew toggled true by label click and false by Space. The first checkout label toggled its control, Space toggled the shipping control, and direct clicking toggled duration. All three checked states render correctly at desktop/mobile. Long help text wraps alongside the billing checkbox. Mobile horizontal overflow is false; outer modal scrollTop stays 0; footer and modal bottoms agree at 557 desktop and 824 mobile. Browser errors output is empty. Wizard step navigation succeeded. No settings were applied.
Evidence: /tmp/arraysubs-wizard-checkbox-billing-before.png; /tmp/arraysubs-wizard-checkbox-checkout-before.png; /tmp/arraysubs-wizard-checkbox-billing-fixed.png; /tmp/arraysubs-wizard-checkbox-billing-mobile.png; /tmp/arraysubs-wizard-checkbox-checkout-mobile-fixed.png; /tmp/arraysubs-wizard-checkbox-checkout-desktop-fixed.png. Reproduction video: N/A, static layout issue. Modified source line counts: wizardFields.js 728; _setup-wizard.scss 939. No lint or PHPCS run.
