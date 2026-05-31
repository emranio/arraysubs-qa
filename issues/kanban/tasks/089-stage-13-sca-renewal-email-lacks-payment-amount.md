---
id: 89
title: 'stage-13: SCA renewal email lacks payment amount'
status: closed
priority: medium
created: 2026-05-23T10:51:45.307404377+02:00
updated: 2026-05-24T11:50:57.929674475+02:00
started: 2026-05-24T11:44:51.4616562+02:00
tags:
    - qa
    - stage-13
    - email
    - sca
    - stripe
class: standard
---

Original task: stages/13-emails/04-pro-emails.md subtask 4.3.\n\nExpected when SCA/3DS renewal verification email is wired: body shows auth URL/link, payment amount, and deadline; placeholders for auth_url/payment_amount/auth_deadline rendered.\n\nObserved via arraysubs_renewal_requires_verification hook capture for subscription #683 / renewal order #1034:\n- Email registered and enabled.\n- Subject: [mirror-help.arrayhash.com] Verify your subscription renewal #683.\n- Body includes Renewal Order #1034, Payment Method Visa ending in 3184, Grace period 26 May, 2026 2:50 PM, and a Complete verification link.\n- HTML contains wc-stripe-confirmation=1 auth link.\n- Body did not include the renewal amount/order total (order #1034 total is 28.99).\n\nExpected: include payment amount so customer can identify what they are authorizing.\n\nDelivery/inbox proof remains blocked by issue #40; this was captured before actual send with wp_mail interception. PayPal/Paddle skipped per instruction.

[[2026-05-24]] Sun 11:50
Fixed by updating `RenewalRequiresVerificationEmail` and both SCA verification templates. Added `{auth_url}`, `{payment_amount}`, and `{auth_deadline}` placeholders, reusing generated Stripe confirmation URL and a subject-safe plain payment amount. HTML/plain body now includes Payment amount.

Verification: `php -l` passed for the email class and both templates. WP-CLI intercepted subscription #683 / renewal order #1034 email to cust1@test.local: subject `[mirror-help.arrayhash.com] Verify your subscription renewal #683`; body contains `$28.99`, `wc-stripe-confirmation=1`, and Grace period/deadline. Custom subject `[QA] {payment_amount} {auth_deadline} {auth_url}` rendered all placeholders with no raw tokens left.
