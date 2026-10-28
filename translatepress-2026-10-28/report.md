# TranslatePress integration and direct settings translation QA

Date: 2026-10-28
Site: http://localhost:10013
Plan: [plan.md](plan.md)
Implementation issues: #128–134 in `qa/issues/`

## Result

> Superseded on 2026-10-28 (same day): the Settings → Translations screen described below was replaced by the per-field translate icon. See the Follow-up section at the end; the evidence above it is historical.

Completed the scoped English/Spanish cycle. TranslatePress 3.3.8 is installed and active with English (`en_US`) as the source and Spanish (`es_ES`) as the target. Automatic translation is disabled. Loco Translate remains active. WPML was explicitly excluded by the user.

ArraySubs now provides **Settings → Translations** for saved customer-facing text. The screen uses TranslatePress's native dictionaries and configured languages. It preserves original settings, offers searchable groups and optional product selection, and validates runtime variables before saving. It includes registered core and premium configuration sources, rather than scanning arbitrary database values or customer data.

Open: http://localhost:10013/wp-admin/admin.php?page=arraysubs-mainadmin#/settings/translations

## Browser verification

Actual browser sessions used the local WordPress site and installed plugins. Screenshots are in [screenshots/](screenshots/).

| Case | Observed result | Evidence |
| --- | --- | --- |
| Saved custom subscribe label | English source remained unchanged; Spanish product URL displayed the manually translated button. | 01, 02, 07 |
| Subscription Box | Custom button, conditional step title, element labels, and review heading displayed Spanish. IDs and source configuration remained unchanged. | 08 |
| Subscription Bundle | Saved bundle subscribe button displayed its Spanish translation. | Browser observation |
| Checkout Builder, block and classic | Saved labels, whole optional labels, placeholders, help and choices translated; submitted choice values remained `morning`/`afternoon`. Safe HTML displayed formatted content. Block floating labels did not overlap custom placeholders after the fix. | 04, 05 before; 09 after |
| Native TranslatePress editor | Editor preview retained the English original and exposed the original/Spanish pair without replacing the source with an already translated string. | 10 |
| Cancellation dialog | Authenticated Spanish REST and the real customer dialog displayed the saved Spanish reason. English REST retained its original. Keep Subscription closed the dialog; no cancellation was submitted. | 06 |
| Retention template | Settings UI rejected a missing `{cycles}` variable, retained the draft, and displayed a persistent accessible error. The corrected template saved; native runtime translation rendered “Ahorra un 20% durante 2 ciclos de facturación.” while the canonical template stayed English. | 15, 17; runtime probe |
| Settings translation save | A label containing quotes and an ampersand saved in the new screen and appeared correctly on the storefront. Search remained selected after save. Source text was read-only. | 12, 13, 14 |
| Conditional product text | On-demand product search selected existing Box #5900; a step title was translated directly in settings and the storefront data attribute reflected it without visiting that condition in the visual editor. | 16 |
| Unsaved changes | Escape opened the shared confirmation. Keep editing retained the draft; Discard closed it. | Browser observation |
| Provider inactive | With TranslatePress temporarily disabled, the screen displayed activation guidance and an Installed plugins link. TranslatePress was restored. | 18 |
| Core without Pro | Core Box label and conditional attributes still translated with Pro temporarily excluded. No browser console errors. Pro was restored. | 11 |
| Email | Native `wp_mail` processing translated a custom subject and body. The final message was intercepted before delivery. | Captured mail probe |
| Restored site | New settings screen remained available after test source fixtures were removed. | 19 |

The settings screen also passed actual authorization/input checks: customer requests returned 403, source-language targets and invalid products returned 400, product search outside the supported page range returned 400, and a stale source fingerprint returned 409. Invalid saves did not change the stored source.

## Implementation coverage

Core owns the shared dynamic-text helpers, native TranslatePress dictionary adapter, allowlisted source registry, catalog, validation, permission-checked REST controller and admin UI. Pro contributes its saved settings sources and translates customer presentation in Checkout Builder and Cancellation Flow.

Regular server-rendered custom labels continue through native TranslatePress HTML processing. Targeted inline JavaScript and REST display fields now have explicit integration. Identifiers, prices, conditions, submitted choice values and editable source settings are not translated. Authored retention templates translate before runtime values are inserted. Editor previews expose original text.

Default/static plugin UI remains a Gettext catalog responsibility. Only saved custom values from registered sources appear in this screen; it does not import every database value or automatically translate the whole site.

## Automated validation and generated assets

All focused checks passed on the merged source:

| Check | Result |
| --- | --- |
| Core actual-provider dynamic translation contracts | 84 assertions |
| Core Box display attributes | 39 assertions |
| Core native dictionary/catalog contracts | 67 assertions |
| Pro source registry regression | 173 assertions |
| Pro retention template regression | 27 assertions |
| Pro dynamic translation regression | 53 assertions |
| Core settings UI/API Jest tests | 10 tests, 3 suites |
| Pro checkout DOM tests | 6 tests, 2 suites |
| Production builds | Core and Pro passed |
| Regenerated core POT | 7,375 messages; 77 JavaScript reference paths; zero missing files; version 2.1.0 |
| Regenerated Pro POT | 1,945 messages; 29 JavaScript reference paths; zero missing files; version 1.3.0 |

Total: **443 PHP assertions and 16 JavaScript tests**. Both changelog drafts contain the completed changes and preserve earlier entries. Every changed file was checked with `wc -l`; the largest touched source files were 818 lines in core and 1,693 in Pro, within the 3,000-line limit.

Final source commits: core `709d86d`, Pro `f90e3ba`. Both source repositories were clean after the checks.

## Fixture restoration

Verified restoration of the original three button-label values, store visibility, WooCommerce checkout page selection, selected checkout configuration, cancellation settings and retention templates. Removed disposable pages #23612/#23613, subscription #23617, customers #476/#477 and fixture actions. Existing products #5916, #5900 and #5905 were retained. Core, Pro, TranslatePress and Loco Translate are active.

No payment or order submission, final cancellation, offer acceptance, paid machine translation or external email delivery was performed. Valid Spanish dictionary entries remain available in TranslatePress. Temporary private state and credentials stayed outside Git.

## Scope limits

This cycle exercised Spanish with representative core and premium flows on the local site. It does not establish exhaustive coverage of every language, theme, third-party checkout extension or arbitrary custom database field. WPML was skipped at the user's instruction. Existing non-fatal Elementor log notices and duplicate POT translator-comment warnings were outside this change.

## Follow-up (2026-10-28, later the same day)

The dedicated Settings → Translations screen described above was removed at the user's request. Saved text is now translated in place: customer-facing text inputs, textareas and rich-text editors (opt-in per field; keys, URLs, prices, admin-only forms stay single-language) show a tiny translate icon that opens a small dialog listing every configured TranslatePress language (English value as placeholder). The Elementor restriction message gets a "Translate message" button control that opens the same dialog; the Gutenberg block and post restriction panels use the shared message field. Saves still go to the native TranslatePress dictionary through the same `TranslatePressDictionary` adapter and placeholder validation; the catalog, source registry, product lookup and Pro source collector were deleted. Storefront behaviour is unchanged: text is still translatable by clicking it in the TranslatePress visual editor.
