# Admin timezone browser verification

Date: November 5, 2026 (Asia/Dhaka). Active plan: [plan.md](plan.md), task 2 and the admin portion of task 6. WordPress subscriptions/orders: N/A. Portal admin fixture: `timezone-admin@example.test` (`6c3596c4-93b3-4a54-96bc-4a407d3ff821`, admin). Customer fixture: `timezone-customer@example.test` (`b9d14b0f-9f86-4c79-80ce-980efe9eb688`, customer).

Test target: actual Next.js app at `http://127.0.0.1:3017`, isolated clone and disposable loopback PostgreSQL schema. Real login, server-rendered pages, authenticated queries, and client invoice component were exercised with `agent-browser --session portal-tz-admin`. Provider responses and email delivery were intercepted inside this local server process. No production accounts, records, external provider mutations, or real email sends were involved. All admin checks below were read-only; the admin profile remained `UTC` throughout.

The common seeded instant was `2026-11-04T19:04:55.000Z`, which must display as `2026-11-05 01:04:55` in UTC+6. Original database timestamps were read back after browser verification and remained unchanged; see [storage evidence](evidence/local-admin-storage-utc.json). Customer profile had been saved as `Asia/Dhaka` by the concurrent customer UI test at this readback.

| Surface | Browser route / action | Observed result |
| --- | --- | --- |
| Email list | `/admin/emails/` | Fixed record displayed `2026-11-05 01:04`; header `SENT (UTC+6)`, date labels `From (UTC+6)` / `To (UTC+6)`, and footer `Dates and times: UTC+6.` |
| Email boundary filter, November 5 | `/admin/emails/?q=boundary&from=2026-11-05&to=2026-11-05` | Exactly two records: boundary 1 at `2026-11-05 00:00` and boundary 2 at `2026-11-05 23:59`; excluded boundary 0 at Nov 4 23:59 and boundary 3 at Nov 6 00:00. |
| Email boundary filter, November 4 | `/admin/emails/?q=boundary&from=2026-11-04&to=2026-11-04` | Exactly one record: boundary 0 at `2026-11-04 23:59`. |
| Email detail | `/admin/emails/60bc27f0-411e-49b5-918e-813da44206c0/` | Subtitle, Created, and Last tried displayed `2026-11-05 01:04:55`, explicitly labeled UTC+6. Historical message body retained `Existing body remains unchanged: 2026-11-04 19:04:55 UTC`. |
| Subscribers | `/admin/subscribers/` | Fixture row displayed updated `2026-11-05 01:04`; header `UPDATED (UTC+6)`. |
| Users list | `/admin/users/` | Both accounts displayed joined `2026-11-05`; header `JOINED (UTC+6)`. |
| Account details | Opened customer from users list | Created `2026-11-05 01:04`; current login activity displayed Last seen `2026-11-05 02:16`; both labels UTC+6. |
| Domain activity | Customer detail `/admin/users/b9d14b0f-9f86-4c79-80ce-980efe9eb688/` | Created, first activated, and last seen each `2026-11-05 01:04`; visible explanation `All times are UTC+6.` |
| Admin invoices | Same customer detail, after client data loaded | Four invoices displayed Nov 6, Nov 5, Nov 5, Nov 4, newest first, from the four UTC boundary fixtures. Visible captions `Dates use UTC+6` and `Dates and date filters use Asia/Dhaka.` |
| Offboarding list | `/admin/offboarding/` | Received `2026-11-05 01:04`, header and date filters explicitly UTC+6. |
| Offboarding detail | Opened fixture's View record link | Occurred, Received, and admin notification each `2026-11-05 01:04:55` with UTC+6 labels. |

Email boundary fixtures were `2026-11-04T17:59:59.999Z`, `2026-11-04T18:00:00.000Z`, `2026-11-05T17:59:59.999Z`, and `2026-11-05T18:00:00.000Z`. Thus the November 5 filter includes precisely the local day `[2026-11-04T18:00:00Z, 2026-11-05T18:00:00Z)`. Invoice fixtures use equivalent whole-second boundaries. The focused automated database suite additionally passed these filters under UTC and America/New_York database session settings.

Evidence: [November 5 email filter](evidence/local-admin-email-filter-nov5.txt), [November 4 email filter](evidence/local-admin-email-filter-nov4.txt), [email detail](evidence/local-admin-email-detail.txt), [subscribers](evidence/local-admin-subscribers.txt), [users](evidence/local-admin-users.txt), [account/domains/invoices](evidence/local-admin-user-detail.txt), [account interactive snapshot](evidence/local-admin-user-detail-snapshot.txt), [offboarding list](evidence/local-admin-offboarding.txt), [offboarding detail](evidence/local-admin-offboarding-detail.txt).

Tool limitation: `agent-browser` 0.27.3 screenshots hung and locked its browser daemon in both independent admin and customer sessions. Only the owned blocked processes were stopped; the admin session was reopened and authenticated. Subsequent checks used actual browser snapshots and rendered text. These observations establish content and calendar correctness, not visual layout approval. The parent agent is capturing visual evidence through the available browser fallback. Native date-field fill was also unreliable in this CLI (reported success while clearing the field), so the email filter checks above navigated the filter URLs and inspected actual browser results; native date interaction is left to that fallback. No product issue is inferred from these tool limitations.

Fixture-only repairs made before these passing checks: root-only private `products/` exclusion during cloning, dependency copy instead of a node_modules symlink to avoid duplicate Next request scopes, and complete fake Resend preference catalogs. None required a production source change. Browser `errors` returned no errors after the corrected fixture server was running. No resends, deletes, impersonation, or account edits were performed in the admin session.

## Follow-up after the customer saved America/New_York

Authenticated again in a fresh local admin browser session after the customer saved `America/New_York` through the actual settings UI. The customer admin detail still showed created/domain activity `2026-11-05 01:04`, UTC+6 labels, and invoice dates Nov 6 / Nov 5 / Nov 5 / Nov 4 with the explicit Asia/Dhaka caption. This confirms that admin display does not inherit either the admin's UTC preference or the customer's New York preference. See [fresh admin browser evidence](evidence/local-admin-user-detail-customer-new-york.txt).

Compared database values against the original pre-test fixture snapshot with strict assertions: user creation instants and every seeded domain, subscriber, and email timestamp remained identical; admin preference remained UTC, customer preference was America/New_York, and the database session timezone was UTC. The customer profile's legitimate update time was `2026-11-04T20:19:23.277Z`.

A separate disposable customer HTTP login then read the authenticated products, invoices, license, and manage APIs. All four returned HTTP 200. Every returned timestamp matched its original UTC fixture instant: provider timestamps kept their established UTC `YYYY-MM-DD HH:mm:ss` representation; domain timestamps kept UTC ISO `Z` representation. This supplementary API/storage verification did not replace the actual browser tests. Credentials, session cookies, license keys, and complete API bodies were excluded from evidence, and the temporary HTTP session was signed out. See [redacted API and database assertions](evidence/local-api-db-utc-after-new-york.json).

The parent agent separately verified native invoice date controls using keyboard commit after filling. Its visual check identified clipped year text in the narrow invoice filter row; the shared invoice filter width was corrected by the parent before final build verification.
