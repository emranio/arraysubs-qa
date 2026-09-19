# Required Orders and Subscriptions regression plan

Current user clarification, 2026-09-19: Orders and Subscriptions are mandatory in Free and Pro but may be renamed and reordered. Their custom labels apply to navigation and page headings. Only hiding/removal is prohibited. The earlier rename lock was incorrect and is superseded by RM-12. Other optional menus retain customization.

Added user requirement: prefix ArraySubs-owned and custom account URL slugs with as-. User explicitly confirmed WooCommerce Orders and other built-in tabs keep their existing URLs. Include as-subscriptions, as-view-subscription, as-store-credit, as-features, and automatic custom prefixes; test pretty/query routes and custom slug collision validation.

Target http://localhost:10003; admin via /wp-admin/?localwp_auto_login=1. Existing customer 525 / qa_download_settings_second_20260917 / customer role, subscription 28240 active, order N/A (_parent_order_id=0). Starting Pro 1.2.4 active and core 2.0.5. Use isolated browser sessions. Preserve all saved settings and subscriptions after testing.

Fresh same-day sequence / progress board:

| ID | Test | Status |
| --- | --- | --- |
| RM-01 | Required rows have drag handles only; optional rows retain controls | Initial pass superseded by RM-12: required rows must also allow renaming |
| RM-02 | Drag required rows, save/reopen, verify storefront ordering | PASS — Subscriptions moved first, saved/reopened, then restored |
| RM-03 | Old hidden required config remains enabled; custom names preserved | PASS — required visibility retained; name behavior corrected and verified in RM-12 |
| RM-04 | External filters at 20, 1000 and maximum priority cannot remove required menu/default rows; no duplicate keys | PASS — actual builder/customer requests; additional priority-11 removal covered Pro injections |
| RM-05 | Missing saved required rows are rediscovered despite removal filter | PASS — actual builder with request-only absent-row fixture |
| RM-06 | Blank WC Orders endpoint still reaches Orders; subscription list/detail still work | PASS — browser with request-only blank endpoint fixture |
| RM-07 | Pro deactivated: both menus survive external filter; reactivate: saved order restored | PASS — actual Plugins UI deactivation/reactivation |
| RM-08 | Restore state, verify unchanged subscription/config, close sessions | PASS — restoration.json; both temporary MU fixtures removed; sessions closed |
| RM-09 | Namespaced built-in URLs, pretty/query list/detail detection, Orders unchanged | PASS — actual browser pages plus browser GET/query-detector checks |
| RM-10 | New custom tab automatic prefix, saved reload/content, duplicate/reserved collision validation | PASS — actual create/save/open/hide/remove; browser reserved error and source collision checks |
| RM-11 | Follow-up default order: Subscriptions immediately after Dashboard, first if Dashboard is absent; saved ordering retained | PASS — browser navigation and builder, 39 source checks; request fixture removed |
| RM-12 | Required menus remain enabled/non-removable but can be renamed; save/reopen and verify menu/page titles, removal-filter restoration and empty-label fallback | PASS — actual admin saves, customer pages and late-removal fixture; 47 source checks; state restored |

Use actual editor/plugin/WooCommerce UI for regular changes. Explicitly identified request-only localhost fixtures simulate external filters and legacy configurations without persisting them; PHP-source checks supplement real browser checks. No customer-site access, subscription actions, deployment or lint/PHPCS. Capture and inspect screenshots. Issue 41 tracks this change.

Completed 2026-09-19. Results, limits, screenshots and restoration evidence: [report.md](report.md).
