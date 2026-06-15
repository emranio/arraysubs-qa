---
id: 147
title: stage-18 customer portal skip and pause actions fail
status: closed
priority: critical
created: 2026-05-23T18:14:54.043138117+02:00
updated: 2026-05-24T14:02:34.343727491+02:00
started: 2026-05-24T13:42:16.841491947+02:00
tags:
    - stage-18
    - qa
    - bug
    - customer-portal
    - skip-pause
class: standard
---

Stage: qa/stages/18-renewal-followup/10-skip-and-pause-over-renewal-cycles.md\n\nFixture: member-stripe@example.com, subscription #1436, Standard Weekly #200, Stripe card ending 4242. Browser session logged in as the customer.\n\nSkip failure: On the customer subscription detail, clicked Skip Next Renewal, selected 2 cycles, confirmed. UI showed generic toast/error: 'Failed to skip renewal. Please try again.' No skip meta was written. Server-side dispatch of the same customer REST route as user #42 succeeded immediately and set _skip_cycles_remaining=2, original_next_payment=2026-05-30 13:38:38 UTC, next_payment=2026-06-13 13:38:38 UTC.\n\nPause failure: After skip was cleared and subscription was Active again, clicked Pause Subscription, entered 14 days, confirmed. UI showed generic error: 'Failed to pause subscription. Please try again.' Server-side dispatch of /arraysubs/v1/my-subscriptions/1436/pause as user #42 with days=14 succeeded and moved the subscription On Hold with pause_start=2026-05-23 16:11:58 UTC, pause_end=2026-06-06 16:11:58 UTC, next_payment shifted to 2026-06-13 15:07:58 UTC.\n\nThis appears to be a customer-portal AJAX/client failure, not a domain validation failure. Related old closed issues: #51 and #52.\n\nImpact: customers cannot use Skip Renewal or Pause Subscription from My Account even though backend routes and permissions allow the actions.

[[2026-05-24]] Sun 14:02
Fix 2026-05-24: customer portal skip/pause now use the shared requestPortalJson() fetch helper instead of the old jQuery AJAX path, so nonce, JSON content-type, credentials, and REST error parsing are consistent with other portal REST calls. Verified live as member-stripe@example.com / user #42 on subscription #1436 against qa/stages/18-renewal-followup/10-skip-and-pause-over-renewal-cycles.md. agent-browser confirmed the #1436 subscription page exposes Skip Next Renewal and Pause Subscription controls; agent-browser fallback captured screenshots and network proof. Skip selected 2 cycles and POST /my-subscriptions/1436/skip returned 200 with message 'Successfully skipped 2 renewal cycle(s).'; WP-CLI proof: status=arraysubs-active, skip_remaining=2, skip_count=2, original=2026-05-31 11:53:32, next=2026-06-14 11:53:32. Pause then tested from a clean active fixture with 14 days; POST /my-subscriptions/1436/pause returned 200 with message 'Subscription paused. Will auto-resume on 7 June, 2026 (UTC+6).'; WP-CLI proof before cleanup: status=arraysubs-on-hold, pause_count=1, pause_start=2026-05-24 12:00:28, pause_end=2026-06-07 12:00:28, next=2026-06-14 11:59:36. Screenshots: qa/artifacts/issue-147-before-skip.png, qa/artifacts/issue-147-skip-success.png, qa/artifacts/issue-147-before-pause.png, qa/artifacts/issue-147-pause-success.png. Fixture #1436 restored afterward to Active, next=2026-05-31 12:01:22, skip=0, pause=0.
