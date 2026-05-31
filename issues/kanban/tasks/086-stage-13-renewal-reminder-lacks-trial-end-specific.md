---
id: 86
title: 'stage-13: Renewal reminder lacks trial/end-specific wording'
status: closed
priority: medium
created: 2026-05-23T10:39:47.777685168+02:00
updated: 2026-05-24T11:36:31.964398922+02:00
started: 2026-05-24T11:14:03.538796533+02:00
completed: 2026-05-24T11:36:31.96439797+02:00
tags:
    - qa
    - stage-13
    - email
    - copy
claimed_by: shell-quartz
claimed_at: 2026-05-24T11:36:31.964398811+02:00
class: standard
---

Original task: stages/13-emails/01-customer-emails-trigger-matrix.md\n\nExpected:\n- Trial Ending Soon should use wording such as Your trial ends in 3 days and explain conversion.\n- Subscription Expiring Soon near fixed end should indicate the subscription is about to end / final invoice behavior.\n\nObserved via captured rendered WooCommerce email output with wp_mail intercepted (delivery itself blocked by issue #40):\n- Trial subscription #663 using renewal_reminder produced subject [mirror-help.arrayhash.com] Your subscription #663 renews soon and body: This is a friendly reminder that your subscription will renew in 3 days. Product Trial Weekly. It does not mention trial ending.\n- Expired/fixed-end subscription #673 using renewal_reminder produced subject [mirror-help.arrayhash.com] Your subscription #673 renews soon and body says it will renew in 3 days, even though next payment date is in the past and end date is set. It does not mention subscription ending/final invoice behavior.\n\nExpected: trial and fixed-end contexts should render context-specific subject/body or there should be separate email classes/triggers for these two cases.\n\nDelivery/inbox/body-header proof remains blocked by issue #40.

[[2026-05-24]] Sun 11:17
Paused: user reported a refund/status regression after the store-credit refund fix; switching to that regression before continuing this email-copy issue.

[[2026-05-24]] Sun 11:33
Plan: detect renewal_reminder context from live subscription meta/status, set context-specific subject/heading/template vars for trial-ending and fixed-end expiring reminders, update HTML/plain templates, then verify with intercepted email rendering for subscription #663 and #673.

[[2026-05-24]] Sun 11:36
Fixed/verified. RenewalReminderEmail now detects trial reminders from arraysubs-trial status and ending/expiring reminders from stored _end_date, then applies context-specific subject, heading, date label, and HTML/plain body copy. Verification via intercepted wp_mail because delivery/inbox remains blocked by issue #40: subscription #663 subject '[mirror-help.arrayhash.com] Your trial for Trial Weekly ends soon' and body includes 'Your trial ends in 3 days' plus paid-plan conversion wording; subscription #673 subject '[mirror-help.arrayhash.com] Your subscription #673 is ending soon' and body includes final-invoice/end wording and End Date label. php -l passed for email class and both templates.
