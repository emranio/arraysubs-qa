# content-gating — feature video notes

Scene: `scenes/content-gating.mjs`. Output: `$FV_OUT/content-gating/content-gating.mp4` + `poster.webp`.
Tier on cards: Free + Pro (feature page catalog tier `free+pro`; Login Limit and plan-feature gates are Pro).

## Storyboard

| # | Kicker | Card | What is shown |
| --- | --- | --- | --- |
| 1 | Settings | Content gating (intro) | Settings → Content Gate: default message for guests, merge-tag help, "Where to gate content" (5 ways). |
| 2 | Editor | Lock a whole page | Page 32133 editor → Access Restriction → Configure → toggle on → pick Plus + Premium → Pro plan-feature field → denied action → Apply → Save. |
| 3 | Storefront | Same page. Two visitors. | Guest sees the default message; Sadie (739, Plus) sees the full page. |
| 4 | Editor | Lock part of a page | Page 31220 Restricted Content block (editor note, Quarterly/Annual pass condition) → guest sees intro + message → Parker (748, Quarterly pass) sees the lesson. |
| 5 | Member Access | Rules for whole sections | Post Types tab, "Members Journal category" rule: flow, condition builder (logged in AND (Plus/Premium OR spent $250)), category + lock icon. Poster frame. |
| 6 | Drip | Unlock on each member's clock | URL rule "Unlock the week-two lesson", Timing 14 days → Theo (749, since April) sees it → Manage Members (Sadie) → Login as Customer → "This content unlocks for you on …". |
| 7 | Shop | Member prices and products | Discount rule (15% + free shipping), Shop Access rule (show, block purchase) → guest flask (message) → Theo flask (member price, purchase limit). |
| 8 | My Account | Member downloads | Downloads rule (limit 10, expiry 90 days) → Theo's My Account → Downloads. |
| 9 | Pro | Stop shared logins | Login Limit tab: default 2 sessions, member rule 3 devices, oldest session signed out. Outro. |

Recording notes: the scene strips the `Document-Isolation-Policy` header for `post.php` in the recording browser only (Playwright route), because with it the block editor renders at the wrong size inside the stage iframe. Role badges on Manage Members are relabelled in the DOM for the shot (`demo_member` → Member, `demo_plus` → Plus Member), display only.

## Store changes (log)

All times UTC, 2026-11-27.

| When | Change | IDs | Undo |
| --- | --- | --- | --- |
| 21:01 | Customer 739 (Sadie Graham, my block) bought Plus Membership through the Store API checkout (bank transfer). | order 32095, subscription 32096 (Plus Membership 6711, $39/month, start 2026-11-27 21:01:26 UTC) | Trash order 32095 and subscription 32096 in admin, or `wp post delete 32096 --force` + `wp wc shop_order delete 32095` |
| 21:02 | Order 32095 marked Processing ("Bank transfer received."), which activated subscription 32096. Role Mapping then added `demo_member` and `demo_plus` roles to user 739 (existing rules). | 32095 / 32096 / user 739 | Cancel 32096 (roles follow the rules) |
| 21:10 | Page "Member Roast: Ethiopia Guji" created (published) for the whole-page gate shot, meta `_fv_content_gating=1`. | page 32133 (/member-roast-ethiopia-guji/) | `wp post delete 32133 --force` |

| each recording of ch2 | Page 32133's own restriction meta is cleared before the chapter (`_arraysubs_restrict_config/_enabled/_conditions/_message`), then the chapter sets it in the editor (active subscription to Plus 6711 or Premium 6712, default message) and saves the page. | page 32133 | delete those 4 meta keys, or delete the page |
| each recording of ch6 | Admin used "Login as Customer" for user 739 (adds a session for 739 and possibly an Activity audit entry). | user 739 | none needed; sessions expire |

Display-only renames (no data change): `clean-map.d/content-gating.json` (created by a duplicate instance of this agent at 21:16 UTC; kept): "RQA 2112 protected URL" → "Webinar archive", "New Download Rule" → "Brewing basics pack", `>woocommerce-placeholder<` → `>Brewing Basics Guide<`, "Demo Members Journal" → "Members Journal", `>Demo Accessories<` → `>Accessories<`, flask short description, flask SKU, "eligible demo membership" / "available to demo members" without "demo".

Duplicate-instance audit (21:25 UTC): no extra pages, rules, orders or subscriptions for users 739-799 beyond the rows above; `members_access` rules unchanged. Its leftovers were only probe images and the clean-map file.

No shared settings changed.
