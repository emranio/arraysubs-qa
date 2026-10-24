# Admin list display fixes

Verified October 25, 2026, Asia/Dhaka, against the real local WordPress site at `http://localhost:10013`, using an authenticated administrator and agent-browser. This was a targeted bug fix; active QA plan, scheduled day, and execution-board task are N/A.

## Changes

- Subscription Plans destructive row actions use a scoped class, avoiding generic `button-link-delete` hover styles. Trash stays dark red against the light row background and retains the WordPress focus ring.
- Subscriptions uses a minimum-width table in a horizontal scroll container. The Customer column retains space, payment descriptions wrap, and mobile filters and status tabs fit the page.
- Both pages belong to core. Pro uses separate, prefixed DataList styles; no Pro source changes or changelog entry are needed. Browser checks ran with Pro available.

## Reproduction and verification

At a 1000px viewport, the original Subscriptions Customer column measured **0px** wide. Its heading and customer names appeared one letter per line and overlapped payment details. After the fix it measures **188.61px**, with a 34px table header. The table scrolls within its 798px container, and the document remains 1000px wide.

| Viewport | Document width | Scroll container | Table width | Customer column |
| --- | --- | --- | --- | --- |
| 1440px | 1440px | 1238px | 1238px | 271.20px |
| 820px | 820px | 742px | 1120px | 188.61px |
| 768px | 768px | 716px | 1120px | 188.61px |
| 390px | 390px | 338px | 1120px | 188.61px |
| 320px | 320px | 268px | 1120px | 188.61px |

All widths retain 20 subscription rows. At 390px, scrolling the table 600px reveals Customer and Next Payment while page horizontal scroll remains zero. Screenshots were visually inspected at desktop and phone widths.

Trash originally computed to `rgb(255, 255, 255)` while hovered on a transparent background. After the fix it computes to `rgb(138, 36, 36)`. Keyboard focus without hover retains a visible dark red label and the WordPress accent focus ring. Enter opens the shared “Move plan to Trash?” confirmation; Cancel was selected. The final Plans list still contains 35 plans and Trash (0). No products or subscriptions were changed. The final browser error list was empty.

Production build passed. No lint or PHPCS was run. Changed source files remain below 3000 lines. Core changelog entries were appended without changing Pro's draft or previous entries.

## Evidence

- [Table before](evidence/subscriptions-1000-before.png), [table after](evidence/subscriptions-1000-after.png).
- [Phone table](evidence/subscriptions-390-after.png), [horizontal scrolling](evidence/subscriptions-390-scrolled.png), [320px](evidence/subscriptions-320-after.png).
- [Trash before](evidence/plans-trash-hover-before.png), [hover after](evidence/plans-trash-hover-after.png), [focus after](evidence/plans-trash-focus-after.png), [confirmation](evidence/trash-confirmation-after.png), [phone Plans](evidence/plans-390-after.png).
- [Viewport measurements](measurements.jsonl), [build log](build.log), [final Plans snapshot](evidence/plans-cancelled-snapshot.txt).

Issue #112 records the Trash fix. Issue #110's Subscriptions portion is resolved; its separately reported Credit History overflow remains open and unchanged by this scoped fix. No live-server deployment was performed.
