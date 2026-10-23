---
id: 106
title: Elementor unrelated heading save can silently turn current ANY restrictions into login-only access
status: closed
priority: critical
created: 2026-10-24T03:57:51.113784+06:00
updated: 2026-10-24T04:09:43.296955+06:00
started: 2026-10-24T03:58:56.51779+06:00
completed: 2026-10-24T04:09:43.255062+06:00
tags:
    - bug
    - qa
    - elementor
    - security
class: standard
---

Active QA task 3, scheduled 2026-10-24 (Asia/Dhaka); plan qa/restriction-final-access-audit-2026-10-24/plan.md.
Subscriptions/order IDs: N/A. Disposable affected page: 20192, QA final access author_elementor 20261024.
Affected actors: administrator ID 1, admin / dev-email@wpengine.local, roles administrator and demo_member; member ID 224, demo260927_220 / demo.260927.220@example.test, customer/demo_member/demo_premium; nonmember ID 455, qa_final_20261024 / qa-final-20261024@example.test, customer; guest ID 0.
Editor URL: http://localhost:10013/wp-admin/post.php?post=20192&action=elementor, isolated authenticated final-elementor session. Storefront URL: http://localhost:10013/qa-final-access-author_elementor-20261024/, separate final-elementor-guest/member/customer/admin sessions.
Reproduction: Store an unversioned transitional rule with arraysubs_enable=yes, arraysubs_login_required=yes, nonexistent role plus nonexistent product joined with OR, admin bypass off, retained custom message, and explicit arraysubs_custom_message_enabled empty/off; no arraysubs_mode/version. Confirm all four actors are denied with shared default. Open editor, select only the Heading, edit its title, Publish without opening Container restriction controls, then revisit frontend.
Expected: Same audience and default message; all four actors denied, because the current login prerequisite never satisfies the failing ANY conditions.
Actual: Elementor drops the empty message flag and no version marker is present, because format initialization runs only when restriction control views are rendered. The saved rule is then recognized as legacy login-only. Member, nonmember and administrator all receive protected content; guest message changes from shared default to retained custom text.
Proof: evidence/elementor-transitional-before.json shows private=false for all four actors; elementor-transitional-after-bug.json shows private=true for all three logged-in actors. elementor-unopened-before-save-model.json shows initialized=false, empty version, and serialized rule missing the explicit flag. elementor-transitional-saved-after-bug.txt proves dropped raw flag. Screenshots elementor-unopened-leak-member.png and elementor-unrelated-heading-save.png show exposed content and ordinary heading-only editing.
Known scope: current transitional rules whose explicit empty flag is the only current-format evidence, especially login=yes with failing member conditions. Opening the restriction panel initializes format and preserves the flag, so the prior panel-open QA path passed. Original version1 rules and explicit version2 rules are not evidence of this failure.
Fix plan: Initialize format and preserve explicit empty booleans for every container in the document before ordinary page saves, independently of panel interaction; cover nested/newly-added containers and document reload. Repeat the actual four-actor browser round trip, then old ordinary ANY absent/off, one-pass/all-fail, visibility and cache/native-render checks.

Independent final review also reproduced a related format-stamping access change: imported arraysubs_access_version=-1 with explicit current custom-message off is denied by PHP before save, but the editor nonzero-version branch coerces it to legacy 1, permitting logged-in users. The fix must classify the original raw record using PHP-equivalent integer parsing before assigning a canonical marker. Review also found inverse-only transitional message choices need projection into the positive custom-message UI and retained explicit-off serialization. Both are included in the bounded final verification.

Fixed and verified in the actual Elementor authoring flow. Document-wide preparation now preserves format and explicit off fields before any unrelated save, with PHP-equivalent raw version classification and inverse-message projection. Thirteen real Heading-only Publish/reload round trips passed across four actors (104 before/after page views), including both empty transitional flags, legacy ANY absent/off/pass/fail, empty/custom guest visibility, login override, negative/numeric-prefix/NBSP markers and a nested legacy child. Native original-HEAD adapter comparison, nested buffering and actual document cache path passed 433/433. Inverse-only custom switch ON->OFF Publish/reload also retained OFF, hidden editor and custom text, with shared default frontend output. Evidence/report: qa/restriction-final-access-audit-2026-10-24/evidence/elementor-final-audit.md and associated files. JS syntax/diff whitespace pass; source files below 3000 lines.

Bounded independent review of the shared numeric marker helper confirmed one remaining variant in the direct Elementor helper: JSON numeric9223372036854775808 casts to PHP_INT_MIN (legacy when oldmode present), while generic JS parsing treated it as positive current. Actual WP member output changes from denied to private if stamped2. Evidence elementor-numeric-overflow-before.txt. Reopened to adopt the already-reviewed signed64 numeric cast branch and verify one actual Heading-only oldguest-visibility save; no other scope added.

Final overflow variant is fixed with the shared PHP-equivalent signed64 numeric conversion. Real Heading-only Publish/reload guest-visibility test with JSON numeric2^63 preserves guest-only audience for all four authenticated contexts; imported marker becomes equivalent legacy1. Final totals14 real unopened-panel saves /112 before-after actor views; native433/433 unchanged. Updated evidence/elementor-final-audit.md. JS syntax and diff whitespace pass; editor.js421 lines.
