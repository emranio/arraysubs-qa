# Download rule copy links — verification

Feature verification on `http://localhost:10013`, September 17, 2026. Active QA plan/task: N/A.

Core owns the implementation; no Pro-specific implementation is required. The copy button uses the same signed URL generator as My Account Downloads. A reusable URL identifies a file; authorization remains specific to the current visitor.

## Checks

- Admin Downloads Rules: copy icon appears inline immediately after the filename. Observed the original browser Clipboard API successfully writing the exact URL shown to a different customer in My Account Downloads.
- Denied Clipboard API: the selection-based fallback successfully copies the same URL. When both methods are denied, the interface displays an error instead of a success message.
- Unsaved replacement: copy is disabled until saved. Discard restores the saved file's copy button. Saving through the real settings UI returns usable links without persisting generated URLs.
- Desktop and 390px mobile: screenshot inspection plus element bounds confirm the icon stays inside the file card, beside the filename. Long filenames truncate without horizontal overflow.
- Custom button on a temporary WordPress page: an eligible customer can use the admin-copied link, and My Account's remaining allowance decreases.
- Independent customer: HTTP 200, attachment disposition and exact fixture contents confirmed. Each customer has an independent allowance.
- Limit reached: HTTP 403 and “You have reached the download limit for this file.”
- Expiry reached: HTTP 403 and “Access to this download has expired.”
- Guest: HTTP 403 and login-required message.
- Logged-in administrator who does not match the test rule: HTTP 403 and access-denied message. Admin capabilities do not bypass download conditions.
- Altered signature: HTTP 403 and invalid-link message.
- Admin settings endpoint remains inaccessible without admin REST authentication.
- Core-only runtime check: generated links match across users, renamed rules/files and the admin response. Generating URLs does not create an allowance or consume downloads.
- Existing product helper link copying still works after sharing its clipboard helper.
- Production build, PHP syntax checks, whitespace checks and source file line limits passed.

Temporary rule `arraysubs-copy-qa-20260917`, users 3/4, files/attachments 6602/6603 and the custom-button page were removed after verification. The original “coming soon” setting was restored. No subscription/order fixtures were changed.

Screenshots: `/tmp/arraysubs-download-copy-desktop.png`, `/tmp/arraysubs-download-copy-mobile-final.png`, `/tmp/arraysubs-download-copy-unsaved.png`, `/tmp/arraysubs-download-copy-guest-denied.png`, `/tmp/arraysubs-download-copy-rule-denied.png`, `/tmp/arraysubs-download-copy-limit-denied.png`, `/tmp/arraysubs-download-copy-expiry-denied.png`.
