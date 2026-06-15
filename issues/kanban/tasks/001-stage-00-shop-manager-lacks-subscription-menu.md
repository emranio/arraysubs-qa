---
id: 1
title: 'Stage 00: Shop Manager lacks Subscription menu access'
status: closed
priority: high
created: 2026-05-19T22:38:44.995585184+02:00
updated: 2026-05-21T20:27:15.454993677+02:00
started: 2026-05-21T20:17:02.815801774+02:00
completed: 2026-05-21T20:27:15.454992745+02:00
tags:
    - qa
    - bug
    - stage-00
claimed_by: mold-glade
claimed_at: 2026-05-21T20:27:15.454993577+02:00
class: standard
---

Found in qa/stages/00-preflight/02-admin-menu-and-capabilities.md, Sub-Tasks 2.2-2.3. Admin sees top-level menu `Subscription`, but Shop Manager does not see the plugin menu and direct access to `admin.php?page=arraysubs-mainadmin#/settings/general` is blocked with permission denial.

[[2026-05-21]] Thu 20:22
Plan: switch ArraySubs admin shell to WooCommerce admin capability; align Settings and Easy Setup REST permissions with manage_woocommerce/manage_options; browser-verify shop manager allowed and customer blocked. Patch applied, running browser QA.

[[2026-05-21]] Thu 20:27
Fixed: admin menu/enqueue capability now uses manage_woocommerce; Settings REST and Easy Setup REST now allow manage_woocommerce/manage_options. Verified: shopmgr opens Settings, Easy Setup, Subscriptions, Profile Builder in agent-browser; cust1 direct settings access shows WordPress permission denial. REST spot-check: shopmgr settings/easy-setup 200; cust1 403.
