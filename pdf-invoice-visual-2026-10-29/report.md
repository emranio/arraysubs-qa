# Invoice visual verification completed

Completed 2026-10-29, Asia/Dhaka. Screenshots, invoice-button interaction and native PDF viewing now pass **with ArraySubs Pro active and with core alone**. The mobile overflow discovered during this visual pass is fixed in core main commit `23c9df3` (QA issue #146). Capture-tooling issue #145 is resolved for this QA workflow through the saved supported launch configuration.

## Simple product fix and translations

Only six markup lines change in the related-orders table: WooCommerce's existing `shop_table_responsive` class and five `data-title` attributes using `esc_attr_e()` with the existing ArraySubs strings. No new stylesheet, JavaScript, helper layer, translation registry or catalog regeneration was needed. The core canonical draft has one appended entry; the earlier draft content is preserved.

The native WP Overnight action still supplies its own translated/custom label. Installed its official WordPress.org Spanish language pack, version5.16.6, because this test site's plugin pack was missing. The existing ArraySubs Spanish catalog already contains every table label. The site's default language was not changed.

Actual Spanish output, in both Pro states: **Pedido / Fecha / Estado / Total / Acciones** and **Ver / Factura / Pagar**. Tested through the configured `/es/` TranslatePress route, without adding translation code.

## Environment and cases

Actual local site http://localhost:10013, ArraySubs2.1.0, ArraySubs Pro1.3.0, WooCommerce11.1.2, WP Overnight PDF Invoices & Packing Slips5.16.6, PHP8.2.27. Customer479, `qa_pdf_invoice_20261029`, `qa.pdf.invoice.20261029@example.test`, customer role. Subscription23967, order23966, product23964. No new orders, purchases or charges were needed. All browser interaction used agent-browser CLI with isolated profiles.

| Check | Pro active | Core alone |
| --- | --- | --- |
| Desktop1440x1000 | View / Invoice / Pay legible and aligned; visible focus; Enter opens new PDF tab | Same order/actions render correctly; desktop columns retained |
| Mobile390px | Table and Invoice fit the viewport; page width390 | Table and Invoice fit the viewport; page width390 |
| Spanish320px | Translated labels and actions fit; Invoice hit target is visible and uncovered | Same translated labels/actions and uncovered Invoice target |
| Native PDF open | Pointer opening and keyboard Enter both open the actual PDF endpoint; final viewer visibly shows the invoice | Clicking Factura from the narrow Spanish page opens the actual PDF; final viewer visibly shows the invoice |
| Download | Real portal action downloads a valid PDF | Real portal action downloads a valid PDF |
| Document appearance | Native viewer and Poppler-rendered page inspected: readable header, order23966, product, quantity1 and total25.00; no clipping/corrupt glyphs | Same invoice content and clean rendering |
| Browser errors | None recorded | None recorded |

The original mobile failure was visible with and without Pro: page width527 at a390px viewport, Invoice spanning x441..510 outside the screen. After the standard responsive markup, the390px English page remains390px wide and Invoice spans x187..256. At320px in Spanish, all actions remain inside the viewport; Pay wraps onto a second line naturally.

## Visually inspected final evidence

- Pro desktop: [normal](evidence/pro-desktop-final.png), [focused Invoice](evidence/pro-focus-final.png).
- Core desktop: [normal](evidence/core-desktop-final.png).
- Pro mobile: [390px English](evidence/pro-mobile-fixed.png), [320px Spanish with controls visible](evidence/pro-spanish-320-visible.png).
- Core mobile: [390px English](evidence/core-mobile-fixed.png), [320px Spanish with controls visible](evidence/core-spanish-320-visible-final.png).
- Native PDF viewer: [Pro](evidence/pro-pdf-final-open.png), [core](evidence/core-pdf-rendered.png). These show the fully rendered document, not merely an application/pdf response or blank viewer.
- Downloaded PDF renders: [Pro](evidence/pro-invoice-rendered.png), [core](evidence/core-invoice-rendered.png). Original PDFs are `evidence/pro-invoice.pdf` and `evidence/core-invoice.pdf`.

Each image above was opened and inspected. Intermediate PDF-loading screenshots and blank out-of-viewport element crops are retained for traceability and excluded from this sign-off. Final evidence uses full-page or viewport captures after explicitly scrolling the controls into view.

`evidence/verification.json` records20 passing checks against the actual browser geometry, Spanish output, unoccluded hit targets, native PDF responses, downloaded document signatures/content and empty browser error logs. The existing portal invoice regression also passes19 assertions. No new unit tests were added for the small markup change; the actual visual before/after is the relevant regression check.

## Screenshot recovery

`launch-browser` selects the existing Edge156 executable and enables the supported `CDPScreenshotNewSurface` compositor feature for a fresh isolated session. This setup captured all final product and native PDF screenshots without hanging. It changes no global CLI installation, shared managed browser profile or user browser preference.

The default Chrome146 capture failure was intermittent: fresh default captures later worked too, so a permanent upstream Chrome fix is not claimed. [Upstream issue1437](https://github.com/vercel-labs/agent-browser/issues/1437) reports matching capture/daemon symptoms, and [PR1189](https://github.com/vercel-labs/agent-browser/pull/1189) documents the compositor mitigation. The local cause match remains an inference. Independent diagnostics also passed12 Chrome captures across six navigations with the feature flag. Exact commands and untouched sample screenshots are in `screenshot-diagnosis.md` and `evidence/diagnostic-*.png`.

## Final state

`evidence/restoration-verification.json` confirms the original four option groups match exactly, ArraySubs Pro matches its initial active state, and WP Overnight remains active. Coming Soon is restored to yes. The official Spanish pack remains installed for ordinary vendor translations. No release version, released changelog or Pro product source was changed in this cycle. Source files remain below3000 lines. Owned browser sessions and the isolated implementation worktree were cleaned up after collection.

The prior functional report's visual limitation is superseded by this completed visual pass. Existing functional evidence remains at `qa/pdf-invoice-compatibility-2026-10-29/report.md`.
