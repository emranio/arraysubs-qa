---
id: 13
title: 'migration: Retention Flow + Profile Builder moved free→pro'
status: closed
priority: medium
created: 2026-08-18T09:21:19.525992234+02:00
updated: 2026-08-18T09:21:19.525992234+02:00
class: standard
---

Moved CancellationFlow, RetentionAnalytics, ProfileFields, MyAccountEditor from arraysubs to arraysubspro (namespaces, helpers, JS bundles, menu gating). Core cancel engine, renewal-invoice gate, and gateway-cancel bridge stay in free. Browser-verified on staging: pro active (retention flow page, offers modal, profile fields, my-account editor, WC Analytics retention), pro deactivated (no fatals, menus hidden, cancel flow intact), pro reactivated (all restored). debug.log clean.
