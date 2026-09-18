---
id: 40
title: Per-post rich message link dialog appears behind restriction modal
status: closed
priority: medium
created: 2026-09-18T18:45:09.018377+06:00
updated: 2026-09-18T18:47:12.6292+06:00
tags:
    - members-access
    - rich-text
class: standard
---

QA task RM-4, scheduled 2026-09-18; plan qa/restricted-messages-2026-09-18/plan.md.
Subscription/order IDs: N/A. Customer/user: ID 1, admin, administrator.
URL: http://localhost:10003/wp-admin/post.php?post=28480&action=edit (page 28480).
Browser: agent-browser with Edge 153.
Steps: open Edit restriction; select text in Restricted Message; click Insert/edit link; enter a URL; click OK.
Expected: link dialog appears above the restriction modal and can be submitted.
Actual: OK is covered by the restriction modal; TinyMCE dialog z-index is 65536 and WordPress modal overlay is 100000.
Proof: qa/restricted-messages-2026-09-18/evidence/post-rule-link-obscured.png; browser click reports covered by p#inspector-select-control-3__help.
Scope: per-post restriction modal only. Gutenberg sidebar and standalone CPT rule link dialogs work.
Fix plan: scope TinyMCE floating panel/backdrop stacking above the active restriction modal, rebuild, then repeat link editing and persistence.

Verified 2026-09-18 after rebuilding: link dialog accepts the URL and OK works above the restriction modal. Applied the rule, saved the page, reloaded, and guest output retains the italic message with the full absolute link. Evidence: qa/restricted-messages-2026-09-18/evidence/post-rule-guest-final.png.
