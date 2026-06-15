---
id: 41
title: 'stage-06: Email previews use empty sample data and visible footer placeholders'
status: closed
priority: medium
created: 2026-05-20T14:25:13.104066253+02:00
updated: 2026-05-22T05:22:00.618630845+02:00
started: 2026-05-22T05:10:55.087556834+02:00
completed: 2026-05-22T05:22:00.618629903+02:00
tags:
    - qa
    - stage-06
    - email
claimed_by: mold-glade
claimed_at: 2026-05-22T05:22:00.618630725+02:00
class: standard
---

WooCommerce Emails preview on 2026-05-20 via agent-browser. [ArraySubs] New Subscription preview renders heading "Your subscription is now active!" but sample data is Product N/A, Price $0.00 / Every month, blank Start Date, blank Next Payment, and footer shows literal {site_title}, {store_address}. [ArraySubs] Trial Started preview renders heading "Your free trial has started!" but Subscription ID #0, Product N/A, and same footer placeholders. Admin New Subscription preview loads but also uses sample/empty data: customer blank, product N/A, subscription #0, dates empty, payment method N/A. Expected preview fallback should use realistic placeholder data and no literal curly-brace placeholders.

[[2026-05-22]] Fri 05:13
Plan: reproduce preview render from email classes; move sample fallback into BaseSubscriptionEmail template args; preserve child extra placeholders; update New Subscription, Trial Started, and Admin New Subscription templates to use passed display values instead of get_post_meta(0); fix plain footer formatting; verify rendered previews contain realistic values and no literal curly placeholders.

[[2026-05-22]] Fri 05:21
Fixed: BaseSubscriptionEmail now supplies realistic preview fallback args and preserves child placeholders. New Subscription, Trial Started, and Admin New Subscription templates now render display args instead of empty get_post_meta(0) values; plain footers now run through email placeholder formatting. agent-browser verified all three Woo previews show sample subscription/customer/product/price/dates and no literal {site_title}, {store_address}, Product N/A, #0, or /bin/bash.00 fallback.
