---
id: 161
title: Guide header blur disappears after CSS minification
status: closed
priority: low
created: 2026-11-11T01:31:14.003665+06:00
updated: 2026-11-11T01:31:14.003665+06:00
tags:
    - bug
    - user-guide
class: standard
---

Active QA task ID, scheduled day, and plan path are N/A. This was a focused local check on 2026-11-11. Subscription and order IDs are N/A. WordPress and customer IDs, logins, and roles are N/A.

### Reproduction

1. Build and preview the guide at user-guide revision 0d6f3fa. Open http://127.0.0.1:4329/docs/ as a guest in Chromium 146 at 1440 × 900.
2. Scroll 285 px so the home page title passes under the sticky header. Compare with the marketing home page at http://127.0.0.1:3000/ after dismissing its privacy prompt.
3. Inspect the computed backdrop-filter on the guide's .site-header__surface.

Both headers should soften the content behind a full-width, 80%-opaque white bar with a 12 px blur. The guide showed legible, sharp title text under the bar and computed backdrop-filter as none. The emitted header rule retained only -webkit-backdrop-filter:blur(12px), which Chromium 146 ignores. The marketing header computed blur(12px) in the same browser.

### Resolution and verification

The WebKit fallback now lives in a separate feature query in user-guide/src/styles/layout.css. The built CSS contains the standard declaration and the conditional fallback. Chromium computed blur(12px) at scrollY 285 on the guide home page at 1440 × 900 and at scrollY 195 on http://127.0.0.1:4329/docs/managing-subscriptions/edit-subscription-contact-and-addresses/ at 390 × 844. Both bars stayed at top 0 and spanned the viewport. Screenshots showed the title visibly softening behind the bar. The article search returned the expected guide for “edit subscription billing address.” The search and assistant dialogs opened and returned focus on dismissal. npm run check passed with 71 unit tests, 308 built pages, and 604 search ranking checks. The fallback was verified in the emitted CSS.
