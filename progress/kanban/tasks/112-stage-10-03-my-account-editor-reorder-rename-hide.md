---
id: 112
title: 'stage-10: 03 My Account Editor — Reorder, Rename, Hide, Custom Endpoint'
status: closed
priority: medium
created: 2026-05-19T22:56:14.200890504+02:00
updated: 2026-07-08T02:18:27.456659+06:00
started: 2026-05-20T13:41:52.948668565+02:00
completed: 2026-07-08T02:18:27.46911+06:00
tags:
    - qa
    - stage-10
class: standard
---

Source: stages/10-profile-builder/03-my-account-editor.md

[[2026-05-20]] Wed 15:34
QA notes: Profile Builder > My Account admin page loads. Observed sortable built-in account tabs (Dashboard, Orders, Subscriptions, Downloads, Log out), disable/expand controls, Add Custom Item button, and help text covering linked pages, direct access protection, permalink flushing. Full reorder/rename/hide/custom Help Center endpoint and reserved slug conflict error were not executed end-to-end; Help Center page was seeded after missing fixture issue #66.

[[2026-05-22]] Fri 04:06
Issue #66 fixed: Stage 10 users/pages/assets normalized; qa_admin Profile Fields settings and pf_test My Account Account Details browser spot-checked.
