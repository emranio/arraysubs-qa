---
id: 66
title: 'stage-10: Profile Builder prerequisites and QA assets missing'
status: closed
priority: high
created: 2026-05-20T15:31:19.843048737+02:00
updated: 2026-05-22T04:06:12.55783657+02:00
started: 2026-05-22T04:01:19.060452367+02:00
completed: 2026-05-22T04:06:12.557835328+02:00
tags:
    - qa
    - stage-10
    - profile-builder
    - fixture
claimed_by: mold-glade
claimed_at: 2026-05-22T04:06:12.55783646+02:00
class: standard
---

Stage 10 README expected pf_test/pf_test2/qa_admin users, Help Center and Account Shortcodes Sandbox pages, and QA asset files avatar-ok.png, avatar-large.jpg, avatar-bad.txt, id-doc.pdf. At stage start, users/pages did not exist and asset search under qa/ returned no files. QA seeded users/pages to continue shortcode checks, but avatar upload and file upload validation cannot be executed without required local files.

[[2026-05-22]] Fri 04:03
Plan: verify Stage 10 README prerequisites against current site, normalize fixture users and page content, confirm QA asset files exist with expected types/sizes, keep already-seeded shortcode sandbox content intact, then browser-check Profile Builder admin page and customer My Account access. No plugin code change expected unless fixture verification exposes product bug.

[[2026-05-22]] Fri 04:06
Fixed: normalized Stage 10 fixtures. Users: pf_test/pf_test2 customers and qa_admin admin with known QA passwords; pf_test first/last/display set per plan. Pages: Help Center published with block content over 100 chars; Account Shortcodes Sandbox published and preserved with existing shortcode test body. Assets present in qa/assets: avatar-ok.png valid PNG <2MB, avatar-large.jpg valid JPEG >2MB, avatar-bad.txt text, id-doc.pdf valid PDF <5MB. Verified via WP-CLI and Alumnium: qa_admin opens Profile Fields settings; pf_test opens My Account Account Details with expected profile identity.
