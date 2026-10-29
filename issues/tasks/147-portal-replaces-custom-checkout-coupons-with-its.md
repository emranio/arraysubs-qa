---
id: 147
title: Portal replaces custom checkout coupons with its default promotion
status: closed
priority: high
created: 2026-10-30T04:03:15.388411+06:00
updated: 2026-10-30T04:03:49.05613+06:00
started: 2026-10-30T04:03:49.062089+06:00
completed: 2026-10-30T04:03:49.062089+06:00
tags:
    - portal
    - coupons
    - customer-report
class: standard
---

## Scope and identities
- Active QA task ID, scheduled day, and plan path: N/A. Direct customer-reported fix requested on 2026-10-30 (Asia/Dhaka); no new QA execution cycle or schedule.
- Subscription IDs and order IDs: N/A; checkout stopped before purchase.
- WordPress customer/user IDs and roles: N/A; this is the external licensing portal. Reported customer email: info@tec-logic.es. No customer account was used during verification.
- Routes: https://user-portal.arrayhash.com/checkout/agency/?billing_cycle=lifetime&coupon=teclogic80 ; local equivalent http://localhost:3000/checkout/agency/?billing_cycle=lifetime&coupon=teclogic80 .

## Reproduction and evidence
1. Open a checkout URL carrying a custom coupon.
2. Before the fix, lib/checkout/query.ts discarded the coupon argument and always selected the catalog promotion (Agency lifetime: special60).
3. Expected: forward the custom coupon to Freemius for validation; do not advertise the unrelated default discount.
4. Actual before fix: the custom coupon was silently replaced; the summary claimed the default discount.

## Fix plan and implementation
- Accept a trimmed custom coupon up to 128 characters without control characters; use the default promotion when input is absent or invalid.
- Keep Freemius authoritative for discount value, eligibility, expiry and redemption limits.
- Render the coupon and catalog price before coupon/tax for custom offers, rather than estimating an unknown discount.
- Include the coupon in the checkout component key so changing offers remounts the checkout cleanly.

## Verification and limits
- Six targeted parsing/rendering tests and 15 existing checkout client tests pass. TypeScript typecheck passes.
- A real local Personal checkout opened the Freemius iframe with teclogic80 and showed $53.80 USD; see ../../artifacts/portal-recovery-2026-10-30/portal-personal-custom-coupon.png.
- Live Freemius API initially reported the coupon restricted to plan 54910 (Personal). The user changed it to all plans. Recheck confirmed plans=null, billing_cycles=null, is_active=true, discount=80.
- Real Edge hosted Agency checkout shows $899 subtotal, $719.20 discount and $179.80 USD total; see ../../artifacts/portal-recovery-2026-10-30/agency-80-percent-edge.png.
- The local Agency summary and iframe src in Edge preserve coupon=teclogic80 and billing_cycle=lifetime. Its iframe stayed on the provider loading spinner during this run; the direct hosted Edge checkout was verified instead. No mocked provider response, purchase, account entry, or outbound email was used.
- Portal fix committed and pushed to main as 9318a24432a92cdb1167e26e38f48ad7c8f50cb7. Production deployment https://github.com/emranio/arrayhash-user-portal/actions/runs/40963252418 passed (server deployment, Cloudflare purge and public portal health checks). The deployed Agency checkout now renders coupon teclogic80 and the custom-coupon price explanation. The live Freemius coupon scope change was made by the user.
