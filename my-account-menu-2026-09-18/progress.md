# My Account browser QA progress

Plan: [plan.md](plan.md). Scheduled day: 2026-09-18.

| Task | Status | Observation / proof |
| --- | --- | --- |
| MA-01 Free baseline | Pass | Menu/list work with saved disabled item; free owned detail verified after actual Pro deactivation. Screenshots 01, 13, 14 |
| MA-02 Actual Pro activation | Pass | Plugins UI activation applies saved disabled item; pretty/query list URLs redirect to dashboard. Screenshot 03 |
| MA-03 Enable and persist | Pass | Enabled through editor, saved/reopened; list and clicked detail link render. Screenshots 05–07 |
| MA-04 Reorder and persist | Pass | Dragged Subscriptions first, saved/reopened; later dragged Orders first. Customer order matches; survives Pro restart. Screenshots 09, 10, 15–17 |
| MA-05 Disable Orders | Pass | Saved/reopened disabled Orders with Subscriptions enabled; list/detail remain usable. Screenshots 18–19 |
| MA-06 Disable/enable Subscriptions | Pass | Disabled and saved: menu gone, pretty/query redirect; enabling restores list/query portal. Screenshots 20–21, 28 |
| MA-07 Master customization toggle | Pass | Off restores default menu and portal with saved disabled items intact; on reapplies hidden configuration. Screenshots 23–25 |
| MA-08 Pro lifecycle transitions | Pass | Actual Plugins UI deactivate/reactivate tested with visible/reordered and hidden configurations; Free portal returns and Pro settings reapply. Screenshots 13–15, 26–27 |
| MA-09 Blank Orders endpoint / permalink refresh | Pass | Real WC Orders endpoint blank: Subscriptions survives in Free and Pro. Permalink save preserves list and detail. Orders endpoint restored through WooCommerce UI. Screenshots 29–33, 36–37 |
| MA-10 Guests and reloads | Pass | Guest pretty/query/detail visits show login, no subscription data. Reopened editor and customer pages throughout. Screenshots 22, 34 |
| MA-11 Restore / wrap up | Pass | Original Orders endpoint, exact menu array, master toggle, inactive Pro and permalink structure match baseline. Subscription status and serialized full-meta SHA-256 unchanged. Final list and clicked detail work; impersonation exited and three sessions closed. Screenshots 35–37; restoration.json |

No new functional issue confirmed in the executed menu, routing and lifecycle checks. Existing customer incident 36 remains open; issue 37 was fixed and is receiving broader real-settings regression coverage here.
