# Portal checkout mobile verification

Requested scope: verify the newly simplified inline checkout on mobile.

Target: http://localhost:3001 (the actual optimized Next.js production build of the local user-portal checkout). The public portal has not been deployed with this change. Freemius payment forms and services remain real; no provider responses will be mocked.

Scheduled day: 2026-11-01, Asia/Dhaka. Execute sequentially in this session, approximately 17:00-17:30:

1. 17:00-17:10: portrait device matrix - iPhone 16, iPhone 16 Pro, Pixel 9, Galaxy S25.
2. 17:10-17:20: payment controls, empty-field validation, country selector, custom lifetime coupon, and free-trial form.
3. 17:20-17:25: compact 320px/375px widths, landscape/portrait transitions, focus, scrolling, and field preservation.
4. 17:25-17:30: consolidate screenshots and browser errors; complete the progress board and report.

Progress board: progress/kanban/. Results: report.md. Evidence: screenshots/ and evidence/.

Pass criteria:

- Checkout renders inline with no modal or scroll lock.
- No horizontal page overflow, cropped fields, or inaccessible action controls.
- Main payment/trial action and policy links remain reachable through ordinary scrolling.
- Card/PayPal switching and country selector work; frame adapts to content changes.
- Trial terms and custom coupon remain correct on mobile.
- Rotation preserves current form state and the checkout frame.
- No unhandled browser errors during the covered flows.

Boundaries: no real purchase or trial will be submitted, no real identity/payment details will be entered, and no account or email workflow will be triggered. Empty required-field validation may be exercised. Subscriptions, orders, WordPress user IDs, and customer accounts are N/A.

Method: Vercel agent-browser with isolated sessions and mobile/touch device emulation. A separate iOS simulator availability check is allowed; if unavailable, explicitly report that these results do not certify Safari, native mobile keyboards, or physical devices. Xcode/iOS simulator discovery failed (`xcrun simctl failed. Xcode may not be installed.`), so the planned matrix uses Chromium mobile emulation.

Existing issues reviewed: qa/issues #149 (Edge iframe loading), #148 (Freemius recovery email sender), #147 (custom coupon forwarding). Preserve their statuses; this local Chromium mobile check does not close the Edge issue or verify production deployment.

Execution completed in the same session. All three progress tasks are done. Local issue #154 was added during task #3, fixed with mobile footer clearance, and closed after exact-route and adjacent-width browser retests. No future automation was created.

User-requested production follow-up: pushed portal commit d3b019a to main; automatic deployment run 41300286942 succeeded. Public desktop and 393px mobile-profile checkout now render inline; evidence is recorded in report.md.
