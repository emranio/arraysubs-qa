---
id: 135
title: 'stage-6: Cross-gateway purchase integrity and cleanup'
status: done
priority: critical
created: 2026-08-15T08:57:02.987497752+02:00
updated: 2026-08-15T10:30:34.891812059+02:00
started: 2026-08-15T10:20:34.919957337+02:00
completed: 2026-08-15T10:20:34.919957337+02:00
tags:
    - lifecycle
    - payments
    - integrity
    - cleanup
depends_on:
    - 133
    - 134
    - 136
    - 137
class: standard
---

Cross-check Stripe and Paddle checkout results in admin, customer portal, WooCommerce orders, Action Scheduler, webhook log, Mailpit, and secret-safe database invariants. Confirm normal products create no subscriptions, mixed carts create exactly one, remove disposable fixtures safely, and publish the final QA verdict.

[[2026-08-15]] Sat 10:20
Final 2026-08-15 verdict: the real-browser Stripe/Paddle purchase matrix passed for subscription-only, ordinary-only, mixed-cart, Stripe SCA, and incomplete/abandoned paths. Release gate remains not clean because formal high issues 1, 2, and 3 remain open; none caused payment loss, duplicate charging, or subscription cardinality failure. Temporary settings were restored to exact baseline hashes. Paddle sandbox subscriptions were remotely canceled before exact local teardown. Nine users, eight orders, six subscriptions, 56 subscription notes, 29 order notes, and 27 exact actions/logs were removed with zero target remnants. Subscription/order population returned exactly to baseline. Preexisting Stripe billing metadata is unchanged; 31 regenerated future actions match the backup exactly on hook, group, args, and UTC schedule with zero missing/due/duplicate rows. Full report: qa/artifacts/payment-migration-regression-20260815/final/report.md

[[2026-08-15]] Sat 10:25
Post-teardown final browser smoke also passed: exact subscription counts rendered, final gateway webhook chains remained visible, page errors were empty, and the cleared console contained only JQMIGRATE. The isolated backup-comparison database was then dropped and verified absent.

[[2026-08-15]] Sat 10:30
Final old-customer proof now covers both automatic gateways: active Paddle subscription 7809 is byte-identical on all business/gateway data, and its two regenerated future actions are one-for-one semantic matches with no missing/due/duplicate row. Its isolated comparison DB was dropped and verified absent.
