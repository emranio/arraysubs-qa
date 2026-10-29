# Customer portal PDF invoice reproduction

Result: reproduced twice on 2026-10-29, Asia/Dhaka. An existing invoice causes the subscription detail endpoint to return HTTP 500. The same customer's normal WooCommerce Orders page displays the invoice action and serves a valid PDF. This investigation made no product source changes.

## Environment and fixtures

The user explicitly requested installing the invoice plugin. Installed and activated the official **PDF Invoices & Packing Slips for WooCommerce**, author **WP Overnight**, WordPress.org slug `woocommerce-pdf-invoices-packing-slips`, version **5.16.6**. The plugin header and `wp plugin get` both verify the identity. Source: https://wordpress.org/plugins/woocommerce-pdf-invoices-packing-slips/ . No similarly named alternative was installed.

Actual local WordPress site: http://localhost:10013 . ArraySubs **2.1.0**, ArraySubs Pro **1.3.0**, WooCommerce **11.1.2**, PHP **8.2.27**. Browser automation used isolated agent-browser sessions and the real site. WP-CLI used the existing Local PHP 8.2.27 runtime/configuration and `--allow-root`. Both ArraySubs plugins were active; a separate core-only activation test was outside this reproduction.

Disposable customer **479**, login `qa_pdf_invoice_20261029`, email `qa.pdf.invoice.20261029@example.test`, role `customer`. Hidden virtual product **23964**, pending order **23966**, pending subscription **23967**. Complete fixture identities and routes are in `fixtures.json`. Setup uses `arraysubs_create_subscription()` and `arraysubs_link_subscription_to_parent_order()`. The subscription has no next payment date. No payment or checkout was performed and no existing customer's order was modified. Mail was short-circuited for the setup request.

The local store initially had WooCommerce Coming Soon enabled. Temporarily changed `woocommerce_coming_soon` from `yes` to `no` so the isolated customer could view the actual portal. Restored it to `yes` after testing. The requested invoice plugin remains active. Disposable fixtures remain available for a future fix/retest; their generated customer password is in a mode-0600 temporary file outside the repository. No credentials are recorded in this report.

## Browser reproduction and control

Active QA task **2**, scheduled day **2026-10-29**, plan `qa/pdf-invoice-portal-2026-10-29/plan.md`. The fresh execution board is `kanban/` in this directory. Existing issue records were preserved; confirmed defect is **qa/issues #140**, left open because this request was reproduction only.

| Step | Exact route/action | Observed result | Evidence |
| --- | --- | --- | --- |
| 1 | Log in through `/wp-login.php` as fixture customer 479; open `/my-account/as-view-subscription/23967/?qa_pdf_phase=baseline` | Subscription and related order render. Actions are View and Pay. No invoice exists. | `evidence/02-subscription-no-invoice.png`, `.txt`; `runtime-no-invoice.json` |
| 2 | WP-CLI `eval-file qa/pdf-invoice-portal-2026-10-29/fixture.php generate --allow-root` | Official `wcpdf_get_document('invoice', $order, true)` initializes and saves the invoice. `exists()` and `is_allowed_in_my_account('available')` become true. | `evidence/runtime-invoice-created.json` |
| 3 | Open `/my-account/as-view-subscription/23967/?qa_pdf_phase=existing-invoice` | HTTP **500**. Page stops at Related Orders, before its order row. WordPress displays “There has been a critical error on this website.” | `evidence/03-subscription-existing-invoice-fatal.png`, `.txt`; `php-fatal-log.txt` |
| 4 | Open `/my-account/orders/?qa_pdf_phase=control` with the same logged-in customer | Normal WooCommerce table shows Pay, View, Cancel and **Invoice** for order 23966. | `evidence/04-woocommerce-orders-invoice-visible.png`, `.txt` |
| 5 | Click the actual Invoice action | New browser tab opens the official invoice endpoint. HTTP **200**, `application/pdf`; the fetched response is **229192 bytes**, starts `%PDF-1.7`. | `evidence/pdf-browser-response.txt`, `pdf-file-response.json` |
| 6 | Open `/my-account/as-view-subscription/23967/?qa_pdf_phase=repeat` | HTTP **500** again, title `WordPress › Error`, critical-error text present. | `evidence/repeat-browser-response.txt` |

Screenshots were inspected. `01-subscription-no-invoice.*` captures the earlier Coming Soon screen before the baseline reload; it is not the baseline evidence. `05-woocommerce-invoice-opens.png` is a blank native PDF viewer capture in headless Chrome; PDF delivery is established by the real action, browser response MIME/status and fetched file signature, not that screenshot. No claim about PDF layout is based on the blank capture.

## Confirmed cause

At `arraysubs/src/Features/CustomerPortal/views/view-subscription.php:797–800`, ArraySubs checks `function_exists('wcpdf_get_invoice')`, retrieves the invoice and, only when `exists()` is true, calls:

```php
$arraysubs_invoice->get_endpoint_url('download');
```

The installed `WPO\IPS\Documents\Invoice` has no callable method with that name. The fatal log at **2026-10-29 06:57:08 UTC** identifies that exact call at line **800**. The reporter's line 817 differs with source revision; the reported failure and method match.

The released plugin still provides `wcpdf_get_invoice()`, but deprecates it since 4.6.3 in favor of `wcpdf_get_document('invoice', $order, $init)`. That deprecated getter still returns the document in the tested release. It is distinct from the undefined URL method causing the 500. The no-invoice row avoids the fatal because `exists()` is false, so it never reaches the invalid method. The plugin's default customer setting only shows invoices already created/emailed; absent Invoice in that no-invoice state is expected.

WP Overnight's own `includes/Frontend.php::my_account_invoice_actions()` uses `wcpdf_get_document()`, checks customer availability and obtains a URL from `WPO_WCPDF()->endpoint->get_document_link($order, 'invoice', ['my-account' => 'true'])`. It registers with WooCommerce's standard order-actions filter. ArraySubs builds these related-order buttons itself instead of obtaining that standard action set. Changing only the document getter would leave the undefined URL call and therefore would not fix the fatal.

Corrective direction for a separate fix: use the supported document getter and endpoint or standard WooCommerce invoice-action integration, preserve customer availability checks and existing renewal payment safeguards, and repeat these browser cases. No premium-specific PDF integration was found in the source search; the failing code belongs to core.

## Final state

`evidence/restored-state.json` confirms Coming Soon restored and the requested PDF plugin plus both ArraySubs plugins active. Root product repositories have no modified source files. No changelog entry is appropriate for a reproduction-only investigation. No phpcs, Composer installation, build, release, deployment or external message was performed. Isolated browser sessions were closed after evidence collection.
