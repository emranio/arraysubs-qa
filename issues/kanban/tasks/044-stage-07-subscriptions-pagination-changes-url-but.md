---
id: 44
title: 'stage-07: Subscriptions pagination changes URL but keeps page 1 rows'
status: closed
priority: high
created: 2026-05-20T14:35:04.210422098+02:00
updated: 2026-05-22T03:00:29.282377852+02:00
started: 2026-05-22T02:56:45.106054447+02:00
completed: 2026-05-22T03:00:29.28237683+02:00
tags:
    - qa
    - stage-07
    - customer-portal
claimed_by: mold-glade
claimed_at: 2026-05-22T03:00:29.282377741+02:00
class: standard
---

Stage 07 Task 01.5. As cust1 on My Account > Subscriptions with 12 subscriptions, pagination shows Page 2 and Next. Clicking Next changes URL to https://mirror-help.arrayhash.com/?page_id=9&subscriptions/page/2/&subscriptions, but visible rows remain the same page-1 rows (#678, #673, #668, #663, #658, #653, #648, #643, #638, #633). Summary remains Showing 1-10 of 12 subscriptions. Expected: page 2 should show the remaining 2 rows and summary Showing 11-12 of 12 subscriptions.

[[2026-05-22]] Fri 02:57
Plan: inspect Stage 07 Task 01 and portal pagination implementation, reproduce with cust1, then patch endpoint pagination. Root cause in code: MyAccountHooks reads get_query_var('paged'), but Woo My Account endpoints pass page number through the endpoint value (subscriptions) on this site. Template also builds page links as subscriptions/page/%#%/, which is malformed for plain permalink/query-string My Account URLs. Fix: resolve current page from subscriptions endpoint value with paged fallback, and generate paginate_links from wc_get_endpoint_url('subscriptions', BIG_INT, myaccount_permalink). Then build assets only if needed (PHP-only likely no build), browser verify page 2 shows remaining rows and summary 11-13 of 13, previous returns page 1.

[[2026-05-22]] Fri 03:00
Fixed. Code: arraysubs/src/Features/CustomerPortal/Services/MyAccountHooks.php resolves page from Woo endpoint query var subscriptions; arraysubs/src/Features/CustomerPortal/views/my-account-subscriptions.php now builds paginate_links base with wc_get_endpoint_url('subscriptions', BIG_INT, myaccount). Browser QA with cust1@test.local: page 1 URL ?page_id=9&subscriptions shows 10 rows and summary Showing 1-10 of 13; Next URL ?page_id=9&subscriptions=2 shows only remaining 3 rows (#633, #306, #271) and summary Showing 11-13 of 13; page 1 link returns first-page rows.
