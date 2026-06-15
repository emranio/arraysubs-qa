---
id: 4
title: 'Stage 00: Menu label/docs mismatch (`Subscription` vs `ArraySubs`)'
status: closed
priority: low
created: 2026-05-19T22:38:45.159299478+02:00
updated: 2026-05-22T05:44:58.352448016+02:00
started: 2026-05-22T05:40:16.797161395+02:00
completed: 2026-05-22T05:44:58.352446203+02:00
tags:
    - qa
    - docs
    - stage-00
claimed_by: mold-glade
claimed_at: 2026-05-22T05:44:58.352447906+02:00
class: standard
---

Found in qa/stages/00-preflight/02-admin-menu-and-capabilities.md. Admin top-level plugin menu renders as `Subscription`, not `ArraySubs` as referenced across stage docs.

[[2026-05-22]] Fri 05:42
Plan: update core admin menu registration so top-level wp-admin label matches stage docs (ArraySubs). Keep slug/capability/submenus unchanged. Verify PHP syntax, then browser-check admin sidebar label.

[[2026-05-22]] Fri 05:44
Fixed: core wp-admin top-level menu title now ArraySubs in MainAdmin Hooks. Verified php -l and agent-browser admin sidebar labels: Dashboard, Posts, ..., ArraySubs, WooCommerce...
