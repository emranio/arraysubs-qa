# Email settings and supported previews: browser verification

Active QA task: #91. Scheduled day: 2026-10-23 (Asia/Dhaka). Plan: `qa/email-boundaries-2026-10-23/plan.md`. Real local site `http://localhost:10013`, isolated `email-admin` browser session, authenticated through documented Local auto-login. No customer password was used.

## Scope and preservation

Pro remained active for the baseline and Pro preview checks; a final brief Free phase was coordinated after all other workers' CLI regressions completed, then exact activation was restored. Coming Soon remained `yes`. No module switch, email setting, subject/body field, account/profile, subscription, order, payment or lifecycle state was changed by this worker. No fixture was created for these previews. Neither **Save changes**, **Copy file to theme**, nor **Send a test email** was used. Supported previews render WooCommerce dummy/sample objects and cannot establish actual SMTP delivery or substitute for root's real disposable-fixture email checks.

The browser recorded zero requests to `/send-preview`, zero email-settings save requests and no page exceptions. Automatic admin POSTs were WordPress heartbeat/community events and WooCommerce's `woocommerce_clear_ces_tracks_queue_for_page` analytics queue clearing. Their sanitized action/body summary is `automatic-admin-post-summary.json`; nonce/header values were not retained. Full request inventory is `requests.txt`, with nonce query values redacted. Generic JQMigrate and Woo `useSelect` warnings are retained separately in `console.txt`.

## Completed browser coverage

| Check | Result and evidence |
|---|---|
| WooCommerce Emails list | `/wp-admin/admin.php?page=wc-settings&tab=email` shows 25 core ArraySubs classes and four Pro Store Credit classes, alongside the site's unrelated Woo/WPSubscription emails. `registered-preview-types.json`, `pro-email-list-snapshot.txt`, `pro-email-settings-full.png`. |
| Every registered ArraySubs preview | Selected all 29 supported types through the actual native preview dropdown. Waited for the matching iframe document to complete before capturing. All render nonempty HTML with no fatal/critical error or unresolved brace placeholder. Per-class `*Email-preview.json` records actual subject, visible body text, links and feature-type attribute scan. `preview-summary.json` summarizes limits below. |
| Core settings | Native New Subscription settings render enabled notification, standard subject/heading/additional-content/type fields and the existing placeholder list. Save changes remains disabled. `core-new-subscription-settings-snapshot.txt`, `core-new-subscription-settings-full.png`. |
| Core desktop/mobile preview | New Subscription displays sample #12345, sample product, 29.99 monthly price and dates. The account link is `/my-account/as-subscriptions/`. Mobile frame layout fits its width. `new-subscription-preview-snapshot.txt`, `core-new-subscription-mobile.png`. |
| Pro settings | Native Store Credit Added settings render standard email fields and preview; Save changes remains disabled. Store credit link is `/my-account/as-store-credit/`. `pro-credit-added-settings-snapshot.txt`, `pro-credit-added-settings-full.png`. |
| Feature-type privacy | No matching raw feature-type/type-derived class or data attribute was found in any of the 29 rendered iframe DOMs. No feature-type label/column appears in the inspected visible content. Per-class JSON contains the scan results. |

## Preview sample-data defect / limitation reported to root

Subscription/order IDs: N/A; previews use dummy/sample objects and no existing subscription/order was triggered. Customer/user: authenticated admin context, login `admin`, recipient UI `dev-email@wpengine.local`; no customer fixture involved.

Reproduction: open the Emails page, choose **[ArraySubs] Renewal Invoice** or **[ArraySubsPro] Store Credit Added** in the preview dropdown; wait for the selected iframe document to load. Expected: internally consistent, representative subscription/order/credit sample data. Actual: Renewal Invoice subject uses subscription #12345, but its body says subscription #0 and omits the order/payment detail table/link. Most other subscription previews similarly use #0 in body fields. Credit Added/Used/Expiring/Expired bodies use 0.00 amounts and generic **Hi there**; Used displays order #0, and Expiring displays zero days/blank expiry date. Constructor placeholder examples alone do not populate the classes' protected subscription/order/customer/credit properties used by their templates.

Proof: `RenewalInvoiceEmail-preview.json`, `renewal-invoice-preview-snapshot.txt`, `renewal-invoice-preview.png`, all four `Credit*Email-preview.json`, `pro-credit-added-preview.png`, `credit-expired-preview.png`. Counterexample: New Subscription displays its sample #12345/product/price consistently. This is a real preview data-seeding limitation; it is not evidence that emails triggered with actual fixture data contain zero values. Root was notified promptly and owns issue disposition/product fixes.

## Supported renderer/routes for root's harness

- `Automattic\WooCommerce\Internal\Admin\EmailPreview\EmailPreview::set_email_type($fully_qualified_email_class)` resolves the class registered in `WC()->mailer()->get_emails()`, seeds Woo dummy objects/placeholders, then `render()` renders the email. `woocommerce_prepare_email_for_preview` is the supported class-data preparation filter.
- The UI iframe uses nonce-protected GET `/wp-admin/?preview_woocommerce_mail=true&type=<FQCN>&_wpnonce=REDACTED`.
- Subject preview uses GET `/wp-json/wc-admin-email/settings/email/preview-subject?type=<FQCN>`.
- Live unsaved-style updates can use POST `/wp-json/wc-admin-email/settings/email/save-transient`; no field was edited and no such request was observed here.
- POST `/wp-json/wc-admin-email/settings/email/send-preview` sends mail and was never called.
- Native single settings routes observed: `section=arraysubs_new_subscription` and `section=arraysubspro_credit_added_email` on the Woo Emails settings URL.

The initial browser baseline covered Pro-active UI availability and rendering. Root owns request-local Free/module-off assertions and real fixture render/trigger checks; a later coordinated native Free browser phase is recorded below. The baseline isolated `email-admin` session was closed without a setting or customer/storefront state change.

## Product fixes: browser reverification

Root merged PHP fixes in both plugins. The isolated admin session was reopened, with Pro active and Coming Soon still `yes`. Every native preview type was selected again; `after/registered-preview-types.json` and per-class `after/*Email-preview.json` record all 29 actual iframe documents. `after/preview-summary.json` confirms nonempty output, no fatal error, no visible subscription/order #0, no unresolved brace placeholder, no matching feature-type attribute, no generic **Hi there** credit greeting, and no order-pay/0 link.

Desktop screenshots were visually inspected for New Subscription, Renewal Invoice, Payment Failed, Auto-Downgrade, Credit Added and Credit Used, saved as `after/<Class>-desktop.png`. Renewal Invoice now shows subscription #12345, Order #12345, Sample Subscription Product, 29.99 total and the due date. **Pay for this order** targets `/checkout-2/`. Payment Failed and Successful render sample product/amount and valid account/checkout links. Credit Added shows 25.00 credit/50.00 balance/source Admin adjustment; Used shows 20.00/30.00/order #12345; Expiring shows 30.00/7 days/October 30 date; Expired shows 15.00. All credit greetings show John Doe.

An intermediate preview link issue was reported promptly to root: three admin subscription links ended in ID `0`; the fourth used the mock ID `12345`; Payment Failed Admin's order label was #12345 but its Woo edit href ended `id=0`, and Credit Used targeted mock customer order #12345. The intermediate records remain in `after/preview-summary.json` for traceability. Root corrected these destinations while preserving sample display numbers and actual-email detail links.

Final targeted verification of four admin types, Credit Used and Invoice passes. All admin **View Subscription** preview links now target the valid core `page=arraysubs-mainadmin#/subscriptions` list; the admin sample-order link targets Woo's `page=wc-orders` list. Credit Used's order links target `/my-account/orders/`; Invoice's payment CTA remains `/checkout-2/`. No final href uses ID 0 or a mock #12345 destination. One real click on the safe admin list link opens **All Subscriptions**, showing existing rows without changing any record. Authoritative final link JSON/screenshots and summary are under `after/final-links/`; `admin-preview-link-destination.png` proves the destination.

## Free check and exact final restoration

After root explicitly confirmed concurrent CLI checks complete, only local Pro was deactivated. A fresh native Emails document contains exactly 25 ArraySubs core types and no Pro credit type. New Subscription and Renewal Invoice previews render sample #12345/product/29.99/date/payment link correctly under Free. Evidence is `free/registered-preview-types.json`, `free/email-settings-snapshot.txt`, `free/NewSubscriptionEmail.png/json`, and `free/RenewalInvoiceEmail.png/json`.

Pro was reactivated immediately. Its original exact eight-plugin activation array order was restored after verifying the current activation set matched the saved set. A new native Emails document again shows 25 core plus four Pro classes (`registered-types-restored-pro.json`). `state-before-free.json`, `state-final.json` and `state-comparison.json` confirm all seven comparisons true: exact activation/order, Coming Soon `yes`, modules, entire ArraySubs settings hash, saved menu hash, profile-fields hash and all 51 email-option hashes. No email configuration changed.

No Send test email, Save changes, or content field was used during any phase. `final-request-summary.json` confirms zero send-preview, settings-save or save-transient requests; `final-errors.json` contains no page exceptions. The isolated `email-admin` session is closed. No browser work or restoration remains pending.

## Final targeted date/card field check

After the final two PHP field fixes, only Renewal Reminder and Card Expiring were reopened in the native preview UI. Renewal Reminder now has the populated **Next Payment Date — November 22, 2026** row, with sample product/29.99 and account link. Card Expiring now shows **4242** and **11/2026** in both the explanatory paragraph and detail table. Screenshots, inline snapshots and DOM observations are in `after/final-fields/`. Both render without a page exception, no test email or settings-save action occurred, and activation remained unchanged.

The intermediate Reminder preview still paired **renew in 3 days** with a date 30 days away. This was reported to root and corrected immediately. The final targeted native refresh shows **renew in 3 days** and **Next Payment Date — October 26, 2026**, matching the shared formatter's expected three-day date from October 23 in the site's `+06:00` timezone. Both browser and independent read-only WordPress expected-date checks pass in `after/final-fields/RenewalReminderEmail-consistent-date.json`; the matching screenshot is authoritative over the earlier date screenshot. `consistent-date-errors.json` contains no page exceptions. Card #4242 / expiry 11/2026 proof remains unchanged.

`state-last-fields.json` / `state-last-fields-comparison.json` and the final `state-last-date.json` / `state-last-date-comparison.json` reconfirm all seven original state/configuration comparisons true, including all 51 email-option hashes. The targeted session was closed after evidence capture. No browser work or restoration remains pending.
