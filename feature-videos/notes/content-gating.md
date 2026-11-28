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

## Recording log

- Pass 1 (21:30 UTC): ch1-4 ok; ch5 failed on a 60 s admin navigation timeout (store under load from other recordings).
- Pass 2-4: scene fixes — caption before ring, token suggestion wait (search took >12 s under load), block toolbar hidden in the editor, product token names awaited, rule-flow ring limited to the step cards, kicker per caption (Storefront / Member Access / Manage Members), drip caption reworded to what the UI shows ("from subscription start date"), wider member-price zoom, rules opened off-camera so the empty "Select a rule to edit" panel never shows.
- Pro was off in the DB 21:07:48-~21:12 UTC (outside session). Only scouting probes p34/p35 fell in that window; nothing recorded then. Every recorded chapter shows the Login Limit tab.

## Product bugs seen (worked around, not fixed)

- Manage Members profile header shows raw role slugs as badges ("Demo_member", "Demo_plus") instead of role names ("Demo Member", "Demo Plus Member"). Repro: ArraySubs → Manage Members → user 739 (Sadie Graham). Cause: `arraysubs/src/Features/MemberInsight/REST/MemberController.php:465` returns `$user->roles` (slugs) and `arraysubs/src/resources/pages/MembersAccess/ManageMembers.jsx:289-292` prints them. The video relabels the badges in the DOM for that shot.

## Final output (23:05 UTC)

- `$FV_OUT/content-gating/content-gating.mp4`: 1920x1080, 30 fps, 250.5 s, 9 chapters (ch1 24 s, ch2 44 s, ch3 17 s, ch4 26 s, ch5 32 s, ch6 39 s, ch7 31 s, ch8 18 s, ch9 19 s, incl. cards).
- `$FV_OUT/content-gating/poster.webp`: chapter 5 condition builder (Plus or Premium OR lifetime spend $250), zoomed with ring.
- The harness switched to 1920x1080 capture at ~22:41 UTC; every segment was re-recorded after that, so all segments match.
- Shared settings: none changed (`members_access` md5 7bdc6e07e2c6ab90a747c15aad875dd8 before and after).
- Left on the store on purpose (needed to re-record): page 32133 gated to Plus/Premium, order 32095 / subscription 32096 for user 739.

## Revision 2 (2026-11-27/28 UTC, after reviewer critique)

### Store changes (revision 2)

| When (UTC) | Change | IDs | Undo |
| --- | --- | --- | --- |
| 11-28 00:00 | Two posts published in the Members Journal category (term 25) for the Post Types storefront shot, dated in the past so they do not top the blog home. Meta `_fv_content_gating=1`. | posts 32745 "Harvest notes from the Guji highlands" (2026-10-14), 32746 "Why we rest every roast for seven days" (2026-11-03) | `wp post delete 32745 32746 --force` |
| 11-28 00:00 | New file `qa/feature-videos/clips/overrides/content-gating-video.json`: request-scoped overrides read by `mu-plugins/zz-fv-clips.php`, only for requests with the `fv_clip=content-gating-video` cookie (set by the scene for the recording browser). Nothing in the database changes. Overrides: flask Shop Access rule gets "Use custom message" + its own text; the leftover "New Download Rule" shows as disabled; `default_member_message` (signed-in customers without access) gets a short message; display renames `RQA Priority Support/RQA Seats/RQA Member Lounge` → `Priority Support/Event Seats/Member Lounge` (feature picker) and `/demo-url-drip/` → `/lessons/week-2/` (URL rule card). | — | delete the JSON file |

No shared settings changed in revision 2 (`arraysubs_settings` untouched; the overrides above are per request).

### Storyboard (revision 2)

| # | Kicker | Card | What is shown |
| --- | --- | --- | --- |
| 1 | Settings | Content gating (intro) | Settings → Content Gate: guest message, zoom on placeholders + message, signed-in-without-access message, the five ways to gate (Shortcode → PHP ringed, card header excluded). |
| 2 | Editor | Lock a whole page | Page 32133 editor → Access Restriction → Configure → toggle → Plus + Premium tokens (typed off camera, no "No items found") → **poster** (tokens ringed, no caption) → Pro Plan Feature picker opened (Feature Manager perks) → denied action: message, switched to redirect (page / custom URL) and back → Apply → Save. |
| 3 | Storefront | Same page. Three visitors. | Guest (default message) → Sadie 739 (Plus active, full page) → Emma 742 My Account → Memberships (Plus cancelled, access ended May 10) → same page shows the signed-in message. |
| 4 | Editor | Lock part of a page | Restricted Content block, editor note, pass tokens → guest: "Public intro" + "Locked part → message" (no dim) → Parker 748 sees the lesson. |
| 5 | Member Access | Rules for whole sections | Post Types rule flow, condition builder, category + lock icon setting → guest category archive (lock icons) → Theo 749 archive (no locks, full previews). |
| 6 | Drip | Unlock on each member's clock | URL rule (timing 14 days, "/lessons/week-2/" display rename) → Manage Members Theo: Plus created April 5 → Theo sees the lesson → Manage Members Sadie: Plus created today → Login as Customer (navigation done off camera, lands on the lesson) → "unlocks for you on December 12, 2026 3:01 am". |
| 7 | Shop | Member prices and products | Discount rule (15% + free shipping) → Shop Access rule (block purchase + custom message) → guest flask (custom message, no buy button) → Theo flask (member price 33.15$, purchase limit). |
| 8 | My Account | Member downloads | Downloads rule (limit 10, 90 days, copy-link button) → Theo My Account → Downloads (one row: Member guide). |
| 9 | Pro | Stop shared logins | Login Limit: default 2, member rule 3 devices, zoom on "New logins replace the oldest sessions…" (caption top-right). Outro. |

### What changed for each critique item (revision 2)

- Drip accuracy (major): captions/labels now say the plan start, not the join date ("Theo's Plus plan started on April 5", "Sadie's Plus plan started today", label "14 days after the plan starts", card sub "Days, weeks or months after each plan starts."). Both subscription rows (Created column) are shown on Manage Members before the storefront shots. Closing caption: "Two weeks after her plan started: December 12."
- Lapsed member (major): chapter 3 now has Emma Myers (742, my block, Plus cancelled May 10): My Account shows "Cancelled / Access Ended", then the roast page shows the signed-in-without-access message. Chapter 1 shows that message's setting. The outro line is now backed by the video.
- Lingering captions: `go()`, `goRule()` and `memberSubs()` hide the caption before every screen change; chapter 7's Shop Access caption is said after the tab loads; chapter 2 hides the redirect caption before Apply.
- Login as Customer: cursor press is shown, the click + redirect + navigation to the lesson run off camera in one `act`, so the blog archive never shows.
- Test-store menu/footer: scene-level init script (recording browser only) hides the header items linking to `/checkout/` (Checkout Classic) and `/cart-2/` (Cart Page), the footer nav columns and the theme credit row. Site menus unchanged.
- "No items found": token search typed off camera (`pickToken`), the crossfade lands on the open suggestion; the list is blurred right after each pick.
- Labels covering text: ch1 rings now include the message line with the pill inside the empty editor; ch3 message label bottom; ch4 two focuses without dim (intro right, message bottom); ch7 "Purchase blocked" top; ch5 "Any one of these" bottom; role-badge focus removed.
- Five ways: ring is a rect around the five method rows (Shortcode → PHP), all inside the frame.
- `/demo-url-drip/` slug: display-only rename to `/lessons/week-2/` via the request-scoped override file.
- Role badges: the DOM relabel is gone. The badges row is hidden (CSS, recording only) on the two Manage Members shots, because the product prints raw role slugs (bug below). No focus on roles.
- Post Types outcome: guest archive with lock icons + Theo's archive without locks (posts 32745/32746).
- Plan feature: the picker is opened and its feature list ringed ("Pro: plan features"), then closed without choosing.
- Downloads caption: "Members find them in My Account → Downloads."; leftover "New Download Rule" is off for the recording browser, so only the real member file shows; copy-link button ringed in the rule.
- Login limit zoom: scale 1.6, pad 40, caption top-right.
- Chapter cards: subs added to ch3, ch7, ch8.
- Poster: chapter 2 Access Restriction modal, Plus + Premium tokens ringed, no caption, cursor hidden.
- Flask message: Shop Access rule shows "Use custom message" with its own text (override), and the guest sees "Members can buy this limited flask. Sign in or choose a membership."

### Recording log (revision 2)

- Pass A (11-28 ~00:05-00:23 UTC): all 9 chapters re-recorded at native 1920x1080 (`run7.log`), 288.1 s.
- Pass B (~00:24-00:35 UTC): chapters 1, 2, 5, 7, 8, 9 re-recorded (`run8.log`): ch1 message rings now include the message line; ch2 caption hidden before Apply; ch5 "Any one of these" label below the group; ch7 adds the rule's custom message and moves "Purchase blocked" above the notice; ch8 drops the hover tooltip on the copy button; ch9 caption top-right during the zoom.
- Chapters 3, 4 and 6 are from pass A (also native 1080p). Every `seg-NN.mp4` is 1920x1080 (ffprobe checked); none is kept from the 1440x810 era.
- Each pass of ch2 resets page 32133's restriction meta first and saves it again in the editor (same end state). Each pass of ch6 uses Login as Customer for user 739 (adds a session).
- Scratch probes used a copy of the scene setup (`$scratchpad/cg/cgprobe.mjs`); one probe opened page 32133's Access Restriction modal and closed it without applying.

### Final output (revision 2, 11-28 00:35 UTC)

- `$FV_OUT/content-gating/content-gating.mp4`: 1920x1080, 30 fps, 290.6 s, 37.2 MB, 9 chapters (28.5 / 50.2 / 25.5 / 27.9 / 40.2 / 43.0 / 34.5 / 21.6 / 19.2 s incl. cards). freezedetect (4 s) finds no static hold.
- `$FV_OUT/content-gating/poster.webp`: chapter 2 Access Restriction modal, "Active subscription to products" ringed with Plus Membership + Premium Membership tokens, label "Plus or Premium members", no caption, no cursor.
- Shared settings: none changed (`members_access` md5 still 7bdc6e07e2c6ab90a747c15aad875dd8).
- Left on the store on purpose (needed to re-record): page 32133 gated to Plus/Premium, order 32095 / subscription 32096 (user 739), posts 32745/32746, override file `clips/overrides/content-gating-video.json`.
- Date-sensitive: ch6 says "Sadie's Plus plan started today" and "December 12". True on 2026-11-28 store time (UTC+6). Re-recording ch6 on a later day needs the caption changed to the date shown in the Created column.

### Product bugs (still open)

- Manage Members profile header prints raw role slugs as badges ("Demo_member", "Demo_plus") instead of role names. `arraysubs/src/Features/MemberInsight/REST/MemberController.php:465` returns `$user->roles` and `arraysubs/src/resources/pages/MembersAccess/ManageMembers.jsx:289-292` renders them. Revision 2 hides the badge row in the two Manage Members shots instead of relabelling it.
