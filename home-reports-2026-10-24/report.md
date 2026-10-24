# Home report consolidation results

Completed 2026-10-24, Asia/Dhaka, against the real LocalWP site at http://localhost:10013.

Reports navigation, route, page, catalog and stylesheet are removed. Analytics Configure opens Home. Eleven new cards follow Action Center / Renewal Outlook and use Home's shared visual styles. Existing cards supply the other six destinations, covering all 17 original reports.

| Original destination | Home coverage |
| --- | --- |
| Subscription Performance | MRR history with Subscription performance link |
| Retention Analytics | Retention pulse |
| AI Churn Analysis | Churn risk |
| AI Revenue Forecast | Revenue forecast |
| Orders Analytics | New Orders Analytics summary |
| Revenue Analytics | New Revenue Analytics summary |
| Products Analytics | Top products by MRR |
| Variations Analytics | New Variations Analytics summary |
| Customers Analytics | New Customers Analytics summary |
| Orders | New order records and type summary |
| Subscriptions | Upcoming/recent subscriptions and status links |
| Member Insights | New member summary |
| Store Credit Balances | New balances summary |
| Credit Transaction History | New transaction summary |
| Activity Audit Trail | New recorded-actions summary |
| Gateway Health & Webhooks | New gateway readiness summary |
| Scheduled Job Logs | New execution-history summary |

All 17 destinations were opened from their Home links in real browser sessions. Report links support keyboard activation. Orders, Revenue and Variations Analytics open with the selected Home dates.

Data verification passed: four ranges (7d/30d/90d/month), direct WC order queries versus grouped counts, Analytics REST totals, credit getters/history and audit REST records. Twenty-three additional source/access comparisons passed. Actual browser range controls showed Orders Analytics counts 8 / 91 / 1,249 / 24 as expected, and Refresh retained all 11 summary cards. The selected 30-day data had 251 order records across statuses versus 91 Analytics orders; these are separately labeled. Lifetime customer/history metrics and current credit balances explicitly state their different time scopes. Credit currencies are kept separate.

A real future window with no sales rendered genuine zero totals. A request-local simulated failed WC customer source rendered an explicit error with no fake zero metrics, while the other cards remained available. Guest access to Overview was denied.

Screenshots were inspected at 1280px desktop, 834px tablet and 390px phone widths. No new card content or page overflow was observed. Cards expand to fill partial final rows. Store Credit alone, Audits alone, both disabled, and Pro unavailable were tested in the browser; related cards disappeared and the remaining layout remained usable. Core-only Home retained the member/gateway summaries and its ordinary free dashboard.

Original module switches and active plugins were restored and compared with the saved originals. The core-only check temporarily omitted Pro from the active plugin option without running activation/deactivation hooks, preserving scheduled jobs. No customer/order/subscription fixtures were changed. No unresolved defect from this change remains.

Production asset build passed. Diff whitespace checks passed, and every touched plugin source file remains below 3,000 lines. PHPCS and full lint suites were not run. Legacy order storage's empty branch was checked independently; this site uses HPOS, so populated legacy storage was not browser-tested.

Evidence and reproducible scoped scripts are in this directory's evidence/ and scripts/ folders. `browser-errors.txt` is empty. PHP source/data checks supplement the real browser tests; the failure injection is explicitly synthetic and request-local.
