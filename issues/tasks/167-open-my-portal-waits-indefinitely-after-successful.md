---
id: 167
title: Open my portal waits indefinitely after successful Freemius trial
status: closed
priority: critical
created: 2026-11-16T19:04:12.810536+06:00
updated: 2026-11-16T19:55:31.761071+06:00
started: 2026-11-16T19:55:31.747424+06:00
completed: 2026-11-16T19:55:31.747424+06:00
tags:
    - portal
    - freemius
    - checkout
class: standard
---

Active QA task, scheduled day and plan path: N/A. Focused regression on November 16, 2026 (Asia/Dhaka) after the checkout submission fix (#166).

Subscription/order ID and WordPress user ID: N/A, no payment or WP account. Freemius reproduction trial license ID 2094919, provider user ID 10635860, synthetic buyer arraysubs-portal-handoff-20261116@example.com. Customer was a guest in Chromium Incognito. Reported separately from an account already signed in via Microsoft Edge.

Reproduce at https://user-portal.arrayhash.com/checkout/personal/?trial=free: start the trial, reach https://user-portal.arrayhash.com/thank-you/, click Open my portal. Expected: open Products & Licenses from the account in one click. Actual: portal displays “Your portal access is still being prepared. Please try again in a moment.” Trial submission returned HTTP 200, Freemius issued an active Personal license, but its carts API returned no cart for the no-card trial. Screenshot: ../evidence/portal-checkout-handoff/before-pending.png.

Fix plan: reuse a buyer’s existing portal session without waiting for a cart. For brand-new no-card trial buyers, bind the new Freemius identity to the checkout browser before submission and verify its active license before granting new access. Verify both paths and the license display.

Existing-session patch ee4524f deployed. An isolated Microsoft Edge session signed in to a local test account linked to the actual reported Freemius identity, replayed the reported pending receipt, and opened /dashboard/ on the first click, retaining the login. Products & Licenses showed the Personal Trial through November 26. The live provider license is 2094914. Evidence: after-existing-edge-license.png.

New trial path creates a new first-party portal account from the buyer details and keeps that session through Freemius; an existing portal or provider identity cannot be claimed this way. A fresh Chromium Incognito test submitted a real no-card Freemius trial and opened the dashboard on the first click. Products & Licenses displayed the Personal Trial through November 26 (after-new-incognito-license.png). At a 390 px viewport an intentionally delayed sign-in response displayed “Please wait while we open your portal”; the button, status block, and support text had identical boxes with and without the pending message.

[[2026-11-16]] Mon 19:37
User clarified exact direct-form flow: preserve signed-in and external prefills; own authenticated account opens directly; returning logged-out email gets a bold sign-in notice; new identity gets one-time login after signed provider proof. The rejected account preparation screen was removed in b5832ed (deployed). 8fb7007 adds HMAC validation of Freemius redirect_url, existing-account notice, and stable click loading. 33 server and 16 client tests pass. Deployment pending; provider signed-success setting and new-guest browser verification still being completed.

[[2026-11-16]] Mon 19:41
8fb7007 deployment 43877400660 succeeded; live status exposes new routing fields and reports ready for the authenticated buyer. Production browser opened dashboard from the receipt and Products & Licenses displayed active Personal Trial (after-live-signedin-license.png). New guest auto-login is NOT yet confirmed: Freemius checkout redirection is disabled, so callback lacks signed redirect_url. Enabling Plans > Customization > Redirect Checkout to a custom URL to https://user-portal.arrayhash.com/thank-you/ is required; product/store API credentials cannot change the developer-dashboard setting (401). User action requested. Do not close this issue until real fresh direct trial yields signed proof and portal login.

[[2026-11-16]] Mon 19:50
Enabled Freemius checkout success redirection in user-authenticated Edge: exact destination https://user-portal.arrayhash.com/thank-you/. Reloaded settings and verified switch enabled and URL persisted. Now testing fresh direct guest checkout against live provider.

[[2026-11-16]] Mon 19:53
Live new guest trial PASSED after redirect setting: actual Freemius callback keys trial,user,redirect_url with signed redirect; complete status ready and existingAccount=false; first Open my portal click opened authenticated /dashboard/; Products & Licenses shows Personal Trial ending Nov 26, 2026. Evidence after-direct-new-ready.png, after-direct-new-license.png, after-direct-new-result.json. Also tested actual signed-in Edge account emran@arrayhash.com: Open my portal -> dashboard preserving identity, then actual Personal Trial license 2094914 visible. A repeated trial for the synthetic buyer was correctly rejected by Freemius policy; existing portal account without prior trial is being tested separately for the bold sign-in notice.

[[2026-11-16]] Mon 19:55
FINAL PASS: existing-email signed-out live E2E created a real portal fixture account via signup, signed out, completed a native Freemius no-card Personal trial with that same email. Receipt showed You already have an account at 24px/700 and existing email at 18px/700. Open my portal led to /signin/ with email/password controls and no authenticated navigation. Evidence after-live-existing-email-receipt.png, after-live-existing-email-signin.png, after-live-existing-email-observations.json. All three requested paths now pass against production: real existing Edge session, new direct-checkout account, returning signed-out email. No payment made. No account preparation gate remains. Customer installation guide updated; npm run check passes 308 pages and 604 ranking assertions.
