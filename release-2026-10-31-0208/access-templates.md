# Access lane template and editor counts

27 discovered distinct page/editor templates; all 27 visited. Meaningful modes (automatic/manual shipping subscription, positive/empty Features, owner/other-customer/guest) are listed within templates and are not counted again. This list is for coordinator deduplication with other workers, not 27 additional global unique pages by assumption.

6 independent save editors discovered in this lane; 5 real-change save+reload tested (Manage Members Edit Details, Account details/custom profile field, Billing address, Shipping address, native Page including per-page restriction). Add payment method/token save is NOT-TESTED. The modal Apply only updates the Page draft, so it is not counted as another independent server save.

| ID | Distinct template | Verified depth |
| --- | --- | --- |
| MM-LIST | Manage Members list | interaction |
| MM-DETAIL | Manage Members customer detail | interaction |
| MM-EDIT | Manage Members Edit Details | save+reload |
| PAGE-EDIT | Native WP page editor | save+reload |
| RESTRICTION-MODAL | Per-page Access Restriction modal | save+reload through Page editor |
| WC-CUSTOMERS | WooCommerce Customers integration | interaction |
| AI-FORECAST | Revenue Forecast | interaction |
| AI-CHURN | Churn Analysis | interaction |
| RETENTION | Retention analytics | interaction |
| REFUNDS | Refund analytics | interaction |
| ACCOUNT-DASH | Customer dashboard | interaction |
| ACCOUNT-EDIT | Customer Account details | save+reload |
| ADDRESS-LIST | Customer addresses overview | render |
| BILLING-EDIT | Customer billing address editor | save+reload |
| SHIPPING-EDIT | Customer shipping address editor | save+reload |
| SUBS-LIST | Customer subscription cards | interaction |
| SUB-DETAIL | Customer subscription detail (automatic paid + manual physical-box modes) | interaction/render |
| ORDER-DETAIL | Customer order detail | interaction |
| FEATURES | My Features (empty + positive admin-owned modes) | render |
| CREDIT | Customer Store Credit | interaction |
| DOWNLOADS | Customer Downloads empty state | render |
| PAYMENT-METHODS | Customer Payment methods empty state | render |
| ADD-PAYMENT | Customer Add payment method | render only |
| ORDERS-LIST | Customer Orders | interaction |
| GUEST-LOGIN | Guest account login/register gate | render |
| PROTECTED-POST | Protected native Post | end-to-end; canonical FAIL, fresh allow/deny PASS |
| PROTECTED-PAGE | Run-owned protected WP Page | save+reload + fresh allow/deny; canonical FAIL |

## Essential lane assertions

- Saved a run-owned page restriction to active product 109433 and verified persistence after reload. Fresh URLs allowed A 3252 and denied B 2595 and guest. A guest request to the exact member URL did not leak the body.
- Existing Daily Stripe member 595 / subscription 69245 passed the same fresh-URL positive case. Both canonical protected pages denied eligible members, recorded as issue #150.
- A 3252 could view owned subscription 109453 and order 109452. B received clear permission/invalid-order errors without private details or actions; guest received a login gate.
- Four existing customer fields were restored exactly. Run-owned Page 109473 was trashed and the UI confirmed it. This lane made no global, lifecycle or financial changes.
- Account details at 390 × 844 and keyboard focus on Save changes passed. The subscription Related Orders mobile layout failed, matching closed issue #146 on the live deployment.
- AI forecast/churn generation was blocked by an installed but inactive provider. No provider activation, credentials or external generation was attempted.
- Retention reports displayed three lifecycle events blocked by an integrity check. Historical refunds transparently displayed eleven unallocated order-level refunds; this lane did not repair or replay data.

## Remaining gaps

- Add payment method token creation/save, ACH and payment-method switching.
- Protected downloads and private invoice actions: no suitable fixture or action was exposed. Downloads empty-state rendering passed.
- Customer-facing REST feature privacy remains untested. Commerce later saved a tagged feature on owned product 109433 and verified ordinary A 3252 positive My Features values for subscriptions 109453/109511, with no type labels or type-derived classes/data attributes. Existing admin-owned QA positives 33298/35313/35323 also passed.
- Direct gating of custom/hidden account pages was not exercised in this lane. The premium builder worker owns configuration coverage.
- Alternative gateway and translation-provider compatibility, external provisioning and AI written output.

## Session handoff

Run-owned sessions: rqa0208-access-admin (two tabs), rqa0208-customer-b, rqa0208-entitled, rqa0208-owner and rqa0208-guest. No mutation is pending. The coordinator may reuse them for profile C and close them at cleanup.

Private reusable states outside the shared QA artifacts: /tmp/rqa0208-customer-b-state.json and /tmp/rqa0208-entitled-state.json, both mode 0600. Do not include them in the report.

All five access-owned sessions closed successfully at coordinator request after handoff; no pending browser operation or mutation.
