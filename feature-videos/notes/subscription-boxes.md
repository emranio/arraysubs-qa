# Subscription boxes — feature video notes

Slug: `subscription-boxes` · Tier: Free (feature page TierTag = free; Pro only adds Flexible Length /
Full Flexible boxes) · Scene: `scenes/subscription-boxes.mjs`
Output: `$FV_OUT/subscription-boxes/subscription-boxes.mp4` + `poster.webp`

Demo box: #6729 "Build Your Monthly Coffee Box" (3 steps: Coffee/Tea single picks max 3,
Extras = Accessories category 0-2, Grind (required select) / Flavor notes (checkbox) / Gift note (text) /
Gift card photo (upload, images, max 5 MB, optional; added for v2, see the change log);
tiers at $40 (10%) and $60 (15% + Ceramic Member Mug); fixed monthly; no renewal sync plan).
Note: #31342 "House Blend Coffee Box" is a simple subscription, not a box product, so it is not used.
Customer for every customer-side mutation: **#1056 Naomi Romero** (block 1044-1104).

## Storyboard (v2, after the review)

| # | Kicker | Title | What is shown |
| --- | --- | --- | --- |
| intro | | Customers build their own box | |
| 1 | Admin | A free box builder | Modules search "box" -> Subscription Box card (Free, on). Plans list, Published + type "Subscription box" -> Glow Routine, Happy Paws, Build Your Monthly Coffee Box; zoom "Priced by your selection / month". Edit product -> product type, General-tab summary (facts, steps table incl. Upload, ranges table). No "Active Subscriptions" notice: a full run deletes the previous take first. |
| 2 | Admin | Lay out the steps | Configure modal: wizard nav, Box Schedule (Fixed / Flexible Length / Full Flexible; period/interval/length), steps collapsed, Coffee element (max qty 3), Extras category (min/max 0-2), step 3 question types "Dropdown, tick boxes, text, upload", Upload element opened: Allowed file types Images / PDF / CSV + Max file size 5. Nothing saved. |
| 3 | Admin | Reward bigger boxes | Discounts & Freebies: basis select, range slider $40/$60, 10% card, $60 card (mug + 15%), range summary. Flexible Renewal Sync: tick "own segment plan" on camera (never saved) -> 3-segment slider; ring on "Supported for manual payment gateways, Stripe, and Mollie". |
| 4 | Storefront | Shoppers build it | Naomi on the box page ("Set by the picks" ring hugs the price text, label at the right), Create Subscription Box, chips, +coffee (hint 18.00$ more), +tea (10% off, next reward hint), +coffee (15% + free mug); poster = full builder, totals ringed "15% off and a free mug", cursor hidden. |
| 5 | Storefront | Ask, then review | Extras step "(choose up to 2)", step 3 "Grind is required" (star), Espresso + Chocolatey + gift note typed, gift-card.jpg uploaded (ring "Photo for the gift card"), Review details (step 1 with Edit; step 3 "Answers and the photo"), Proceed to Checkout. The old "This field is required" beat is gone (stale error, product bug). |
| 6 | Checkout | One tidy line | Block checkout: zoom 1.5 on the summary (renewal price/date, contents/gift/discount, under 4 s), zoom out, answers + photo row, price ring hugging "52.70$" ("Just the amount"), Place Order clear of the caption, order received with 0.00$ item lines and the photo link. |
| 7 | My Account | Their box, one subscription | "Once the transfer clears, the box is active." Card (notice scrolled out of view), View Details: Box Contents (label at the right), answers incl. Gift card photo, Skip / Pause. Endpoint title shows "Subscriptions" (display-only override, see below). |
| 8 | Admin | A ready packing list | Subscription detail Box Contents panel ("The subscription holds the box she built."), then the next month's **renewal order**: order note "Renewal order for subscription #... (Cycle #2 ...)", box line with the same answers + photo link, 0.00$ item and gift lines, "Every renewal ships these items and answers." |
| outro | | Stop guessing what to pack. | |

Recording-only layout CSS (no data or settings change; added with `v.context.addInitScript` in the
scene `setup`, so it only exists in this scene's headless browser):
- the builder pop-up uses at most 640px height and sits above the caption strip (like a slightly shorter window);
- the checkout page ignores the store's Checkout Builder "medium" (860px) body width and uses the full window;
- the product's placeholder short description is hidden in the checkout order summary (the clean map could
  not reach that copy because the block checkout hydrates it from data the output filter does not see);
- v2: the checkout body gets 260px bottom padding so Place Order can scroll above the caption strip;
- v2: WooCommerce's product-image hover zoom (`.zoomImg`) is hidden (the parked pointer made the image look cropped);
- v2: in wp-admin only, a MutationObserver hides the single wrong bullet "Adding a box empties the cart first ..."
  in the Box Schedule help box (product bug below). The rest of the box editor is untouched.

Chapter 7 also sets the cookie `fv_clip=subscription-boxes-video` (after `v.as(1056)`), which makes
`mu-plugins/zz-fv-clips.php` apply `clips/overrides/subscription-boxes-video.json` to that browser only:
display-only text `Memberships` -> `Subscriptions` (the store's My Account endpoint title). Nothing is written
to the database; other sessions and agents are unaffected. Undo: delete that json file.

## Store change log (every change, with undo)

| When (UTC) | Change | IDs | Undo |
| --- | --- | --- | --- |
| 2026-11-28 ~04:00 | Added display-only clean map `clean-map.d/subscription-boxes.json`: SKU `DEMO-260927-BOX` -> `BOX-COFFEE-M`, `DEMO-260927-TOTE` -> `BAG-TOTE`, the box excerpt "— demo catalog." and the description "Sample catalog item ..." replaced by real-sounding copy. Only affects `fv_clean=1` requests. | – | delete the json file |
| 2026-11-28 ~04:05 | Scouting: customer #1056 built the box (2 coffee, 1 tea, Espresso, Chocolatey, "Happy birthday, Sam!") and placed a bank-transfer order; order set to Processing via WP-CLI (as an admin confirming the transfer), subscription became active. | order 32267, subscription 32268 | Deleted automatically by chapter 6 `before` (`removeEarlierTakes`) on the next recording. |
| per recording | Chapter 1 `before` sets the three QA junk boxes (#5922 "test box", #5990 "wst", #5900 "QPC QA 2026-09-10 Box") from `publish` to `private` (direct `post_status` update + cache clean) so the plans list shows only demo boxes; chapter 2 `before` and the scene `teardown` set them back to `publish`. Window: about 2 minutes per take. | products 5922, 5990, 5900 | `FV_PRO=1 wp eval 'global $wpdb; foreach([5922,5990,5900] as $id){$wpdb->update($wpdb->posts,["post_status"=>"publish"],["ID"=>$id]); clean_post_cache($id);}' --allow-root` |
| 2026-11-28 00:46 | **Box config change** on demo product #6729 (only this scene uses it): appended an Upload element to step 3 "Make it yours": `{"id":"demo-photo","type":"upload","label":"Gift card photo","required":false,"settings":{"max_size_mb":5,"allowed_types":["images"]}}`, saved through `ProductType::saveConfiguration()`. Original config saved in `notes/subscription-boxes-config-before.json`. Left in place so the scene can be re-recorded. | product 6729 meta `_arraysubs_box_config` | `cd app/public && FV_PRO=1 wp eval '\ArraySubs\Features\SubscriptionBox\Services\ProductType::saveConfiguration(6729, json_decode(file_get_contents(WP_CONTENT_DIR."/plugins/qa/feature-videos/notes/subscription-boxes-config-before.json"), true));' --allow-root` |
| 2026-11-28 00:48 | Scouting: built renewal order #32863 for v1 subscription #32779 with `OrderCreation::createRenewalOrder()` (pending). Deleted again by chapter 1 `before` of the v2 run. | order 32863 | – (deleted) |
| 2026-11-28 00:51 | Scouting: uploaded `assets/subscription-boxes/gift-card.jpg` once through the storefront builder as #1056 (not added to the cart). The plugin created `wp-content/uploads/arraysubs-box-uploads/` (with its own `index.html` / `web.config`). Orphan upload files from the scout and the deleted take were removed after recording; the one used by the final take is kept. | uploads/arraysubs-box-uploads/Sy1sIBzCqigj5NlTl8LK.jpg (final take) | delete the folder once the final take is deleted |
| 2026-11-28 | Added `clips/overrides/subscription-boxes-video.json` (display-only `Memberships` -> `Subscriptions`, chapter 7 browser only). | – | delete the json file |
| per recording | Chapter 8 `before` builds the next month's renewal order for the take (`OrderCreation::createRenewalOrder($sub)`, status Pending payment, box child lines + answers added by `OrderHooks::addChildItemsToRenewalOrder`). `removeEarlierTakes()` deletes it with the take. | final: renewal order 33195 | `FV_PRO=1 wp eval '$o=wc_get_order(33195); $o && $o->delete(true);' --allow-root` |
| per recording | Chapters 4/5 empty customer #1056's cart (session row + persistent cart). Chapter 6 places a real bank-transfer order for the box as #1056 (earlier takes for #1056 + product 6729 are deleted first). Chapter 7 `before` marks that order Processing ("Bank transfer received."), which activates the box subscription. | user 1056; final order/subscription IDs listed below | delete the order and subscription (see `removeEarlierTakes` in the scene) |

v1 final recording order / subscription (deleted by v2): **order #32778, subscription #32779** (customer #1056 Naomi Romero,
Build Your Monthly Coffee Box: 2 x Single-origin Coffee, 1 x Herbal Tea Collection, free Ceramic Member Mug,
15% off, 52.70$/month, Espresso / Chocolatey / "Happy birthday, Sam!", bank transfer, set to Processing via
WP-CLI). Earlier takes deleted by the scene: order 32267 / sub 32268 (scout), 32630 / 32631, 32723 / 32724 (earlier takes).
(v1 undo no longer needed: v2 chapter 1 deleted 32778 / 32779 and the scout renewal 32863.)

No shared settings were changed. One Click Checkout (`checkout.one_click_mode = all_items`) and the
Checkout Builder multistep layout were already set in the store and were left as they are.

## Final take (v2, 2026-11-28)

- `subscription-boxes.mp4`: 274.7 s (4:35), 1920x1080, 30 fps, 42 MB, 8 chapters + intro/outro. Segments:
  34.6 / 46.8 / 46.8 / 34.7 / 36.4 / 24.9 / 21.9 / 28.6 s. Poster: chapter 4, the whole builder at 1.0x with both
  picked cards, the chips and the totals ringed "15% off and a free mug" (no cursor).
- Final take records: **order #33185 (processing), subscription #33186 (active), renewal order #33195 (pending)**,
  customer #1056, 2 x Single-origin Coffee, 1 x Herbal Tea Collection, free Ceramic Member Mug, 15% off,
  52.70$/month, Espresso / Chocolatey / "Happy birthday, Sam!" / gift-card.jpg, bank transfer.
  Undo: `FV_PRO=1 wp eval 'foreach([33195,33185] as $id){$o=wc_get_order($id); $o && $o->delete(true);} wp_delete_post(33186, true);' --allow-root`
- Earlier takes deleted by the scene during v2: sub 32779 (orders 32778, 32863), sub 33139 (orders 33138, 33154).
- After the final take (verified via WP-CLI): QA boxes 5922/5990/5900 are `publish`, customer #1056's cart is empty
  (no session row, no persistent cart), only #33186 exists for #1056 on product 6729.
- Shared settings untouched (`checkout.one_click_mode` still `all_items`, `checkout_builder.mode` still `multistep`).

## v2 changes (review fixes)

Reviewer verdict "fix" (4 major, 13 minor). What changed:
- Major, stale "This field is required." under Grind: the Next-click error beat was removed (scene-only fallback;
  plugin code is off limits). The star + "Grind is required" ring stays.
- Major, wrong "Adding a box empties the cart first" copy: the single bullet is hidden in the recording browser
  (MutationObserver, wp-admin only). Product bug reported.
- Major, renewal order: chapter 8 now ends on a real renewal order built by `createRenewalOrder()` (order note
  "Renewal order ... Cycle #2", same answers, photo link, 0.00$ child lines). Captions: "Every renewal order is a
  ready packing list." / "Every renewal ships these items and answers."
- Major, file uploads: Upload element added to the box (store change above); chapter 2 shows the type list and the
  allowed file types; chapter 5 uploads gift-card.jpg; the file name rides through review, checkout, order received,
  My Account, the subscription panel and the renewal order.
- Minor: labels moved off content (product title, payment method, free-gift row), price ring hugs "52.70$" with
  "Just the amount", ring pads no longer clip neighbouring lines, checkout zoom split (under 4 s), Place Order above
  the caption, product image hover zoom off, amounts written as the store does (9.30$, 0.00$), no "Active
  Subscriptions" notice (chapter 1 deletes the previous take in full runs; chapter 2 starts at the box summary),
  poster from the full builder at 1.0x without cursor, "Memberships" -> "Subscriptions" (display only), contact-admin
  notice scrolled out of the ringed card view, bridging caption "Once the transfer clears, the box is active.",
  zooms start at the modal / order-items edge (no admin-menu sliver), parked cursors hidden or moved off buttons,
  children ring clears the caption strip.
- Not changed: `naomi.romero@example.com` stays (example.com is reserved; same convention as `_shared.json`);
  the admin subscription panel's `$22.00` symbol position (product bug, below).

## Product notes found while recording

- Box editor copy vs behaviour: the Box Schedule info box in the Configure Subscription Box modal says
  "Adding a box empties the cart first: a box is bought on its own…", but `CartHooks::handleAddToCart` only calls
  `WC()->cart->add_to_cart()` (no emptying) and the user guide says "The cart is not emptied". Source:
  `arraysubs/src/resources/components/subscription-box/ScheduleSection.jsx` (~line 86). Hidden in the v2 recording.
- Storefront builder: a required-field error ("This field is required.") is only cleared by the next `validateStep()`
  (Next click), so it stays under a field after the customer fills it (`arraysubs/src/resources/subscriptionBoxFrontend.js`
  ~line 253). Repro: box #6729, step 3, click Next with Grind empty, pick Espresso: the red error stays.
- Admin subscription detail (Box Contents panel, recurring amount) prints `$22.00 / -$9.30 / $52.70` with the symbol
  on the left while the store, order screens and My Account print `22.00$` (WooCommerce currency position right).
  `formatCurrency` in the admin SPA ignores the store's currency position (`pages/subscription-detail/ContainerContentsPanel.jsx`).
  Visible for about 3 s in chapter 8.
