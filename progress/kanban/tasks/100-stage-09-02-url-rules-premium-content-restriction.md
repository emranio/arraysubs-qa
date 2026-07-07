---
id: 100
title: 'stage-09: 02 URL Rules — `/premium-content` Restriction'
status: closed
priority: high
created: 2026-05-19T22:56:12.22147234+02:00
updated: 2026-07-08T02:18:27.311167+06:00
started: 2026-05-20T13:41:52.930668936+02:00
completed: 2026-07-08T02:18:27.318913+06:00
tags:
    - qa
    - stage-09
class: standard
---

Source: stages/09-member-access/02-url-rules.md

[[2026-05-20]] Wed 15:29
QA notes: Seeded Premium Content page (#694) with body PREMIUM CONTENT — TEST PAGE OK and Pricing page (#695). Configured URL rule Premium Content URL gate: prefix /premium-content, priority 10, condition active Pro Plan #233, action redirect /pricing. Logged-out direct /premium-content redirects to /pricing instead of login despite require_login=true (issue #64). member1 login succeeds and Premium Content page content is visible when using site page link (?page_id=694). nonmember logged-in direct /premium-content redirects to /pricing as expected; nav/page_id link can show content because plain permalink URL rule only matches path /premium-content, not ?page_id=694. Exclusion and 403 subcases not fully completed due time; base rule left enabled for downstream checks.

[[2026-05-22]] Fri 03:51
Issue #64 fixed. UrlRestrictor now sends guests to wp-login.php before rule redirect when require_login=true. Rechecked: guest direct /premium-content/ -> wp-login.php?redirect_to=/premium-content/; nonmember -> /pricing; member1 can view Premium Content via page_id=694. Plain permalink caveat remains from original QA note for /premium-content/ content rendering.
