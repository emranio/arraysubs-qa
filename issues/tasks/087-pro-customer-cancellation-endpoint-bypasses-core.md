---
id: 87
title: Pro customer cancellation endpoint bypasses core cancellation policy
status: closed
priority: high
created: 2026-10-23T02:22:37.984377+06:00
updated: 2026-10-23T02:43:02.036882+06:00
started: 2026-10-23T02:23:18.647761+06:00
tags:
    - bug
    - customer-portal
    - cancellation
    - security
class: standard
---

Source-confirmed during active QA #86 (2026-10-23), plan qa/storefront-account-boundaries-2026-10-23/plan.md; scheduled day 2026-10-23 targeted manual run.
Affected subscriptions/orders/users: N/A yet; use isolated disposable manual-payment fixtures for endpoint policy tests, never cancel an existing subscription.
Exact route: POST /wp-json/arraysubs/v1/subscriptions/{id}/cancel-with-reason, authenticated owner context. Browser site http://localhost:10013.
Reproduction: configure customer cancellation unavailable or end-of-period only; an authenticated owner can call this duplicate Pro route with cancel_type=immediate. Its permission callback only checks login/ownership; cancelWithReason directly invokes low-level cancellation helpers without canonical customer policy. Some errors return WP_Error despite :WP_REST_Response declaration, causing TypeError rather than a controlled response.
Expected: all customer cancellation follows the canonical core permission/timing/lock policy, required reasons return a clean 400 and conflicts return 409. Actual source proof: Pro CancellationController.php:148,598-689; core CustomerController.php:236 checks arraysubs_can_customer_cancel_by_subs_id while the duplicate does not. No frontend JS caller exists for the duplicate route. Low-level helpers do not restore the missing policy check.
Scope: core portal already calls the canonical /my-subscriptions/{id}/cancel route. Investigate retiring unused duplicate route and centralizing reason validation in canonical core handler. Preserve existing reason/retention UI and admin behavior. Required-reason server validation is also absent in core and must be addressed with shared existing helpers. Admin saveFormConfig has the same return type mismatch if touched.
Plan before implementation: inspect current shared cancellation policy/reason helpers, remove or delegate duplicate endpoint without a parallel path, preserve capability/ownership and response contracts, add targeted real WordPress REST checks using disposable fixtures/read-only denial checks, and verify browser portal still uses core cancellation without performing actual customer lifecycle actions.
Browser proof pending; this issue records code evidence rather than claiming a performed payment/lifecycle action.

[[2026-10-23]] Fri 02:23
Fix plan (cowman-soho): inspect the shared required-reason policy and Free fallback before editing. Retire the unused Pro cancel-with-reason route and handler so all customer cancellation uses the existing owner-checked core endpoint and merchant timing. Add trimmed required-reason validation to core only when the portal actually offers configured cancellation reasons, preserving optional reasons and Free fallback behavior. Correct saveFormConfig's return contract to include WP_Error. Prepare a real WP REST regression script with disposable manual-payment fixtures and request-local policy filters only; root will run it after merging and perform final browser verification. No builds, deployment, changelog edits, global setting changes or existing lifecycle/payment mutations in this subtask.

[[2026-10-23]] Fri 02:24
Inspection confirmed the portal already uses arraysubs_require_cancel_reason() and arraysubs_get_cancellation_reasons() in Core, with built-in default reasons available in Free. Therefore the core server will enforce exactly the shared require_reason boolean (no Pro/module gate and no new policy helper); optional false remains permitted. No frontend caller or documentation uses the retired Pro endpoint. Regression coverage will use real rest_do_request calls, request-local option filters, disposable manually paid subscription/product/customer fixtures, suppressed fixture email, and finally cleanup; saved options and plugin activation remain unchanged.

[[2026-10-23]] Fri 02:27
Implementation handoff (cowman-soho): Core commit 72a6758 in /tmp/arraysubs-customer-cancel-core adds required-reason validation using the existing shared policy; Pro commit 4a02a8a in /tmp/arraysubs-customer-cancel-pro removes the unused duplicate registration/handler and fixes saveFormConfig to return WP_REST_Response|WP_Error. Both worktrees contain only these source commits; no changelog/build/deployment changes. git diff --check passed; modified files are 797 and 654 lines. The non-mutating real WP REST baseline failed as expected because the duplicate Pro route is registered; evidence: qa/storefront-account-boundaries-2026-10-23/cancellation-rest-before.json (zero fixture IDs; saved settings and active plugins unchanged). Prepared real REST regression script: qa/storefront-account-boundaries-2026-10-23/cancellation-rest-regression.php. Root must run the full script after merging source (without route-only argument); it creates and cleans only disposable draft product/customer/manual-payment subscription fixtures, suppresses fixture email, uses request-local settings, and checks no saved settings/plugin changes. Covers retired route 404, missing/whitespace reason 400 without state change, disabled policy 403, ownership/anonymous denial, merchant end-period timing despite client immediate override, optional reason/immediate path, invalid admin form 400. Full post-fix execution and browser portal confirmation remain pending. Keep issue in-progress claimed cowman-soho; root owns merge/build/browser and final closure.

[[2026-10-23]] Fri 02:43
Verified after integration: Core 72a6758 and Pro 4a02a8a are merged. Real WordPress REST regression passed all 22 checks in qa/storefront-account-boundaries-2026-10-23/cancellation-rest-after.json using qa/storefront-account-boundaries-2026-10-23/cancellation-rest-regression.php. The obsolete duplicate route returns 404; canonical owner, anonymous, required/whitespace reason, optional reason, disabled policy, merchant timing, and invalid admin form behavior passed. Only disposable manual-payment fixtures were mutated and cleaned; saved settings and active plugins remained unchanged. Root completed browser review: customer 224 subscription 8326 correctly shows the contact-admin advisory and no Cancel action under the saved allow_cancellation policy. No existing subscription lifecycle or payment was mutated and no saved settings were changed. Closed on root verification; cowman-soho claim released.
