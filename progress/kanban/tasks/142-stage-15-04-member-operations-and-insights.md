---
id: 142
title: 'stage-15: 04 Member Operations and Insights'
status: closed
priority: medium
created: 2026-05-19T22:56:19.151852569+02:00
updated: 2026-05-23T12:53:00.31235926+02:00
started: 2026-05-23T08:06:53.45409062+02:00
completed: 2026-05-23T12:53:00.312358338+02:00
tags:
    - qa
    - stage-15
claimed_by: mold-glade
claimed_at: 2026-05-23T12:53:00.312359159+02:00
class: standard
---

Source: stages/15-manage-members/04-member-operations-and-insights.md

[[2026-05-23]] Sat 12:52
QA complete. Customer profile stats after live order/credit changes: Total Spent 5, Total Orders 4, Active Subs 1, Total Subs 3, Store Credit 0, Total Refunds 5; REST/backend matched. Subscription ID link/View route opened detail. Store Credit quick link blocked by issue #110, so add/deduct verified through backend credit controller with +0 QA test top-up and - QA test deduction. Shortcuts verified: subscription customer link routes to #/manage-members/{id}; order edit screen shows Open Member Details and routes to #/manage-members/32; WP user edit button previously verified; WC Analytics Customers row shows Member details URL to #/manage-members/32; Woo order preview action backend renders Member details URL, but modal did not open in headless click so action HTML verified via WooCommerce preview API.
