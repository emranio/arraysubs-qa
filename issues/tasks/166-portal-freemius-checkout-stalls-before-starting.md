---
id: 166
title: Portal Freemius checkout stalls before starting trials or payments
status: closed
priority: critical
created: 2026-11-16T18:40:05.350732+06:00
updated: 2026-11-16T19:38:33.236764+06:00
started: 2026-11-16T19:38:25.032386+06:00
completed: 2026-11-16T19:38:25.032386+06:00
tags:
    - portal
    - freemius
    - checkout
class: standard
---

Active QA task, scheduled day and plan path: N/A. Focused reproduction of the reported production checkout outage on 2026-11-16 (Asia/Dhaka).

Subscription and order IDs: N/A; nothing was submitted to Freemius. WordPress account/user ID and role: N/A; signed-out Chromium Incognito guest. Only the synthetic address arraysubs-checkout-qa-20261116@example.com was entered.

1. Open https://user-portal.arrayhash.com/checkout/personal/?trial=free in a fresh Chromium Incognito session.
2. Enter the first and last names, the synthetic email and matching confirmation, then activate Start Trial.
3. Expected: Freemius submits the trial and confirms the result or shows a validation error. Actual: the form stays disabled on Processing, please wait. The embedded page sends get_context to the portal, never receives a response, and sends no trial request.

Freemius uses the same submission path for paid plans. The manual inline integration sets mode=dashboard, which is for WordPress admin checkout and needs the installed site's context before either kind of submission. A hosted Professional annual checkout did load; one separate portal Professional annual entry also timed out before the form rendered, while a second attempt loaded. The earlier Edge iframe load concern (#149) remains separately tracked.

Proof: ../evidence/portal-freemius-submit/before-trial-spinner.png and ../evidence/portal-freemius-submit/before-trial-messages.json.

Fix plan: use storefront dialog mode inside the existing inline iframe, retain the provider result messages and verified portal handoff, and verify both trial and card submission with real Freemius sandbox checkout before rolling out.

The local production build switched the frame to Freemius dialog mode. In fresh Chromium Incognito sessions, Freemius Sandbox accepted the Personal no-card trial (POST /action/service/subscribe/trial/) and the Professional annual test-card order (POST /action/service/subscribe/). Both returned HTTP 200, sent purchaseCompleted without get_context, and reached the portal receipt after a successful handoff POST. No live charge was made. Agency lifetime also displayed its payment form at a 390 px viewport without horizontal overflow. The request logs and screenshots are in ../evidence/portal-freemius-submit/.

Live rollout fe2fda3 repaired submission. During smoke testing, an additional incognito Personal trial reported its first form height 3.9 seconds after navigation, yet the portal hid the already rendered form and showed its 30-second timeout. Freemius's separate loaded event arrived at 63.8 seconds. Evidence: before-frame-visibility-timeout.png and before-frame-load-messages.json. Follow-up: treat a valid height from the current verified Freemius frame as ready so tertiary resources cannot hide the form.

In the follow-up local production build, a browser listener withheld only the Freemius loaded message. The real Freemius sandbox still displayed both the Personal trial and Personal lifetime payment forms. The trial also submitted, received purchaseCompleted, and reached the portal receipt. Logged messages include the valid height and the intentionally withheld loaded event.

Deployments fe2fda3 and 059ab8c completed. All three production paid form checks passed in fresh Chromium Incognito (Personal lifetime, Professional annual, Agency lifetime). Visibility was recorded at the first valid height, before the separate loaded event on every tested plan. See after-live-paid-forms.json.

[[2026-11-16]] Mon 19:38
Production direct-form regression completed in fresh Chromium Incognito contexts: Personal free trial and Personal/Professional/Agency paid forms expose native Freemius inputs and submit controls; no preparatory account screen. Screenshots after-direct-* and observations after-direct-observations.json. Original submission defect verified resolved with real Freemius trial and paid sandbox flows earlier; no real charge. Closing checkout visibility/submission issue; account handoff tracked separately as #167.
