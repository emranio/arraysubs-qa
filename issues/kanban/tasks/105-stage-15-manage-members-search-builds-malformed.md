---
id: 105
title: 'stage-15: Manage Members search builds malformed REST URL'
status: closed
priority: critical
created: 2026-05-23T12:12:59.95804553+02:00
updated: 2026-05-24T12:16:49.160462402+02:00
started: 2026-05-24T12:11:26.527580568+02:00
tags:
    - qa
    - stage-15
    - manage-members
    - member-insight
    - rest
class: standard
---

Stage 15 task 01: Manage Members search field accepts input, but no dropdown appears for 'customer' or other 2+ char queries. Browser-side fetch using window.arraySubs.env.apiBaseUrl reproduces 404: apiBaseUrl is https://mirror-help.arrayhash.com/index.php?rest_route=/ and frontend concatenates arraysubs/v1/members/search?query=customer directly, producing a malformed rest_route URL. Backend REST works via rest_do_request for /arraysubs/v1/members/search with query=customer and query=vip@example.com. UI also gives no visible error/toast on the 404, so search appears broken/silent.

[[2026-05-24]] Sun 12:16
Fixed Manage Members REST URL construction by replacing raw `env.apiBaseUrl + route` concatenation with shared `buildRestUrl()` for both `/members/search` and `/members/{id}`. Added non-2xx checks so failed search/detail calls surface the existing toast error path instead of silently clearing results. Rebuilt ArraySubs assets with `npm run build`.

Verification: Alumnium could not expose Manage Members content after route navigation, so per AGENTS fallback rule I used Playwright with screenshots in `qa/artifacts/issue-105-manage-members-search/`. Browser test confirmed one-character `c` sends no request and no dropdown; `customer` sends `https://mirror-help.arrayhash.com/index.php?rest_route=/arraysubs/v1/members/search&query=customer` with 200 and shows 20 results; `vip@example.com` sends the same valid REST shape with 200 and shows one result; selecting it loads `#/manage-members/37` and profile stats render. No console errors captured.
