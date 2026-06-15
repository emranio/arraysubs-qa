---
id: 100
title: 'stage-14: Documented pretty REST export URL returns site HTML'
status: closed
priority: high
created: 2026-05-23T11:56:42.298080307+02:00
updated: 2026-05-24T15:42:36.051129732+02:00
started: 2026-05-24T15:38:22.766731432+02:00
completed: 2026-05-24T15:42:36.051127307+02:00
tags:
    - qa
    - stage-14
    - admin-subscriptions
    - export
    - rest
claimed_by: shell-quartz
claimed_at: 2026-05-24T15:42:36.051129632+02:00
class: standard
---

Original task: stages/14-admin-subscriptions/07-export-subscriptions.md\n\nBrowser QA while logged in: visiting https://mirror-help.arrayhash.com/wp-json/arraysubs/v1/subscriptions/export?format=json redirects to /wp-json/arraysubs/v1/subscriptions/export/?format=json and renders the public Blog/Hello world theme page instead of JSON. Unauthenticated curl to the same pretty URL follows the same HTML route.\n\nUsing the non-pretty ?rest_route=/arraysubs/v1/subscriptions/export&format=json endpoint returns proper REST auth behavior (401 unauthenticated).\n\nExpected: documented pretty REST endpoint returns JSON with HTTP 200 for authenticated admin and 401/403 unauthenticated.

[[2026-05-24]] Sun 15:38
Fix pass started 2026-05-24 by shell-quartz. Verifying documented pretty REST URL and WordPress rewrite/rest_url behavior before code changes.

[[2026-05-24]] Sun 15:39
Plan: add narrow parse_request fallback for /wp-json/arraysubs/v1/* when WordPress has no rewrite rules, so core rest_api_loaded dispatches normal ArraySubs REST controllers and permission checks.

[[2026-05-24]] Sun 15:42
Fixed and verified. Code: Boot now maps /wp-json/arraysubs/v1/* to rest_route on parse_request when rewrite rules are absent, so core REST dispatch handles ArraySubs pretty routes. Check: php -l Boot.php passed. Browser/REST: agent-browser confirmed pretty URL shows JSON REST 401 instead of public blog HTML when unauthenticated. agent-browser confirmed unauth pretty URL returns 401 application/json; admin nonce request returns 200 application/json top-level array with 308 rows; plain browser address bar without REST nonce returns JSON 401 per WordPress nonce/auth behavior, not theme HTML. Screenshot: qa/artifacts/issue-100-pretty-json-browser.png.
