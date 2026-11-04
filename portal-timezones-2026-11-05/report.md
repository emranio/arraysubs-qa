# Portal timezone verification

Executed November 5, 2026, Asia/Dhaka. [Plan](plan.md), [progress board](progress/tasks), issue [160](../issues/tasks/160-portal-admin-dates-use-inconsistent-timezone-and.md).

Implementation pushed to user-portal main: `628864974af8caacc32e31646d7bf2bba4154da7`. [Deployment run](https://github.com/emranio/arrayhash-user-portal/actions/runs/41906506685).

## Resulting behavior

- All portal administrator dates use Asia/Dhaka (UTC+6): email lists/details/filters, subscribers, users, account/domain activity, admin invoices and existing offboarding records. Administrator presentation ignores customer profile preferences.
- Customers choose a timezone in Settings → Account details. Existing and new users default to UTC. Product, license, trial, subscription, domain and invoice dates follow the preference, including invoice calendar filters. Saving refreshes the account cache, and fresh sign-ins retain the preference.
- Portal-generated customer email dates use the preference, with the date-specific UTC offset; internal admin email dates use UTC+6. Historical stored email content and retry payloads retain the original text. Provider-generated invoice documents and billing emails remain under the provider's formatting.
- API timestamp values, database timestamp columns, service communication and calculations remain UTC instants. The named timezone is separate preference metadata. Saving it does not shift stored data. A pre-existing provider payment parser was corrected to interpret provider UTC strings independently of the server timezone before persistence.
- Account lookup failures show a recoverable error with Try again; dates do not flash in UTC while a saved timezone is loading. Invoice date controls were widened after visual inspection found clipped year text.

## Automated validation

All 54 test cases passed with zero failures. In addition, the focused React rendering harness passed loading/error/retry/missing-user scenarios for all four actual customer page components and the cached-timezone refresh-failure case.

| Suite | Passed | Evidence |
| --- | ---: | --- |
| Customer date parsing, calendar boundaries, DST and fractional offsets | 4 | [log](evidence/customer-date-tests.log) |
| Profile migration, validation, persistence, authentication, impersonation, UTC API expiry | 6 | [log](evidence/profile-tests.log) |
| Admin filters against real PostgreSQL under multiple session timezones | 3 | [log](evidence/admin-date-tests.log) |
| Customer/admin email formatting, recipient lookup, DST offsets, immutable resends | 13 | [log](evidence/email-tests.log) |
| Offboarding contract regressions | 15 | [log](evidence/offboarding-contract-tests.log) |
| Offboarding intake/delivery/database regressions | 11 | [log](evidence/offboarding-email-tests.log) |
| Real payment webhook UTC persistence boundary | 2 | [log](evidence/webhook-utc-tests.log) |
| Four-page account lookup failure and retry rendering | All scenarios | [log](evidence/account-loading-tests.log) |

Typecheck and final production build passed: [typecheck](evidence/typecheck.log), [build](evidence/build.log). The build retained the existing Turbopack trace warning for the licensing updates route; compilation and typechecking completed successfully. Modified source line counts were checked; no file exceeds 3,000 lines.

## Actual browser and storage verification

See the [customer browser report](customer-browser.md) and [admin browser report](admin-browser.md) for exact routes, fixture IDs, steps and observations. Actual local browser tests covered default UTC, saving Asia/Dhaka and America/New_York, purchase/expiry/trial/domain/invoice dates, native invoice date filtering, fresh login persistence, admin preference independence, and a captured customer reset email. The email's rendered New York expiry matched PostgreSQL's independent conversion of the stored UTC instant.

Strict baseline comparisons confirmed original database timestamps stayed unchanged, and authenticated customer API dates retained their UTC values: [API/storage evidence](evidence/local-api-db-utc-after-new-york.json), [reset email/storage evidence](evidence/local-customer-reset-email.json).

The required agent-browser CLI was used first. Its screenshot command hung both owned sessions; actual browser screenshots and visual inspection continued through connected Edge. Only the owned blocked process trees were stopped. The local app, disposable schema, temporary clone, private credentials and captured email files were cleaned up after evidence was sanitized. No production customer account/billing data was edited and no real email was sent during this timezone QA cycle.

## Live deployment verification

Deployment completed successfully, including migration, application reload, Cloudflare purge and public health checks: [deployment metadata](evidence/deployment.json).

Used the existing signed-in Edge administrator session (`emran@arrayhash.com`) on `https://user-portal.arrayhash.com`. No live setting was saved, email resent, or record deleted.

| Live surface | Verified result |
| --- | --- |
| `/settings/` | Timezone selector is visible in Account details, defaults to `UTC (UTC+0)`, and explains that account dates and email times follow it. Existing billing fields/preferences were left unchanged. [Screenshot](evidence/live-settings.jpg). |
| `/admin/emails/` | Sent header, both calendar filters and footer explicitly use UTC+6. Existing offboarding email appears at Nov 5 01:04. |
| Email From/To date controls | Selected Nov 5 with the native browser calendar. URL became `?from=2026-11-05&to=2026-11-05`; exactly one record matched: the offboarding email stored at Nov 4 19:04:55 UTC. Date controls display complete day/month/year. [Screenshot](evidence/live-email-filter.jpg), [text](evidence/live-email-filter.txt). |
| `/admin/emails/14bcd715-1443-4ad2-91dc-d3077e3177c0/` | Subtitle, Created and Last tried show `2026-11-05 01:04:55 UTC+6`. Historical message body still shows its original UTC text. [Evidence](evidence/live-email-detail.txt). |
| `/admin/subscribers/` | Updated header is UTC+6. First three visible dates were Nov 4 18:10, 17:29 and 03:47. Horizontally scrolling the existing wide table reveals complete timestamps. [Sanitized metadata](evidence/live-subscriber-times.json), [date-column screenshot](evidence/live-subscriber-times.jpg). |
| `/admin/users/` | Joined header is UTC+6; dates render clearly, with no layout regression. [Sanitized metadata](evidence/live-user-times.json). |

Live browser error log returned no errors. Customer names/emails from the subscriber/user tables were omitted from saved evidence. The existing user tab was left on Settings for convenient access to the new timezone selector. Issue 160 is resolved; no open blocker remains for this scope.
