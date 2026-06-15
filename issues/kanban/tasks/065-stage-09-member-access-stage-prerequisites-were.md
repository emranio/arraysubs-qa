---
id: 65
title: 'stage-09: Member Access stage prerequisites were missing on test site'
status: closed
priority: high
created: 2026-05-20T15:29:43.594685596+02:00
updated: 2026-05-22T04:01:04.457950093+02:00
started: 2026-05-22T03:51:44.049355156+02:00
completed: 2026-05-22T04:01:04.457921159+02:00
tags:
    - qa
    - stage-09
    - members-access
    - fixture
claimed_by: mold-glade
claimed_at: 2026-05-22T04:01:04.457949993+02:00
class: standard
---

Stage 09 README expected member1/member2/nonmember users, Premium Content/Pricing/Become a Member pages, Premium Widget/Members Tee products, and existing member subscriptions. At stage start, the three users did not exist, required pages did not exist, and Premium Widget/Members Tee products did not exist. QA seeded minimal fixtures via WP-CLI to continue, but downstream rule tests depending on CPTs, media downloads, multi-browser login sessions, and prior lifecycle state remain fixture-sensitive.

[[2026-05-22]] Fri 03:55
Plan: compare Stage 09 README prerequisites to current site, seed missing durable fixtures (users/passwords/roles, pages, products, KB posts, shortcode sandbox, downloadable zip attachment, member subscriptions including member1 active Pro, member2 paused Pro and active Basic, nonmember no subs), keep existing Stage 09 rules intact, then verify with WP-CLI and browser spot checks that fixtures exist and key pages render.

[[2026-05-22]] Fri 04:00
Fixed: seeded Stage 09 prerequisite fixtures via WP-CLI. Users: member1/member2/nonmember/qa_admin with documented QA passwords and roles. Products: Pro Plan, Basic Plan, Premium Widget, Members Tee. Pages: Premium Content, Pricing, Become a Member, preview, drip test, shortcode sandbox. KB CPT posts: KB-A/KB-B/KB-C with KB-C per-post restriction. Subscriptions: member1 active Pro, member2 paused Pro plus active Basic, nonmember none. Download fixture: pro-templates.zip attachment #990. Verified via WP-CLI and agent-browser: member1 login, Premium Content marker, Shortcode Sandbox render.
