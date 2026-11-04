# Offboarding QA — 2026-11-05

Scope: ArraySubs 2.1.0, ArraySubs Pro 1.3.0, and the ArrayHash user portal. This was focused offboarding verification, not a full subscription-engine regression.

## Environments and evidence

- Real WordPress browser interactions used LocalWP at `http://localhost:10013/` with the user's authorized admin account. Both plugins started active.
- Five initial browser-generated records were verified in the live portal's administrator list and detail page through the user's signed-in Edge session. Screenshots: `evidence/live-offboarding-records.jpg` and `evidence/live-offboarding-detail.jpg`.
- Additional portal authorization, filter, pagination, network recovery, and input-boundary tests used the actual portal application with a dedicated local PostgreSQL schema and disposable fixture accounts. These were separate from production users and records.
- Agent-browser drove LocalWP and the isolated portal. Its Chrome connector could not attach the user's existing Edge login, so the app's browser connector was used for the authenticated production readback.
- The external contact page was opened and inspected; its form was never submitted.

## Completed checks

| Area | Result and concrete evidence |
| --- | --- |
| Plugin builds | Core and Pro production builds completed, including POT generation; `i18n:update` completed for both. The corrected core entry was rebuilt after the Unicode fix. |
| Popup | Both plugins opened the modal. All seven checkboxes were available, multiple selections worked, and Something else revealed the optional textarea. See `pro-popup.png`, `pro-all-reasons.png`. |
| Optional feedback | Pro help, core help, and core deactivation worked with no selected reasons. No reason is required to leave. |
| Cancellation and accessibility | Close/Escape left the plugin active and sent no event. Keyboard focus entered the dialog and wrapped between its controls. The 390×844 mobile view was legible without horizontal overflow (`core-popup-mobile.png`). |
| Get Help Directly | The real ArrayHash contact page opened in a separate tab, with no opener reference. The plugin remained active and one help record was stored. Separate dialog interactions produced separate IDs. See `contact-new-tab.png`, `pro-help-return.png`. |
| Pro deactivation | All seven reasons and Bengali text reached the production portal. Script markup was sanitized. Exact event: `f0729a6d-2ade-4c29-86e5-4482b8f2c251`. |
| Core without active Pro | Core's popup, help, deactivation with no reasons, and reactivation worked while Pro was inactive. |
| Direct and bulk actions | Actual Pro bulk deactivation and core direct nonce-link deactivation logged separate events without reasons. See `pro-bulk-deactivated.png`, `core-direct-outage-deactivated.png`. |
| Transport failure/recovery | A real refused connection did not block deactivation. The pending receipt survived with both plugins inactive; after core activation and the retry delay, the identical UUID was accepted once and removed from the outbox. Exact event: `91550a74-d7b3-48bd-b405-389040255f1e`. |
| Duplicate delivery | Resubmitting the same saved help event returned HTTP 200 with `duplicate: true`; the database retained one record. |
| Payload identity | Observed payloads included the correct plugin/version, website, timestamp, and the effective Help/onboarding subscribed email. Selected and empty reasons matched the UI. |
| Nonce protection | Invalid AJAX nonce returned HTTP 403. |
| Unicode limit | Oversized supplementary Unicode input is now bounded to the portal's UTF-16 limit without splitting characters. An authenticated browser request was accepted with HTTP 201, then acknowledged as a duplicate on retry. PHP regression tests passed with and without multibyte functions. |
| Portal admin UI | Sidebar entry, totals, list, record detail, all reasons, full text, subscribed email, website, slug/version, and timestamps were checked in production. |
| Portal filters and pagination | Plugin/event/no-reason combination returned the matching Pro deactivation. With 28 isolated API fixtures, email search returned 28; Next showed rows 26–28, Previous returned rows 1–25, with 28 unique IDs and no overlap. Exact UUID returned one; unmatched search and prior-day UTC dates returned zero. Same-day UTC dates included all 28; Clear restored all 33 rows and empty fields. See `evidence/portal-filter-pagination-results.md`. |
| Portal access | Guests redirect to sign-in. Ordinary fixture users see no admin menu and get actual HTTP 404 from both list/detail routes with no feedback data. The denial page is readable, and its dashboard link works. See `portal-authorization-results.json` and `portal-user-denied-fixed-response.json`. |
| Portal contract tests | 15/15 parser tests passed, including empty email/reasons, future product registry extension, invalid dates/URLs/types, and payload limits. |
| Admin email integration | 9/9 tests passed against a disposable PostgreSQL schema with intercepted email transport: both event types, correct recipient/Reply-To, escaped text, invalid intake, duplicate/conflicting receipt, failure/retry, lost provider response, concurrent claims, failed sent-status update, expired lease/window, and migration rerun. Portal typecheck and production build passed. |
| Production admin email | After deployment, a real Pro popup deactivation returned HTTP 201 and created event `dbd20445-3a8d-4587-aa40-5bfb9ed12d5d`. The live record shows notification `sent`, linked to email log `14bcd715-1443-4ad2-91dc-d3077e3177c0`. The email was sent through Resend to `emran@arrayhash.com`, with the saved recipient as Reply-To, both selected reasons and Bengali text. Plain-text and HTML previews were inspected. |
| Production email deduplication | Reposted the identical production event: HTTP 200, `duplicate: true`. The filtered live Email logs still contained exactly one notification with one send attempt. See `live-email-duplicate-receipt.json` and `live-offboarding-email-after-retry.jpg`. No manual resend was clicked. |
| Guide validation | Build passed for 308 pages/281 guides; 55/55 unit tests passed. Offboarding search questions passed. The full guide check exited 1 because one audit-log question ranked its overview before its settings guide among 600 search checks. This separate issue is recorded as #158; see `user-guide-check.log`. |
| Plugin source sizes | All eight source files touched across the core/Pro feature commits remain below 3,000 lines (largest 325). See `plugin-source-line-counts.txt`. |

## Bugs found and fixed

- **#155:** PHP counted Unicode code points while JavaScript and the portal counted UTF-16 units. Long emoji feedback could be rejected indefinitely. The shared core limiter now matches browser/API limits and preserves complete characters. Browser regression and 8 PHP cases passed, including without multibyte extensions.
- **#156:** The default Next.js 404 inherited white text under a dark OS preference and appeared blank on the portal's white surface. A scoped admin not-found page now uses existing portal components/colors. Both protected routes were retested for visible content and correct HTTP 404.
- **#157:** The email-log link touched the sent timestamp. It now sits on a separate line with spacing, verified in the deployed portal (`live-offboarding-email-record-fixed.jpg`).

## Delivery boundaries

Pending WordPress records retry when a registered plugin handles another admin request. If both plugins remain inactive or the site is permanently removed/offline, there is no running plugin to finish delivery. Hooks explicitly suppressed by WordPress operations cannot be observed. These limitations do not block deactivation.

Build output included existing translator-comment and Next tracing warnings; WordPress CLI showed an existing Elementor deprecation. No offboarding browser error was observed. No Composer installation or PHPCS run was needed.

## Separate documentation finding

**#158 was open at the end of this run:** “How do I choose which activity events appear in the audit log?” ranked `audit-logs/review-the-activity-audit-trail` before the expected `audit-logs/choose-what-activity-is-logged`. This was found by the broader guide check, not the offboarding workflow. No baseline was run to establish whether it predated this task, and that original check was not reported as passing.

**Resolved in the user's requested follow-up on 2026-11-05:** clarified the settings guide's search metadata, retained the original assertion and added four regression questions. The complete guide check now passes all 55 unit tests and 604 ranking checks; 11 desktop/mobile browser checks passed. See `qa/audit-search-2026-11-05/report.md` and its evidence for the resolution.

## Restoration and deployment

Both plugins are active again. The complete active-plugin list matches the original baseline, and the offboarding outbox is empty. Temporary observer code, endpoint overrides, and run-owned help-receipt transients were removed. No notification preferences, subscriptions, orders, or unrelated plugins were changed. See `evidence/restored-wordpress-state.json`.

The dedicated local portal process, isolated schema, fixture credentials, and temporary source copy were removed; port 3016 has no listener. Only run-owned agent-browser sessions were closed. The user's signed-in Edge session remains available. See `evidence/local-portal-cleanup.json`.

Six test records from `http://localhost:10013/` remain in the production portal as evidence. The new notification feature sent one real labelled QA email; no retrospective emails were sent for the five records created before that feature was added. The portal's log confirms provider acceptance; inbox delivery/read status was not inspected.

Source changes were committed and pushed to main: core `110aafe`, Pro `8b44c22`, portal notification `d623f5c`, followed by portal display spacing fix `53d9f31`; guide `cf03efd`. Notification deployment [41893059076](https://github.com/emranio/arrayhash-user-portal/actions/runs/41893059076) and final deployment [41893732391](https://github.com/emranio/arrayhash-user-portal/actions/runs/41893732391) both succeeded. The final portal layout was checked after deployment. All three issues found in this QA scope are closed.
