# PDF invoice integration: fix and compatibility results

Completed 2026-10-29 (Asia/Dhaka). The original crash and four additional integration defects are fixed. Changes are merged into both local main checkouts: ArraySubs `0b53fc2`, ArraySubs Pro `126f590`. No version bump, release, push or remote deployment was performed.

## Environment and scope

Real local WordPress at http://localhost:10013, PHP 8.2.27, WooCommerce 11.1.2, ArraySubs 2.1.0 and Pro 1.3.0. The official **WP Overnight PDF Invoices & Packing Slips for WooCommerce 5.16.6**, slug `woocommerce-pdf-invoices-packing-slips`, remains installed and active as requested. Testing used the prebuilt runtime/assets, WP-CLI with `--allow-root`, actual WooCommerce orders, genuine core renewal construction, actual Pro store credit, real local SMTP/Mailpit delivery and isolated agent-browser customer/admin/guest sessions. No gateway charge was run.

Fresh plan: `plan.md`; execution board: `kanban/`. Original reproduction evidence remains under `qa/pdf-invoice-portal-2026-10-29/`. Existing unrelated QA issues were preserved.

Primary fixture customer **479**, `qa_pdf_invoice_20261029`, `qa.pdf.invoice.20261029@example.test`, role customer; second customer **480**, `qa_pdf_other_20261029`, role customer. Product **23964**. Original subscription/order **23967/23966**; multi-order subscription **23976**, original order **23975**; paid fixture subscription/order **23981/23979**; core-created renewal **23984**. Further exact IDs are in `fixtures.json` and each ordering probe record. Only disposable test records were intentionally modified; all test credit came from explicit grants to that fixture and both final balances are zero.

## Fixed defects

| QA issue | Confirmed problem | Result |
| --- | --- | --- |
| #140 | An existing invoice reached undefined `Invoice::get_endpoint_url()` and crashed subscription details with HTTP 500. | Portal consumes supported WooCommerce invoice actions provided by WP Overnight, which owns modern document lookup and URL generation. Existing invoice loads with HTTP 200 and downloads a valid PDF. |
| #141 | Core subscription emails and Pro Store Credit Used omitted `WC_Email::$object`, so selected WPO PDF attachments received no order. | A freshly verified, related order reaches standard attachment/header/subject hooks. Stale, deleted, foreign and invalid contexts are cleared. Verification emails cannot restore a rejected foreign order. Actual core and Pro messages contain PDFs. |
| #142 | Portal hardcoded existence, label and target, ignoring Always/Never/status policies and download preferences. | Native invoice policies, custom labels, PDF/XML actions, endpoint settings and target behavior are preserved. Existing View/Pay and settlement behavior remain separate. |
| #143 | Fresh renewals lost WP Overnight customer identification/VAT metadata. | Canonically verified original-order identification is copied through WooCommerce metadata APIs on the existing renewal-built hook. Only the three upstream customer-identity keys are copied; existing target overrides and invoice identity remain independent. |
| #144 | Invoice email ran before Pro credit deductions, sending an attached PDF for 25.00 when the final order was 20.00. | Invoice mail runs after coupons, credit and carried checkout fields. A fresh order must remain payable; paid, zero-due, invalid and duplicate invoices are suppressed. |

All product changes received independent source review. No third-party plugin files were edited. The alternative invoice-provider fallback now verifies a callable static API and passes a numeric order ID; its real plugin is not installed, so that path has regression coverage rather than a claimed end-to-end run.

## Actual browser and delivery checks

| Case | Observation/evidence |
| --- | --- |
| Existing invoice, core + Pro | HTTP 200; View, Invoice, Pay remain. `available-existing.json`; actual `portal-invoice-download.pdf`. |
| Existing invoice, core only | Deactivated Pro through WP-CLI, then opened the real customer page. HTTP 200 with invoice action. `core-only.json`. Pro restored. |
| PDF plugin inactive | HTTP 200 with View/Pay and no Invoice. `pdf-inactive.json`. PDF plugin restored. |
| Available, absent invoice | No invoice button and no document creation. `available-absent.json`. |
| Always, absent invoice | Invoice action present without initializing an invoice. `always-absent.json`. |
| Status policy | Pending allowed; pending denied under Completed-only; completed order allowed without Pay. `custom-pending.json`, `custom-completed-denied.json`, `custom-completed-paid.json`. |
| Never / document disabled | Existing invoice hidden, page and native actions preserved. `never-existing.json`, `disabled-existing.json`. |
| Download vs display | Download leaves target empty; display lets WPO set `_blank` and `noopener noreferrer`. `download-mode.json`, `display-mode.json`. Actual download succeeds. |
| Provider custom label | Literal `QA <Invoice> & bill` is preserved as escaped text. `custom-label.json`. |
| Provider exception | Controlled scoped provider failure is caught; page stays HTTP 200 with View/Pay and a diagnostic log. `provider-failure.json`. Temporary probe MU-plugin removed. |
| Settlement protection | Real fixture order settlement metadata produces “Payment in progress”, View/Invoice and no Pay or Cancel. `settling-renewal.json`. Synthetic metadata cleared afterward. |
| Pretty links | Native `/wcpdf/invoice/.../pdf` action renders and downloads. `pretty-links.json`, `pretty-invoice-download.pdf`. Rewrite setting restored. |
| No list-side creation | `pre-list-state.json` and `post-list-state.json` both show order 23975 has no invoice or number after the availability matrix. |
| First and repeated download | Clicking the actual portal action creates invoice number 7 for order 23975; repeating it preserves number 7. Both downloads are valid PDFs. `after-first-download-state.json`, `after-second-download-state.json`, `new-invoice-download.pdf`, `repeated-invoice-download.pdf`. |
| Guest access | Protected subscription displays login; replayed invoice URL returns permission text and no PDF. `guest-subscription.txt`, `guest-invoice.json`. |
| Other customer access | Customer 480 cannot view customer 479's subscription. A valid nonce from customer 480's own order 24027, with only order ID replaced by 23966, is also denied. `foreign-subscription.txt`, `foreign-invoice.json`. WPO returns HTML permission errors with HTTP 200; the test asserts denied content rather than assuming HTTP 403. |
| Actual attachment failure/fix | Original core message had no attachments; corrected message contains `%PDF-1.7`. `email-send-before.json`, `email-send-after.json`; actual SMTP messages in `mail-final-messages.json`. |
| Actual credited renewal | Active subscription: original listener sent order 24017 email/PDF total 25 while final total was 20; fixed listener sends order 24022 email/PDF total 20, matching the final order. `renewal-email-ordering-before-active.json`, `renewal-email-ordering-after-active.json`, attached PDFs and extracted PDF text. |
| Pro attachment | Actual Store Credit Used message for order 24022 has one PDF attachment. Browser Mailpit view shows order 24022, credit 5 and invoice filename. `pro-mail-browser-snapshot.txt`; message `hVyCennGsD6nH86g5U6hW3`. |
| Email browser content | Mailpit view for the actual corrected renewal displays order 24022, amount 20.00 and `invoice-24022.pdf`. `mail-after-browser-snapshot.txt`; message `bdNBnqBidLSpfiCu8jJMcj`. |
| Repeat real renewal event | Re-dispatching the same real event for order 24022 sends no duplicate invoice. `mail-duplicate-check.json`. |
| Customer identification | Original order 23975 shows QA Customer VAT; old renewal 23988 lacks it; new renewal 24009 shows the retained value. `vat-parent-text.txt`, `vat-renewal-before-text.txt`, `vat-renewal-after-text.txt`, `vat-fixed-runtime.json`. |

The initial `after` ordering probe used a pending subscription and correctly sent no payable renewal request because the existing lifecycle guard denies pending-subscription renewals. The valid **active** before/after pair above establishes the monetary fix. That protection was retained. Fixture status was restored to pending after testing.

## Regression checks

On the merged main checkouts with PHP 8.2.27:

- Portal invoice adapter: **19** assertions.
- Core email order context and verification isolation: **74** assertions across 25 core classes.
- Renewal customer metadata: **36** assertions.
- Invoice event ordering with installed Pro registration priorities: **24** assertions; core-only standalone variant also passes.
- Email locale and merchant overrides: **559** assertions across 29 core/Pro classes.
- Pro credit email order context: **28** assertions.
- Existing lifecycle and all core/Pro email outcome contract script passes. Its stale harness was repaired with the missing shared trait include and optional Pro checkout path, preserving original assertions.

Total: **740 numbered regression assertions**, plus lifecycle/outcome contracts. `python3 qa/pdf-invoice-compatibility-2026-10-29/verify-results.py` passes **94 assertions against saved actual browser/mail/runtime observations**. These are evidence checks across the cases above, not 94 separate user journeys.

No phpcs, PHP lint, Composer installation or build was run. PHP source changes require no frontend asset rebuild. Every touched product file is below 3000 lines. Both existing canonical changelog drafts were read, appended in the correct plugin, and verified to preserve all original bytes as a prefix.

## Limits and final state

Chrome `Page.captureScreenshot` began timing out even on a blank page. Diagnostics found no installation failure; retries in isolated headless/headed sessions and the connected agent-browser backend also failed. Existing baseline screenshots are retained. Functional testing continued through the real browser with accessibility snapshots, DOM observations, actual clicks/downloads and actual Mailpit messages. No new visual screenshot sign-off is claimed.

Paid WPO extensions, external Peppol delivery, custom invoice templates and the optional XML/EDI download were unavailable and were not end-to-end tested. XML action preservation and the exact Peppol metadata keys have focused regression coverage. The standard installed free plugin, genuine PDF generation and download paths were exercised.

`restoration-verification.json` confirms all four changed option groups exactly match their originals, both ArraySubs plugins and WPO remain active, and both fixture credit balances are zero. Coming Soon is restored to yes. Temporary failure/label probes and synthetic settlement metadata are removed. All three fixture subscriptions have had their task-specific scheduled actions cleared through the centralized scheduler helper. Identified disposable fixtures and evidence remain for repeat verification. No release metadata or released changelog was changed.
