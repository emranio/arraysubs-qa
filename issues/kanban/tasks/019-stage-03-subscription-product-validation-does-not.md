---
id: 19
title: 'stage-03: Subscription product validation does not block invalid saves'
status: closed
priority: high
created: 2026-05-20T08:34:14.96253885+02:00
updated: 2026-05-22T01:08:12.671717367+02:00
started: 2026-05-22T00:57:02.068860032+02:00
completed: 2026-05-22T01:08:12.671716165+02:00
tags:
    - qa
    - stage-03
    - validation
    - products
claimed_by: mold-glade
claimed_at: 2026-05-22T01:08:12.671717267+02:00
class: standard
---

Observed via WP/Woo save path on 2026-05-20 using Validation Sandbox #276. Saved subscription product with regular price 0 and billing interval 13; post remained draft with invalid meta persisted. Code in Validation::addValidationError only registers admin_notices and does not abort product save. Expected Stage 03 task 09: save blocked for exact errors: invalid price, interval out of range, missing renewal price, renewal price after < 1.

[[2026-05-22]] Fri 01:00
Verified Stage 03 Task 09 and code. Root cause: validation reads old saved meta and only registers admin_notices, so Woo still saves product props and ArraySubs meta; posted invalid interval/renewal values persist. Existing stale invalid meta can also cause wrong extra errors. Plan: move admin save validation into the product save hook that sees posted values; on validation errors, add exact Woo admin errors, preserve prior post status, skip ArraySubs meta writes, and restore Woo price props from saved meta before Woo saves. Disable old before_product_object_save notice-only validation to avoid duplicate/stale errors. Verify with browser/admin or WP save path that invalid posted values do not persist, then verify clean values save.

[[2026-05-22]] Fri 01:08
Fix applied. Admin product validation now uses posted values before Woo meta save, adds exact Woo admin error messages, preserves prior post status on invalid save, restores saved price props, and skips ArraySubs subscription meta writes when invalid. Disabled old before-save notice-only validation to avoid stale duplicate errors. Browser/admin QA on Validation Sandbox #276: interval 13 shows exact 'Billing interval must be between 1 and 12.' and meta remains interval 1; price 0 shows exact 'Subscription products must have a valid regular price greater than zero.' and price remains 19.99; enabled renewal with empty renewal price shows exact renewal-price error and renewal meta does not persist; renewal price after 0 shows exact period error and renewal meta does not persist; valid 19.99 / interval 1 / renewal 29.99 after 3 saves cleanly and persists. Sandbox moved back to trash. debug.log remains 0 bytes.
