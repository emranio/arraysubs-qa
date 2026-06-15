---
id: 39
title: 'stage-06: Portal Change Plan modal fails to load options'
status: closed
priority: high
created: 2026-05-20T14:06:12.726871237+02:00
updated: 2026-05-22T02:55:39.900180455+02:00
started: 2026-05-22T02:47:37.222633997+02:00
completed: 2026-05-22T02:55:39.900179293+02:00
tags:
    - qa
    - stage-06
    - customer-portal
    - plan-switching
claimed_by: mold-glade
claimed_at: 2026-05-22T02:55:39.900180345+02:00
class: standard
---

Stage 06 Task 04 on customer portal subscription #618 (Basic Monthly with Basic Monthly -> Pro Monthly upgrade target #495 configured) showed Change Plan button as expected, but clicking it opened 'Change Your Plan' modal with error 'Failed to load plan options. Please try again.' Expected: modal/form lists available plan switch options without JS/API failure.

[[2026-05-22]] Fri 02:51
Plan: reproduce browser failure, compare REST path. Repro confirmed: clicking Change Plan shows 'Failed to load plan options'. In-page fetch to same switch-options URL succeeds with Pro Monthly option; jQuery.ajax to same URL fails status=0 TypeError: Illegal invocation under agent-browser. Fix narrowly: convert PlanSwitchModal switch-options, preview, and execute REST calls from jQuery.ajax to fetch with same nonce, credentials, JSON parsing, and existing confirm/toast/loading UI. Rebuild assets, then rerun portal browser flow.

[[2026-05-22]] Fri 02:55
Fixed. Code: arraysubs/src/resources/customerPortal.js PlanSwitchModal now uses fetch-based requestJson() for switch-options, switch-preview, and switch execution. Built arraysubs customer-portal asset with npm run build. Browser QA: customer-pending@example.test opened /my-account/view-subscription=618, clicked Change Plan, modal displayed Pro Monthly upgrade instead of load error; clicked Select, proration preview loaded with credit/charge/amount due 9.33 and Confirm Plan Change button. Did not confirm switch; WP-CLI verified no pending switch remains.
