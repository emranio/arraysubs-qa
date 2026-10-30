# Read-only security risk check

## Existing blocked issue #19 remains reproducible on the QA host

- Active QA task: #4, scheduled day 2026-10-31 Asia/Dhaka. Plan: qa/release-2026-10-31-0208/plan.md.
- Affected original fixture: subscription 33278 and order 33277, as recorded in existing issue #19. These links were not re-correlated to current database records in this read-only check.
- Customer: N/A for this request; fresh unauthenticated guest browser. Original customer user ID/login: N/A, not recorded in issue #19.
- Browser context: new isolated rqa0208-securityguest Chromium session, no authentication state loaded, desktop 1280×720. Session closed after verification.
- Exact URL: https://mirror-help.arrayhash.com/wp-content/uploads/arraysubs-box-uploads/pDwqQLRwgUlKSfz3aVkF.png
- Steps: Open the existing known synthetic test-file URL from issue #19 as a logged-out guest; inspect browser title, screenshot and network response. No upload, configuration change or mutation.
- Expected: Raw private box-upload path refuses unauthenticated access (403 or equivalent protected response).
- Actual: Browser renders the known 8×8 PNG. Main document GET returns HTTP 200, title pDwqQLRwgUlKSfz3aVkF.png (8×8).
- Proof: evidence/security-box-raw-network.txt; security-box-raw-known.txt; security-box-raw-known.png (inspected).
- Known scope: Original issue says signed, authorized plugin endpoints are fixed but Caddy ignores plugin .htaccess; a host deny rule remains outstanding. This check verifies raw-path exposure only. Signed-link authorization and arbitrary/private customer uploads were not tested or accessed. No claim that current local plugin code regressed.
- Existing status: preserve blocked; append this run occurrence to issue #19. No new duplicate issue or status change.
