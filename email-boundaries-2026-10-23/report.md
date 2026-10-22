# Email audit and fixes — 2026-10-23

Fresh targeted plan: [plan](plan.md), QA91, immediate local run on 2026-10-23 Asia/Dhaka. User explicitly requested proper testing and fixes for all findings. Core and Pro were reviewed together; no older QA cycle or watcher was reused.

## Completed product fixes

- **Customer data isolation:** shared subscription email population clears the related order and order/payment placeholders on every trigger. Reused WooCommerce email instances cannot carry another customer's order/payment link into a notification with a missing/deleted order. Derived admin emails use the same reset.
- **Real send results / issue92:** the manager and public email helper use the existing exact email-result observer. Disabled emails, missing recipients/records and false/throwing mail callbacks are not marked as sent. Pending-cancellation notices track successful customer/admin recipients independently, so retrying a failed sibling does not duplicate the successful one. Undo clears both date guards. Nested email context and existing payment-failure ledger behavior remain covered.
- **Correct core navigation:** all four admin notification types use the registered core subscription route. Actual email links open the real subscription/order; preview buttons open existing list pages rather than mock IDs0/12345.
- **Subscription amounts and plaintext:** recurring/trial/retention values use the stored subscription currency. A shared Free helper removes HTML markup and decodes entities for plaintext bodies; customer names, currency symbols and footers remain readable.
- **Preview context:** Reminder preview dates match their notice period, card previews include sample digits/expiry, and real missing card digits show N/A rather than an empty field. Shared display arguments supply sample subscription numbers, products and dates without looking up a fictitious record. WooCommerce's nonpersistent PreviewOrder is cloned only in preview mode with real ID0; order tables use coherent sample totals, and preview payment buttons use checkout rather than order-pay/0. Real send paths keep their actual context.
- **Pro credit notification delivery / issue93:** the four Store Credit classes consume WooCommerce notification actions, preserving cold/warm/queued delivery and avoiding premature raw-action sends when delivery is deferred.
- **Pro credit currencies / issue93:** notification amounts and placeholders follow the order/subscription/ledger currency. Remaining balance excludes another customer's subscription and incompatible currencies. Credit-expiry notifications consume their source context. Real zero amounts remain zero.
- **Pro credit previews/plaintext / issue93:** sample names/amounts are coherent; customer order buttons open Orders during preview, while actual notifications retain their order detail URLs. All four classes reuse the shared Free plaintext formatter.

Core25 email types remain available without Pro. Pro's four credit email types and their actions are absent when Store Credit is disabled; no Free email page depends on a Pro-only endpoint. Core retention email registration is intentionally shared and dormant without its Pro event processor. Pro-only Woo order-item email enhancements remain owned by Cart Info Editor. [Pro audit details](pro-email-audit.md).

## Real runtime evidence

Actual local WordPress/WooCommerce was used, not a standalone template mock. All mail calls were intercepted using pre_wp_mail before external transport; controlled true/false results exercise Woo's mail-result observer. This verifies application rendering and transactional dispatch, not delivery to an external inbox.

| Suite | Free request | Pro active | Evidence |
| --- | --- | --- | --- |
| Preview/actual HTML/plain rendering, context isolation, currency and links |246/246|246/246|[Free](render-after-free.json), [Pro](render-after-pro.json), [source](render-regression.php) |
| Failure/retry/dedupe, helper results, nested triggers and payment-failure ledger |48/48|48/48|[Free](delivery-primary-free.json), [Pro](delivery-primary-pro.json), [source](delivery-regression.php) |
| Deleted product/customer, stored-email fallback and missing recipients |398/398|398/398|[Free](missing-records-free.json), [Pro](missing-records-pro.json), [source](missing-records-regression.php) |
| Modules disabled before provider boot |70/70 with actual Pro active and request-only module filter|same run|[Result](module-off.json), [source](module-off-regression.php) |
| Pro notification/preview/plain behavior and actual order links |N/A|29/29|[Result](pro-credit-primary.json), [source](pro-credit-regression.php) |
| Final reminder/card sample fields and real card metadata |7/7|7/7|[Free](preview-fields-free.json), [Pro](preview-fields-pro.json), [source](preview-fields-regression.php) |
| Pro credit GBP source/balance isolation |N/A|16/16|[Result](pro-credit-currency-primary.json), [source](pro-credit-currency-regression.php) |

Additional before/after evidence verifies actual Woo cold initialization and queued notification dispatch using a request-local mailer reset/provider registration with Pro skipped for that probe; this is distinct from full active-Pro testing. No persistent queue entry or background SMTP was used. See pro-credit-isolated-before.json and pro-credit-worktree-after.json.

The original render harness was refined to respect the site's currency-symbol position and to inspect decoded visible text for #0 (an HTML entity such as &#036; is not a zero subscription). Concrete defects are supported by rendered messages, source paths and targeted before/after results; the initial aggregate failure count is not used as a bug count.

## Browser acceptance

The actual WooCommerce Email settings/preview UI was driven with agent-browser. All29 registered Pro-active email preview types loaded without PHP/browser errors. Final sample subscription/order/product/credit values and customer/admin/payment destinations were checked. The final reminder/card check confirms the three-day notice matches its site-local date and card digits/expiry are populated; real card metadata and absent-card fallback pass seven focused runtime checks in both Free and Pro. Only a safe preview subscription-list link was clicked; the actual core All Subscriptions page rendered. No mock record link, test-email sending control or settings save was used. Screenshots, DOM observations and sanitized request evidence: [browser report](browser/report.md).

The fresh Free-only native Emails screen showed25core/0Pro previews; New Subscription and Renewal Invoice previews passed. A fresh Pro document restored25core+4credit types. [Final state comparison](browser/state-last-date-comparison.json) passes all7 checks: exact original8-plugin activation order, Coming Soon=yes, module states, complete ArraySubs settings hash, menu/profile hashes and all51Woo email-option hashes. Final network summary records zero send-preview/settings-save/save-transient calls; browser errors are empty and the isolated session is closed.

## State and cleanup

Every runtime fixture user, draft product, subscription, credit log and unpaid manual order belongs solely to this QA task and is deleted in finally cleanup. Individual JSON results record fixture IDs/deletion and exact saved-settings/active-plugin comparisons. No existing lifecycle, payment, account, address or credit balance was mutated. Runtime filters and mailer resets last only for their CLI process. No external message was sent.

Changes are committed on the local core/Pro main branches. PHP/template-only changes require no frontend asset rebuild. No PHPCS/lint/Composer installation or version bump was performed. All touched product source files are below3000lines; [source counts](source-line-counts.json). Canonical per-plugin changelog drafts contain the completed fixes and preserve existing entries. Issues91–93 are complete. Browser restoration is exact; runtime fixture cleanup passed. The separate WooCommerce Cart bootstrap issue90 from the earlier storefront audit is unrelated to these email changes and remains open.
