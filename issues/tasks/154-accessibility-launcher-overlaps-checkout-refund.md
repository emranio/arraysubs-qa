---
id: 154
title: Accessibility launcher overlaps checkout Refund Policy on narrow phones
status: closed
priority: low
created: 2026-11-01T17:02:42.986585+06:00
updated: 2026-11-01T17:08:41.525585+06:00
started: 2026-11-01T17:02:43.043818+06:00
completed: 2026-11-01T17:04:02.044106+06:00
tags:
    - portal
    - mobile
    - checkout
class: standard
---

## Scope and identities
- Active QA task: portal-mobile-checkout-2026-11-01 progress task #3, scheduled 2026-11-01 (Asia/Dhaka), plan qa/portal-mobile-checkout-2026-11-01/plan.md.
- Affected subscription IDs and order IDs: N/A; no purchase was submitted.
- Affected WordPress customer/user IDs, login/email and roles: N/A. Anonymous external portal; email and card fields stayed empty.
- Route: http://localhost:3001/checkout/professional/ . Context: isolated agent-browser portal-mobile-android, Pixel 9 user agent with compact 320x568 viewport, local optimized build.

## Reproduction
1. Open the local checkout and let the real Freemius form load.
2. Set viewport to 320x568 and scroll to the bottom using ordinary page scrolling.
3. Observe the Refund Policy link under the fixed accessibility launcher.

## Expected and actual
- Expected: the complete policy link is visible and its target unobstructed.
- Actual: the 46x46 launcher at x254/y502 overlaps the right portion of the Refund Policy link, whose bounds are x196.53..270.48 and y532..548. A hit test at the link's right edge returns accessibility-widget-trigger instead of the policy link.
- Proof: qa/portal-mobile-checkout-2026-11-01/screenshots/compact-320-bottom-viewport.png and the recorded rectangle/hit-test observation above.

## Scope notes
- Payment fields and the 226x48 Review Order action remain reachable; no horizontal page overflow.
- Standard 393px/402px/412px profiles and landscape center the footer links farther from the launcher. This is a narrow-footer overlap, not a payment or Freemius pricing failure.
- Existing Edge iframe issue #149 remains outside this Chromium mobile test.

## Fix plan
Add bottom clearance beneath checkout policy links on widths below 640px so they sit above the fixed launcher when scrolled to the page end. Keep the desktop footer spacing. Rebuild and repeat the exact 320px browser check and adjacent 375px/landscape checks, including hit testing the policy link.

Implemented mobile-only checkout footer clearance in user-portal/components/checkout/CheckoutShell.tsx (80px bottom padding below 640px; desktop remains 20px). Optimized production build passes. Browser retest at 320x568 gives Refund Policy y472..488 and launcher y502..548, overlap=false and rightEdgeHitsPolicy=true. Adjacent 375x667 and landscape 874x402 also have overlap=false and rightEdgeHitsPolicy=true, with scrollWidth equal to viewport width. Proof: qa/portal-mobile-checkout-2026-11-01/screenshots/compact-320-footer-fixed.png, compact-375-footer-fixed.png, landscape-footer-fixed.png; evidence/footer-320-fixed.json, footer-375-fixed.json, footer-landscape.json. Verified locally only; not deployed.

Production follow-up: portal commit d3b019a pushed to main; automatic deployment run 41300286942 succeeded (server, Cloudflare purge, public health). Public mobile checkout verified inline at 393px with no page overflow; screenshots/live-inline-mobile.png in the same QA record. Earlier exact 320px clearance proof remains in evidence/footer-320-fixed.json.
