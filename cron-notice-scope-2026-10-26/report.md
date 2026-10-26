# Cron notice scope and Gateway Logs layout

Verified against the actual local WordPress installation at http://localhost:10013 using agent-browser and administrator ID 1 (`admin`; administrator and demo_member roles), with ArraySubs Pro active. This is validation of the requested UI changes; no subscription lifecycle QA plan was reused. Subscription and order IDs: N/A.

The shared cron warning now appears only in Tools > Site Health > Status, WooCommerce > Status > Scheduled Actions, and ArraySubs > Renewal Flow > Gateway Logs. On Gateway Logs, it renders inside the page content below the header menu and above the gateway cards. Re-check and dismissal keep the Gateway Logs hash route. Gateway cards use the available row width without reserving empty columns.

## Browser verification

| Screen or interaction | Result |
| --- | --- |
| WordPress Dashboard | Warning absent |
| WooCommerce Settings | Warning absent |
| WooCommerce Status default tab | Warning absent |
| WooCommerce Status Scheduled Actions tab | One warning visible |
| Tools Scheduled Actions | Warning absent |
| ArraySubs Home | Warning absent |
| Old `/settings/gateways` route | Warning absent |
| Renewal Flow Grace Period, reached through the header menu | Warning absent |
| Renewal Flow Gateway Logs | One warning visible below header menu |
| Site Health Status | Full warning visible above the checks, with the timing issue also available in its collapsed issue details |
| Site Health Info | Warning absent |
| Gateway Logs Re-check | Stays on Gateway Logs and clears the temporary late execution warning |
| Gateway Logs dismissal | Stays on Gateway Logs and hides both admin notice copies |
| Site Health after dismissal | Subscription renewal timing issue remains visible |
| Layout at 1920px and 3840px | Four equal cards fill the row and align with the event log |
| Layout at 1024px | Two equal columns fill the row |
| Layout at 390px | One full-width card per row; no page overflow |

Evidence: [screen counts](evidence/screen-results.json), [layout measurements](evidence/layout-results.json), [desktop screenshot](evidence/gateway-logs-final-desktop.png), [mobile screenshot](evidence/gateway-logs-final-mobile.png), and the accompanying browser snapshots.

Site Health visibility follow-up: the issue details were present but collapsed, so the native notice now also renders directly on the Status tab. Browser-verified with the existing recorded late execution; no synthetic health record was added. See [visible notice](evidence/site-health-visible-notice.png). The Info tab has no notice, and temporary browser authentication was removed after checking.

Gateway card design follow-up: unavailable capabilities now use a bordered definition list with separate headings and descriptions, and disabled cards retain readable text. All eight PayPal explanations remain present. The disclosure uses Show more / Show less, aria-expanded, and a controlled hidden region. Keyboard Enter collapses and expands the panel while retaining focus. Desktop and 390px mobile checks passed, with no page or limitation-text overflow. See [desktop limitations](evidence/limitations-desktop.png) and [mobile limitations](evidence/limitations-mobile.png).

Gateway settings follow-up (#118): browser-reproduced empty Stripe and Mollie payment sections while their official plugins were inactive. PayPal and Paddle were counterexamples with working forms. The endpoint now resolves native gateway settings, Stripe's registered setup placeholder, and Mollie's actual settings tab or plugin setup. All four card links were followed in the browser after using the dashboard Refresh action, and each displayed the expected populated destination: [results](evidence/gateway-settings-results.json). The core-only WP-CLI request produced the same valid links with Pro skipped. The active Mollie route was checked against its installed settings registration; no provider plugins were activated and no payment settings were changed. Production admin build and PHP syntax checks passed.

## Validation and cleanup

- Production admin webpack build passed; unrelated entry bundles were preserved.
- PHP syntax checks and scoped whitespace checks passed.
- All changed plugin source files are below 3000 lines.
- Browser checks used a temporary recorded late-execution marker, with no scheduled jobs, subscriptions, or orders created or modified. The original marker, transient, dismissal state, and temporary authentication session were restored after verification.
- Existing changelog entries and unrelated workspace changes were preserved. Only the core draft received entries; Pro uses the core UI and monitor.
