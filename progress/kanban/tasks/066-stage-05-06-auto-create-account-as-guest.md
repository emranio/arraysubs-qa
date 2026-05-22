---
id: 66
title: 'stage-05: 06 Auto-Create Account as Guest'
status: closed
priority: high
created: 2026-05-19T22:56:07.404870778+02:00
updated: 2026-05-20T12:06:58.9001593+02:00
started: 2026-05-20T10:28:05.941947576+02:00
completed: 2026-05-20T12:06:58.900157327+02:00
tags:
    - qa
    - stage-05
claimed_by: mold-glade
claimed_at: 2026-05-20T12:06:58.9001592+02:00
class: standard
---

Source: stages/05-checkout/06-auto-create-account-as-guest.md

[[2026-05-20]] Wed 12:06
QA failed. Settings confirmed via WP-CLI: arraysubs checkout.auto_create_account=true, Woo guest checkout=yes, checkout signup=yes. Confirmed guest-auto@example.test did not exist before test. In fresh unauthenticated browser, added Standard Weekly and opened active block checkout. Checkout showed email/billing fields and Log in link, but no Account password field and no forced Create account toggle/form. Guest-only checkout was not blocked at form-render level. Also saw notification: 'Only express payment methods are available for this order. Please select one to continue.' Logged critical issue #28. Did not place order because account-creation precondition failed.
