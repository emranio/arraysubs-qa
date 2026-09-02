---
id: 20
title: Refund product chart labels collapse to an indistinguishable shared prefix
status: closed
priority: medium
created: 2026-09-02T18:53:56.926573+06:00
updated: 2026-09-02T19:00:20.897089+06:00
started: 2026-09-02T18:54:18.291004+06:00
completed: 2026-09-02T19:00:20.897089+06:00
tags:
    - refund-analytics
    - ui
    - charts
    - qa
claimed_by: rhubarby-ballader
claimed_at: 2026-09-02T19:00:20.897089+06:00
class: standard
---

Active QA task ID / scheduled day / plan: N/A — no active formal QA plan.

Affected subscription IDs, order IDs, and WordPress users: N/A — visual presentation defect only.

URL and context: http://localhost:10013/wp-admin/admin.php?page=wc-admin&path=%2Fanalytics%2Farraysubs-refunds, local admin session.

Reproduction: Seed the Refund Analytics fixtures, open the fixture date range, and inspect Top Refunded Products.

Expected: Each long product label remains distinguishable while fitting the chart axis.

Actual: Labels with the same long prefix were truncated at the end and rendered identically.

Proof: /private/tmp/refund-qa-browser-filters/screenshots/refunds-fixture-range-full.png shows the duplicate-looking labels; chart tooltips still contained the full distinct names.

Scope and counterexample: Affects labels longer than 24 characters that share a prefix. Short labels remain distinct, and full tooltip values are correct.

Resolution: Preserve both the beginning and end using a middle ellipsis, rebuild the production bundle, and verify in-browser.
