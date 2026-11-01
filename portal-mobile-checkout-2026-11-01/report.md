# Portal checkout mobile results

Date: 2026-11-01 (Asia/Dhaka).

Target: optimized local production build at http://localhost:3001, with real Freemius frames. Scope and session schedule: [plan.md](plan.md).

Status: complete. One low-priority issue found, fixed locally, and verified; no remaining failures in the covered flows.

Testing uses agent-browser mobile device emulation in Chromium. The iOS simulator is unavailable because `xcrun simctl` failed. Physical iPhone/Android hardware, Safari/WebKit, native keyboards, and completed payments are outside the verified scope.

## Device matrix

| Emulated profile | CSS viewport | Checkout | Result |
| --- | --- | --- | --- |
| iPhone 16 | 393 x 852 | Personal annual | Fits; no horizontal overflow; scroll unlocked |
| iPhone 16 Pro | 402 x 874 | Agency lifetime, custom coupon | Fits; provider confirms 80% coupon and $179.80 before any applicable tax |
| Pixel 9 | 412 x 923 | Professional annual | Fits; no horizontal overflow; scroll unlocked |
| Galaxy S25 | 360 x 800 | Personal free trial | Fits; 10-day trial and terms visible |

Every profile had document scrollWidth equal to viewport width, no visible checkout modal, and body overflow visible. JSON measurements are in evidence/. Screenshots are in screenshots/.

The device presets set viewport/user-agent, but reported maxTouchPoints=0 and a fine pointer. These are responsive-layout and browser-interaction checks, not certified touch gestures or real Safari.

## Interactions

- iPhone 16 Pro: Card -> PayPal removes card fields, changes the action to Continue to PayPal, and shrinks the checkout frame.
- Galaxy S25: submitting the empty trial form shows Required at the email field, focuses it, and keeps the trial form open. No trial was created.
- Country search and selection work: searching United States returns the expected options, selecting it changes the postal field label to Zip code, and the provider updates its tax line.
- Empty card and PayPal forms stop at required email validation; no purchase or trial was completed. Test email/card fields stayed empty.
- Card/PayPal switching works in iPhone and compact Android contexts, with the correct action and conditional fields. A round trip on iPhone retains synthetic first/last names and selected country.
- Rotation 402x874 -> 874x402 -> 402x874 keeps the same iframe and preserves Mobile/QA values, selected country, and payment method. Ordinary scrolling reaches the action in landscape.
- Compact widths 320x568 and 375x667 fit without horizontal overflow. At 320px the payment action is 226x48 CSS pixels, and the fields/action remain reachable through ordinary page scrolling.
- Final browser error logs are empty (evidence/iphone-final-errors.txt and evidence/android-final-errors.txt).
- Full-page screenshots immediately after resizing can contain unpainted offscreen iframe regions. Viewport screenshots taken after ordinary scrolling prove the payment actions remain rendered and reachable; see compact-320-bottom-viewport.png and iphone-landscape-bottom-viewport.png.

## Findings

One low-priority finding: [QA #154](../issues/tasks/154-accessibility-launcher-overlaps-checkout-refund.md), now closed after local fix and browser retest.

At 320px, the fixed accessibility launcher obscured the right end of Refund Policy. Before the fix, the link occupied y532..548 and the launcher occupied y502..548; clicking the right edge hit the launcher.

Added mobile-only footer clearance in user-portal/components/checkout/CheckoutShell.tsx. After rebuilding/restarting the actual local production app, the link occupies y472..488, leaving a 14px gap above the launcher. The right edge now hits the policy link. 375px and landscape retests also pass; desktop footer spacing stays 20px.

Evidence:

- [Before, 320px](screenshots/compact-320-bottom-viewport.png)
- [After, 320px](screenshots/compact-320-footer-fixed.png)
- [After, 375px](screenshots/compact-375-footer-fixed.png)
- [Landscape](screenshots/landscape-footer-fixed.png)
- [320px hit test](evidence/footer-320-fixed.json), [375px hit test](evidence/footer-375-fixed.json), [landscape hit test](evidence/footer-landscape.json)

## Verification limits

This verifies responsive layouts and interactions in Chromium with mobile viewport/user-agent profiles. No physical devices or Safari/WebKit runtime were available, and the profiles expose no native touch points. Native touch gestures, mobile keyboard behavior, and completed Card/PayPal purchases remain unverified. The initial matrix was run locally. The same code was subsequently deployed as described below; existing Edge issue #149 remains open.

The optimized production build passes. It retains the pre-existing Turbopack file-tracing warning through next.config.ts -> lib/licensing/updates.ts -> app/api/plugins/update/route.ts, unrelated to this footer change.


## Production follow-up

The public checkout was initially confirmed to be the old modal: role=dialog, aria-modal=true, position=fixed and body overflow hidden. After the user requested pushing to main, portal commit d3b019acb1ec19a06a38e3c86611ab5600bfa5e7 was pushed and its automatic deployment succeeded, including server activation, Cloudflare purge, and public health checks.

Deployment: https://github.com/emranio/arrayhash-user-portal/actions/runs/41300286942 .

Public browser verification:

- https://user-portal.arrayhash.com/checkout/personal/ on desktop: real Freemius form loaded inside Payment details, iframe position static, no modal ancestor, body overflow visible.
- https://user-portal.arrayhash.com/checkout/agency/?billing_cycle=lifetime&coupon=teclogic80 at 393x852 with the iPhone user-agent profile: real inline form loaded, custom coupon retained, scrollWidth=393 and no scroll lock/modal ancestor.
- Evidence: screenshots/live-inline-desktop.png, screenshots/live-inline-mobile.png, evidence/live-desktop.json, evidence/live-mobile.json.
- Browser error logs were empty for these public loads. No payment was submitted.
