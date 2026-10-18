---
id: 45
title: External member-download links fall back to wp-admin and consume download quota
status: closed
priority: high
created: 2026-10-18T13:19:30.943143+06:00
updated: 2026-10-18T13:31:23.871084+06:00
started: 2026-10-18T13:29:01.270285+06:00
completed: 2026-10-18T13:31:23.871084+06:00
tags:
    - bug
    - members-access
    - downloads
    - redirect
class: standard
---

## QA linkage and verification level

- Active QA task ID: N/A.
- Scheduled day: N/A.
- Active plan markdown path: N/A; no active QA cycle.
- Investigated 2026-10-18 against ArraySubs main 525a2b6 and Pro main 95c3e25.
- Source-confirmed redirect defect. Fresh end-to-end browser reproduction has not been run.

## Affected records and context

- Subscription ID(s) and order ID(s): N/A; none accessed or changed.
- WordPress user/customer ID, login/email, and role: N/A; no account used. Required reproduction actor: authenticated customer satisfying a configured download rule.
- Local entry page: http://localhost:10013/my-account/downloads/
- Exact handler route shape: http://localhost:10013/?arraysubs_download=<opaque-file-id>&signature=<valid-generated-HMAC>; an actual signed URL requires a saved rule/file pair and is N/A for this source-only investigation.
- Browser/user context during this investigation: N/A; source inspection only.

## Reproduction to execute

1. As administrator, save a member-download rule with a valid external HTTPS file URL on a host absent from allowed_redirect_hosts and a finite per-file limit.
2. Sign in as an eligible customer and open My Account -> Downloads.
3. Follow the generated protected link. Observe the Location header/navigation target and the remaining download count.
4. Compare a readable local upload and an external host explicitly allowed by WordPress redirect filtering.

## Expected

After authorization, an administrator-configured external/CDN file can be downloaded. A rejected redirect must not consume a successful-download allowance.

## Actual and concrete proof

- RestrictionController.php:1288-1311 sanitizes saved file URLs with esc_url_raw() without restricting them to the local host.
- DownloadManager.php:214-226 validates and consumes per-file access using getFileAccess(..., true) before calling serveFile(). getFileAccess() increments access.used before any response is sent.
- DownloadManager.php:236-249 streams local files, but sends external/CDN files through wp_safe_redirect($file_url) without authorizing that configured host.
- Local WordPress wp-includes/pluggable.php:1645-1649 uses admin_url() as the safe-redirect fallback; :1729-1733 substitutes that fallback when the destination host is not allowed.
- The inferred result is a redirect to the admin fallback (potentially followed by login/account redirects) while the per-file allowance has already been consumed. No fresh browser, response-header, or DB observation is claimed.

## Scope and counterexamples

- Applies to ArraySubs Members Access custom file rules and the protected link generated for them, not every WooCommerce downloadable product.
- Readable same-site files are streamed directly. An external host explicitly in allowed_redirect_hosts can pass this redirect check.
- Pro has no replacement download-delivery implementation. The issue belongs to core and is not an authentication or condition-evaluation bypass.

## Fix direction

Use a validated destination taken from the saved authorized rule, permit only that configured destination host for this response, and validate delivery before consuming quota. Verify external/CDN, local, unauthorized, missing-file, and exhausted-limit cases through the browser before closure. No product code was changed during this investigation.

Fix plan: validate the authorized saved external target before consuming quota, then permit its exact configured host for the response. Preserve HMAC, login, conditions/drip checks, local streaming and counter locking. Reproduce the blocked redirect, then browser-check external success plus local/denial counterexamples. No remote fetches or quota rollback machinery.

Fixed 2026-10-18 in core with a 40-line focused change. Browser baseline as admin user 1 redirected the external saved target to /wp-admin/ and consumed 1 allowance. After fix, same protected link reached the actual file at 127.0.0.1:10013 (different host from localhost, no global allowlist change). Browser verified external content, missing local file, ftp URL, embedded user/password, altered signature, guest and exhausted limit responses. Invalid/missing/credential entries stayed used=0; local response initiated a browser download (ERR_ABORTED navigation) and used=1; external capped at used=3, including baseline, and further/guest requests did not increase it. Existing rules restored after fixture test. Records: admin 1, no subscriptions/orders; fixture rule fix45-20261018. Evidence qa/three-bug-fixes-2026-10-18/45-browser-cases.json, 45-counts-after.jsonl, screenshots/45-after-external-file.png. Independent cross-plugin safety review found no blocker. Remote-server delivery after redirect is outside quota accounting; no remote fetch or rollback was added.
