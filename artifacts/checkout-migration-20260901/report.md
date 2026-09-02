# Checkout migration QA report

Date: 2026-09-01

Scope: Move the ArraySubs checkout, thank-you experience, and browser-side Freemius integration from `web-content` to `user-portal`, while preserving old checkout URLs and affiliate attribution.

## Result

Pass. The production builds, type checks, schema migration, Freemius connectivity check, browser matrix, redirect matrix, attribution transfer, mobile review, and signed-webhook/referral race tests completed successfully.

No real purchase or free-trial submission was made during QA; doing so would create a live Freemius customer/order. Checkout rendering and amounts were verified inside the real Freemius iframe, while post-purchase processing was exercised against signed synthetic Freemius events and a local GoAffPro mock.

## Browser matrix

| Plan | Billing | Discount | Freemius total | Result |
| --- | --- | ---: | ---: | --- |
| Personal | Annual | $38.70 | $90.30 | Pass |
| Personal | Lifetime | $80.70 | $188.30 | Pass |
| Professional | Annual | $74.70 | $174.30 | Pass |
| Professional | Lifetime | $155.70 | $363.30 | Pass |
| Agency | Annual | $146.70 | $342.30 | Pass |
| Agency | Lifetime | $269.70 | $629.30 | Pass |

Additional browser checks passed:

- Personal 10-day trial; trial was rejected for plans that do not offer it.
- Fresh-visitor checkout suppresses the privacy-consent UI and opens Freemius immediately; consent remains available on non-checkout pages.
- Checkout cancel and reopen states.
- Human-readable and legacy numeric plan URLs.
- Invalid plan custom 404 in a production build, with no console errors.
- Thank-you direct-load fallback and one-time checkout handoff state.
- 390 x 844 mobile viewport.
- Pricing annual/lifetime toggle and exact-cent display.
- Valid affiliate/referral cookies transfer to the portal; malformed or overlong values do not.
- All legacy checkout URL families return one-hop permanent redirects and preserve query parameters.
- Checkout and thank-you pages emit `noindex`, `nofollow`, no-cache headers, and canonical URLs.

## Backend integration checks

The end-to-end harness used the real local Postgres schema, signed synthetic Freemius webhooks, and a local GoAffPro HTTP mock. It passed:

- Cross-origin referral request rejection.
- Invalid referral identity rejection.
- Claim-before-payment reconciliation.
- Payment-before-claim reconciliation.
- Concurrent duplicate webhook idempotency.
- Same-customer, different-plan claim disambiguation.
- Signed Freemius net amount as the commission authority.
- Exactly one GoAffPro commission write for each attributed payment in the exercised flows.

Database test rows were removed after the run.

## Build and release checks

- `user-portal`: production build and TypeScript validation passed.
- `web-content`: production build and TypeScript validation passed; no checkout application route remains.
- Portal schema migration completed and was idempotent.
- Freemius doctor authenticated the store/product credentials and read purchases, licenses, and payments.
- Deploy shell scripts passed syntax checks.
- Git whitespace checks passed in both repositories.
- Touched source files remain below the workspace's 3,000-line limit.

Deployment order is intentional: deploy `user-portal` first, verify its checkout and thank-you health checks, then deploy `web-content`. The marketing deployment now refuses to proceed unless all three portal checkout routes are reachable.

## Screenshots

- `screenshots/01-personal-consent-gate.png` (pre-fix regression baseline)
- `screenshots/02-personal-freemius-open.png`
- `screenshots/03-thank-you-received.png`
- `screenshots/04-personal-mobile.png`
- `screenshots/05-personal-mobile-viewport.png`
- `screenshots/06-checkout-auto-open-no-consent.png`
