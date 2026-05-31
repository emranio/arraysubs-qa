---
id: 111
title: 'stage-10: 02 Avatar Upload Settings'
status: in-progress
priority: medium
created: 2026-05-19T22:56:14.105203824+02:00
updated: 2026-05-22T04:06:17.602210795+02:00
started: 2026-05-20T13:41:52.947893486+02:00
tags:
    - qa
    - stage-10
claimed_by: mold-glade
claimed_at: 2026-05-22T04:06:17.602210554+02:00
class: standard
---

Source: stages/10-profile-builder/02-avatar-settings.md

[[2026-05-20]] Wed 15:34
QA notes: Avatar Settings section is visible on Profile Form admin page with Enable avatar upload. Avatar upload validation not executed: required QA files avatar-ok.png, avatar-large.jpg, avatar-bad.txt were absent under qa/ (issue #66), so drag-drop, size rejection, type rejection, Gravatar fallback, storage path, and remove-avatar checks are blocked.

[[2026-05-22]] Fri 04:06
Issue #66 fixed: Stage 10 users/pages/assets normalized; qa_admin Profile Fields settings and pf_test My Account Account Details browser spot-checked.
