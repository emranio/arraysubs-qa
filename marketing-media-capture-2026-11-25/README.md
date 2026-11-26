# Marketing media capture: local store changes (2026-11-25)

Capture agents used the local store (wp-test2, http://localhost:10013) to record the screenshots and short videos for the new web-content product pages. This is what they left on the store, so it can be kept or removed on purpose.

Files in this folder:

- `growth-seed.php`: the seed script for the analytics data. Its `cleanup` mode removes every record carrying the marker. Its seed modes read `people.json` from a temporary folder that may be gone; cleanup does not need it.
- `backup-6728-6729.json`: the original settings of demo bundle #6728 and demo box #6729 before they were changed.
- `store-changes-full-log.md`: every change each agent reported, unedited (long, with duplicates).

Run WP-CLI from `app/public` after `source "/Users/emran/Local Sites/wp-test2/app/.envrc"`, always with `--allow-root`. Local's DB dump `app/sql/local.sql` is the fallback restore point.

## Still on the store

- **Seeded analytics data** (marker `analytics-growth-20261125`, registry option `arraysubs_media_seed_analytics_growth`)
  - 716 customer users (IDs 495-1210), 756 subscriptions (IDs 26181-31185), 3,538 orders, 940 retention-ledger events and 128 retention-log rows.
  - Hidden product #26179 "Team Membership" (36 team subscriptions use it).
  - A $25 store credit and 5 one-off orders for user 495; partial refunds #31197 ($23 on order #29686) and #31198 ($15 on order #28524).
  - Remove: `wp eval-file growth-seed.php cleanup --allow-root` (from this folder's path).
- **Demo products**
  - #25875 "Fresh Roast Coffee Plan", variable #25877 "Coffee Club Membership" (variations 25879, 25881, 25883), #26114 "Monthly Roast Club", #31445 "Family Membership", #31511 "Coffee Club Bundle", #31342 "House Blend Coffee Box" (published and visible in the shop).
  - Industry set for the use-case pages, all hidden, meta `_kit_usecase_media=1`: categories #44-49, 19 simple products, boxes #31421 (Glow Routine Box) and #31424 (Happy Paws Box), bundle #31427 (Daily Wellness Stack), variable #31430 (Weekly Meal Plan; variations 31432, 31434). List them: `wp post list --post_type=product,product_variation --meta_key=_kit_usecase_media --allow-root`.
  - Trashed, not deleted: #25799 and #26090 ("Studio Membership").
- **Existing demo products changed**
  - Box #6729: configuration replaced, prices cleared, sign-up fee and trial set to 0.
  - Bundle #6728: product type changed to subscription bundle, price 39.60 to 50.40, title and excerpt changed. Original values: `backup-6728-6729.json`.
  - Membership Tier variations 6723 and 6724: attribute values capitalised. Monthly Downloads reset set to Monthly on 6710, 6711, 6712, 6723 and 6724.
- **Demo customers, orders and subscriptions**
  - Users 1211 (Nina Calloway) and 1212 (Noor Haddad, role `demo_member` added).
  - Orders 25801, 25809, 25817, 25825, 25833, 25841, 25849 with subscriptions 25802, 25810, 25818, 25826, 25834, 25842, 25850.
  - Orders 31208, 31214, 31222 and switch order 31257, with subscriptions 31209, 31215, 31223.
  - Order 31283 and refund 31277 (user 95); refund 31305 on order #6778 (user 11).
  - 11 renewal-demo subscriptions (25905, 25917, 25934, 25946, 25966, 25979, 25994, 26008, 26020, 26032, 26059) with 24 orders, meta `_demo_renewals_media=renewals-media-20261125`.
  - Orders 31325, 31329, 31344, 31351, 31462, 31473, 31491, 31504, 31514, 31525, 31549 with subscriptions 31326, 31345, 31352, 31463, 31492, 31515, 31519, 31526 (meta `_demo_acct_prices_media=acct-prices-20261125` on some).
  - Payment failures recorded on subscriptions 7333, 7873, 7812 and 7909 (orders 25565, 25482, 25478 and 7912 now Failed, 3 retries queued; $30.00 store credit returned on 7812).
  - Subscription 8001: manual $99 payment recorded (order 31549), invoice 25649 cancelled, next payment moved to 2027-02-25. Subscription 7558: billing address restored, 3 activity notes remain. Subscription 26748: feature usage recorded.
  - Retention offers accepted on subscriptions 8267 and 31526 (20% off for 2 renewals); retention log row #668 on 8211 (not cancelled).
  - Store credit balances: user 95 at $155.00, user 11 at $100.00.
- **Other**
  - Pro webhook endpoints #52, #53, #54 (paused, no deliveries). Delete under ArraySubs > Webhooks.
  - Page #31220 "Lesson 2: Dial In Your Grind" (published).
  - Media library: attachments #31357, #31358, #31368-#31386, #31441, #31449 and #31543 (#31357 is no longer used).
  - Open Portal Errors rows #25903 (subscription 8267) and #26096 (subscription 8211); many activity and audit notes.
  - Subscription notes added for screenshots: #31572 (private, on subscription #31345); #31566 (private) and #31567 (shown to the customer) on subscription #31515. Delete them from each subscription's Subscription Notes card if not wanted.
- **Plugin state**: ArraySubs Pro was inactive when this work started and is active now (the captures needed it). It was left active on purpose: deactivating Pro can draft bundle products.

## Changed and restored

- ArraySubs settings toggled for single captures and restored: allow cancellation, cancel immediately, retention offers, store credit purchases, auto-renew toggle, Checkout Builder layout, Cart Info Editor, My Account menu, profile fields and avatar settings.
- Temporary Paddle and Mollie test options were added and deleted; temporary payment meta on subscription 7590 was restored.
- WooCommerce currency position was set to left for the captures (prices read "$29.00") and set back to right afterwards.

## Outside the store

- About 7 requests to the store's Anthropic AI connector (1 churn analysis, about 6 revenue forecasts), anonymised rows or store totals only, billed to that connector's key.
- All emails went to the local mail catcher.

## Later capture rounds (same day)

Source: `round3-store-changes.json` and `stills-video-store-changes.json` (agent reports, duplicates merged). Media files and manifests went to the capture scratchpad, not the store.

### Still on the store

- Users: 1213 Emma Lawson (customer, demo_member, demo_plus), 1214 Hannah Whitaker, 1215 Marcus Bell, 1216 Sofia Marchetti, 1217 Owen Fletcher, 1218 Olivia Bennett, 1219 Clara Jennings, 1220 Noah Whitfield, 1221 Priya Raman. Membership rules also gave 1214-1217, 1219, 1220 demo_* roles. Meta `_demo_emails_media` / `_demo_cxc_media` marks 1213-1217, 1219, 1220 and their orders and subscriptions.
- Emails round: order 31593 -> sub 31594 ($39 monthly, next payment 2026-12-28), renewal order 31613; order 31604 -> sub 31605 (seeded Stripe card, expiry 11/2026, no renewal jobs).
- Cancel round: orders/subs 31628/31629, 31640/31641, 31661/31662, 31674/31675, 31724/31725, 31734/31735. Checkout round: 31694/31695 (user 1218). Platform round: 31769/31770 (user 1221).
- Trashed, not deleted: order 31650 / sub 31651 (mistaken trial variation 25879); trash note 31673.
- Accepted retention offers: 31641 and 31725 (20% off next 2 renewals, $24.00 and $79.20); 31675 (pause); 31662 (cheaper plan).
- Scheduled plan switch: 31662 to Plus Membership (6711), $39.00, 2026-12-02 06:18 UTC, in `_pending_switch`, no order.
- Paused: 31675 (resume 2026-12-25 15:27 local, next payment 2027-01-08). Cancel at period end then undone: 31735 (Active). 31629: Before You Go opened 7 times, kept.
- Queued scheduler actions: 31594 (26411-26413), 31629 (26418/26419), 31641 (26423/26424), 31662 (26433/26434), 31675 (26439/26440), 31725 (26468-26470), 31735 (26617/26618), plus reminders.
- Logs and notes: retention log rows 805-821, retention events 7863, 7864, 7867, 7870, 7872, 7873, 7875-7880, many notes (e.g. 31673, 31690-31691, 31700-31704, 31714, 31744-31745, 31751-31753).
- Mailpit only, nothing delivered: Renewal Reminders UYZQmS2PVKKNTuFvKNM4YS and PmZiLyLSEAFQN3rVGeexFL, Invoice 4vdRLkrqqTbcQSbJpvXqvG, Card Expiring e7Kz3L3cducj5AB9mppzQZ, admin new order 31613 KHDCTMqPyUhayUCFd2jdCW, Payment received 8qNfXQGMECvFBLZVEgBhKd, test send to lucas.moreau@example.com gJ96eHdvNghLjgUDJPBwSF, Retention Discount Accepted G3xLon4juWmrP5nHnhiyXr, plus signup and lifecycle emails.
- Webhook endpoint #55 "Accounting sync" (Paused, not deleted), deliveries #194-#198; its receiver 127.0.0.1:8765 is stopped.
- Product #31767 "Demo - Studio Season Pass" in Trash; product category term #50 "Demo Season Passes" remains. Product #26114 moved from term 15 to term 19 (revert: `wp post term set 26114 product_cat 15 --by=id`).
- Page #31750 "Week 2: Pour Your First Latte Heart" (/demo-url-drip/, meta `_arraysubs_marketing_capture=members-drip-20261125`).
- Media attachments: #31692 (order 31694, sub 31695); #31720 and #31758 are unattached, the plugin's daily cleanup should remove them after about 24 h.
- Other: Login as User used for 1212, 1178, 535, 1214-1217, 1219, 1220 (may add Activity entries). `_edit_lock` refreshed on 6729, 6728, 31427. Guest session rows remain and expire.

### Changed and restored

- Cancellation settings: `allow_cancellation` false -> true -> false (two windows, 09:14-09:32 and 09:56-10:04 UTC); `cancel_immediately` and `retention_offers_enabled` true -> false -> true (10:02-10:04 UTC).
- `checkout_builder`: replaced in short windows (09:18-09:52 and 10:18-10:23 UTC) and restored (md5 097c5d814ce25ee11a7ec2e402984cf8); full `arraysubs_settings` md5 2f345dff4d12082023a6dad74a1ce2a7 matches the backup.
- Members: `custom_message_enabled` on URL rule demo260927-url-drip set true and removed (`members_access` md5 a545320448e727580c25352282bdd243 unchanged).
- Capture-only mu-plugin `zz-kit-capture-gateway-demo.php` (PayPal/Paddle look connected, cookie `kit_gw_demo=1`) created and deleted; those options never existed.
- WooCommerce currency position was set to left for the captures and set back to right afterwards.
- Carts: guest carts emptied. Unsaved editor changes discarded with Discard Changes/Cancel.

### Unclear or not restored

- Currency: checked afterwards, the position is back to right.
- `arraysubs_settings_last_saved_at`: baseline 1795562533. Other agents stamped later values (1795599015, 1795599904, 1795601406, 1795602047); the checkout reviewer reset it to 1795562533 at 10:24:31 UTC. Final value not confirmed; the billing report still shows the baseline.
- `url_rules.2.custom_message_enabled=true` was seen at about 10:02 UTC and left by the cancel reviewer; the members agent says it removed the key. Checked afterwards: that rule no longer has the key.
- Cancel-admin reports allow_cancellation ON 09:25:42 and OFF 09:49:44 UTC from "another agent"; the window times do not match the cancel-customer reports.
- Webhook #55 and product #31767 were left (Paused, Trashed), not deleted; term #50 stays.
- A PHP fatal in `wp-content/debug.log` (09:40:10 UTC, POST `arraysubs/v1/checkout-builder/product-table/cart`, product 6698) was not investigated.
- Product Table fix check (after the capture rounds): temporary Checkout Builder layouts with a Product Table were applied and the original restored (checkout_builder md5 097c5d814ce25ee11a7ec2e402984cf8, full arraysubs_settings md5 2f345dff4d12082023a6dad74a1ce2a7, last_saved_at 1795562533); currency set to left during the capture and back to right; guest test carts emptied; no orders placed.

## Bug-fix verification (same day)

- Still on the store: demo customer 1222 (qaverify1125) and demo subscription 31865; a guest cart with Balancing Toner 31442; the new empty `members_access.default_member_message` setting key added by the member-access fix.
- Changed and restored: avatar settings, Content Gate default messages, Checkout Builder test layouts and dark scheme, test pages 31854-31856 (deleted), session tokens (removed).
- Full list: `bugfix-store-changes.json` in this folder.
