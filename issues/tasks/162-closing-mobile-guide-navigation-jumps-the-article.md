---
id: 162
title: Closing mobile guide navigation jumps the article upward
status: open
priority: low
created: 2026-11-11T01:31:14.063648+06:00
updated: 2026-11-11T01:31:14.063648+06:00
tags:
    - bug
    - user-guide
class: standard
---

Active QA task ID, scheduled day, and plan path are N/A. This was found during the focused local guide header check on 2026-11-11. Subscription and order IDs are N/A. WordPress and customer IDs, logins, and roles are N/A.

### Reproduction

1. Open http://127.0.0.1:4329/docs/managing-subscriptions/edit-subscription-contact-and-addresses/ as a guest in Chromium 146 at 390 × 844.
2. Scroll the article to scrollY 195. Click Open guide navigation and confirm focus moves to Close guide navigation. The article is still at scrollY 195 while the drawer is open.
3. Press Escape and inspect the article's scroll position and focused element.

The drawer should close, return focus to the header toggle, and keep the article at scrollY 195. The drawer closed and restored focus, but the article moved upward. Chromium reported scrollY 59 in the first check and 40 during a repeat with backdrop-filter set to none. The repeat used a direct pointer click on the visible toggle to rule out the test tool scrolling the element into view. The drawer covered the viewport and its backdrop intercepted pointer input correctly while open. The jump reproduces independently of the header blur.
