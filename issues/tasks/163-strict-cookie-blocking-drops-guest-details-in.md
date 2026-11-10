---
id: 163
title: Strict cookie blocking drops guest details in Freemius checkout
status: in-progress
priority: medium
created: 2026-11-11T01:54:41.375729+06:00
updated: 2026-11-11T02:02:52.959575+06:00
tags:
    - bug
    - checkout
class: standard
---

Active QA task ID, scheduled day, and plan path are N/A. Focused checkout verification on 2026-11-11. Subscription and order IDs are N/A. WordPress and customer IDs and logins are N/A. The browser is signed out and uses synthetic fixture details.

### Reproduction

1. Open https://subscriptionplugin.pro/pricing/ in a fresh Chromium 146 Incognito session with --test-third-party-cookie-phaseout and a 1280 × 800 viewport.
2. Choose Personal Lifetime. Enter the fixture email checkout-qa+strict-20261111@example.com and names Lee Jordan in Your details, then select Continue.
3. Wait for the inline Freemius form at https://user-portal.arrayhash.com/checkout/personal/ and inspect its email and name inputs.

Expected the three submitted details to be present. All three inputs were empty. The portal's POST /api/checkout/prefill/redeem/ returned 200 and the outgoing Freemius POST contained the exact fixture values. Freemius then returned a 302 to a clean GET and supplied an unpartitioned __fs_session cookie with SameSite=None and Secure. The GET loaded an editable checkout with blank fields. No purchase was submitted.

Standard fresh Incognito runs of Personal Lifetime, Personal Yearly at 390 × 844, and Agency Lifetime at 1440 × 900 all prefilled. In readonly mode, both Freemius POST and documented GET prefill returned the cookie-dependent redirect. The issue is isolated to the provider handoff when cross-site cookie storage is blocked.


### Fix and verification

The provider served the three fields directly from the secure POST when the readonly flag was omitted. The portal now uses editable details for pricing-form prefill and keeps complete portal-profile details in their existing mode. Customer details stay out of request URLs. The iframe, offer parameters, checkout state, and signed-in fallback follow the same path.

The updated website and portal worked end to end at http://localhost:3000/pricing/ and http://localhost:3001/checkout/personal/ in fresh Chromium Incognito sessions with strict cookie blocking. Personal Lifetime at 1440 × 900 prefilled the fixture email, Léa and O'Connor. Each of the three actual Freemius fields accepted and retained a replacement value. Personal Yearly at 390 × 844 prefilled the submitted email, Sam and Ng. Freemius showed its separate email confirmation for editable checkout. The provider also rendered editable prefill from an isolated browser POST on the public portal origin. No payment was submitted.

Both production builds passed. All 23 checkout link, portal prefill, embed and coupon tests passed. The desktop modal displayed the supplied celebration asset beside the form and held a still frame with reduced motion. On mobile, the name inputs shared a row below email. The local changes are ready for a production rollout and recheck.
