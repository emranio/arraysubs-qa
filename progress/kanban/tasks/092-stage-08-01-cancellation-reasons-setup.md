---
id: 92
title: 'stage-08: 01 Cancellation Reasons Setup'
status: closed
priority: high
created: 2026-05-19T22:56:11.197271071+02:00
updated: 2026-05-22T03:45:25.153401636+02:00
started: 2026-05-20T13:41:52.913967464+02:00
completed: 2026-05-20T15:10:54.469334361+02:00
tags:
    - qa
    - stage-08
class: standard
---

Source: stages/08-retention/01-cancellation-reasons-setup.md

[[2026-05-20]] Wed 15:10
QA notes: Admin Retention Flow loads with Cancellation Reasons, Retention Offers, Save Settings. Require Cancellation Reason checked. Initial default list missing temporary_pause (issue #59). Configured required reason list to include temporary_pause and custom shipping_issues before Other; cache-busted admin reload shows Reason 6 temporary_pause, Reason 7 shipping_issues, Reason 8 other. Customer cancel modal on #643 opens with EOP wording, Keep Subscription, Continue, reason selector; however modal still exposes stale six-reason list and Continue is enabled before selection (issue #60). Choosing Other shows details textarea and typing works. Keep Subscription closes modal; #643 remains Active/no pending cancellation.

[[2026-05-22]] Fri 03:40
Issue #59 fixed. Raw arraysubs_settings.cancellation.reasons now has seven defaults including temporary_pause / Just need a temporary break before other. Browser Retention Flow shows seven reason rows with Reason 6 - temporary_pause.

[[2026-05-22]] Fri 03:45
Issue #60 fixed. Admin Retention Flow reload shows eight reasons with temporary_pause and shipping_issues before other. Customer #643 cancel modal lists all eight reasons; Continue disabled until reason selected; Other reveals textarea and enables Continue; modal closed without cancelling; #643 active/no pending cancel.
