---
id: 80
title: 'stage-06: 06 Emails on Subscription Creation'
status: closed
priority: high
created: 2026-05-19T22:56:09.341318546+02:00
updated: 2026-05-22T05:21:56.69288011+02:00
started: 2026-05-20T13:41:52.84110775+02:00
completed: 2026-05-20T14:25:31.11215436+02:00
tags:
    - qa
    - stage-06
claimed_by: mold-glade
claimed_at: 2026-05-22T05:21:56.692879829+02:00
class: standard
---

Source: stages/06-initial-lifecycle/06-emails-on-creation.md

[[2026-05-20]] Wed 14:25
QA notes (2026-05-20, Chrome headless via agent-browser + WP-CLI):
- WooCommerce Emails tab loaded. ArraySubs email rows are enabled, including New Subscription, Trial Started, and New Subscription (Admin). Admin recipient is admin@mirror-help.arrayhash.com. Woo email sender settings visible in admin: From name mirror-help.arrayhash.com, From address admin@mirror-help.arrayhash.com. WP Mail SMTP transport config forces from_name admin.arrayhash.com and from_email test@emran.io, so real headers may differ from Woo sender settings but could not be verified without inbox/header access.
- Delivery/content verification blocked: no QA-accessible inbox/catch-all found, WP Mail SMTP content logs are disabled, and Resend API key is send-only; GET /emails returns 401 restricted_api_key. Logged issue #40.
- Email previews checked in WooCommerce settings. New Subscription preview heading: Your subscription is now active! Trial Started heading: Your free trial has started! Admin New Subscription heading: New subscription received. Preview data is mostly sample/empty (N/A, #0, blank dates) and customer previews show footer placeholders {site_title}, {store_address}; logged issue #41.
- Because live delivered emails cannot be opened, task subtasks requiring actual subject/body/sender/header/message-ID verification are not passable in this environment.
Result: FAIL/BLOCKED for live delivery proof, with configuration and preview fallback verified.

[[2026-05-22]] Fri 02:56
Issue #40 rechecked during issue-fix pass. Still blocked: no customer/admin inbox access, no WP Mail SMTP log/content table, logs disabled, no local mail catcher, no read-capable Resend/API credentials. Browser/code cannot prove live delivery/Message-ID/headers. Left issue #40 blocked with required prerequisite.

[[2026-05-22]] Fri 05:05
Issue #37 related doc cleanup: Trial references now use Trial Weekly / 7-day trial instead of old Trial 14-Day / 14-day data.

[[2026-05-22]] Fri 05:21
Issue #41 fixed: email previews now use realistic fallback data for New Subscription, Trial Started, and Admin New Subscription. agent-browser verified product Sample Subscription Product, 9.99 / every month, real dates, John Doe admin/customer sample, no literal footer placeholders.
