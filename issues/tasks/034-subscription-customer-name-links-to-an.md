---
id: 34
title: Subscription customer name links to an unregistered customers route
status: closed
priority: medium
created: 2026-09-15T19:08:38.766706+06:00
updated: 2026-09-15T19:12:48.794184+06:00
started: 2026-09-15T19:08:55.07031+06:00
completed: 2026-09-15T19:12:48.794184+06:00
tags:
    - bug
    - admin
    - subscriptions
class: standard
---

Active QA task ID / scheduled day / plan: N/A; targeted user-reported local fix, no active QA cycle.
Affected subscription ID: 5999. Related order ID: 5998.
Affected customer: ID 2, display name Box Optional QA, email box-optional-20260910@example.test; login and role not yet checked. Browser user: admin, administrator.
Exact route: http://localhost:10013/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions/detail/5999. Browser context: isolated agent-browser customer-link-admin session on the local site.
Reproduction: open subscription 5999 and click Box Optional QA under Customer Information.
Expected: http://localhost:10013/wp-admin/admin.php?page=arraysubs-mainadmin#/manage-members/2 displays the selected member.
Actual: the rendered link targets #/customers/2, which has no registered React route; user reports a blank page.
Proof: browser snapshot shows Box Optional QA linking to #/customers/2. Core SubscriptionDetail.jsx line 1002 uses /customers/${data.customer.id}; Main.jsx registers /manage-members/:userId?.
Scope: the link is shared by all subscription detail pages. Core feature log/member links and Pro MemberInsight already use /manage-members/{id}. No subscription or payment data change is required.
Fix plan: change the customer name Link to the existing manage-members route, rebuild the admin assets, and click through from subscription 5999 to verify member 2 renders. Capture before/after browser evidence and close this issue after verification.

Resolution: changed the Customer Information name link in core SubscriptionDetail.jsx to /manage-members/${data.customer.id}. Existing Pro MemberInsight URLs already match this route, so no Pro change was needed. Targeted production admin build passed; source remains 1873 lines. No lint or PHPCS run.
Browser verification: reproduced navigation to #/customers/2 and visually confirmed the blank content area. After a full admin reload, the customer name href is #/manage-members/2; clicking it displays Box Optional QA, box-optional-20260910@example.test, and subscription rows #5999 and #6003. Member page identifies the login as box optional.qa and role Customer. Browser errors output is empty.
Evidence: /tmp/arraysubs-customer-link-before.webm; /tmp/arraysubs-customer-link-before.png; /tmp/arraysubs-customer-link-blank.png; /tmp/arraysubs-customer-link-fixed-source.png; /tmp/arraysubs-customer-link-fixed-member.png. The URL glob wait timed out despite successful navigation; the actual current URL and screenshot confirmed the result. Existing unrelated issue tasks and readme/screenshot work were preserved.
