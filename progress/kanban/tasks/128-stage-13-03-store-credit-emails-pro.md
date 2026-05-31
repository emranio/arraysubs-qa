---
id: 128
title: 'stage-13: 03 Store Credit Emails (Pro)'
status: closed
priority: medium
created: 2026-05-19T22:56:16.980953245+02:00
updated: 2026-05-24T11:44:35.516690672+02:00
started: 2026-05-23T08:06:53.441706409+02:00
completed: 2026-05-23T10:49:16.516044135+02:00
tags:
    - qa
    - stage-13
class: standard
---

Source: stages/13-emails/03-store-credit-emails.md

[[2026-05-23]] Sat 10:49
Stage 13 Task 03 complete with delivery blocker. Browser/WC Emails list shows four Store Credit entries with Manage links: Added/Used/Expiring/Expired. CLI/WC mailer confirms all four registered/enabled, default subjects/headings match plan, HTML type. wp_mail interception used because inbox proof blocked by #40. Captured default Added, Used, Expiring, Expired to cust3; no raw placeholders. Custom Credit Added subject/heading/additional content saved and captured; additional content present, but money placeholders rendered HTML spans in subject, issue #88. Credit Used toggle off: 0 Used mails; Added still fired. Re-enabled Used: 1 mail. Plain text mode for Added: Content-Type text/plain, no HTML. Restored Credit Added defaults/html and removed Credit Used override. Debug log: no new product email errors beyond old AS fatal and prior QA-command typo warning.

[[2026-05-24]] Sun 11:44
Issue #88 fixed/verified. Store Credit email money placeholders now render as plain decoded price text in customized subjects/headings. wp_mail capture for Credit Added custom subject returned '[QA] cust3 just got 0.00 in credit (balance now 5.00)' with no HTML markup; settings restored.
