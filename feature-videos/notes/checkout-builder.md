# checkout-builder: storyboard and store-change log

Scene: `scenes/checkout-builder.mjs`. Output: `$FV_OUT/checkout-builder/checkout-builder.mp4` + `poster.webp`.
Tier: Pro (feature page `web-content/src/pages/features/checkout-builder.astro`, TierTag = pro).

## How the demo layout is isolated (no shared setting is changed)

Ten agents share this store, so the Checkout Builder layout shown in the video never touches the
shared `arraysubs_settings['checkout_builder']`.

- `wp-content/mu-plugins/zz-fv-checkout-builder.php` (NEW, temporary): active only for requests with
  the cookie `fv_cb=1`, which only this scene's recording browser sets (the scene adds it after every
  `v.as()`). For those requests it
  - reads `arraysubs_settings['checkout_builder']` from the private option `fv_cb_overlay`, and
  - turns any `update_option('arraysubs_settings')` into a no-op after copying `checkout_builder` into
    `fv_cb_overlay` (so the on-camera Save works but never writes the shared option).
- `notes/checkout-builder-layout.php` writes the demo layout into `fv_cb_overlay`:
  `FV_PRO=1 wp eval-file wp-content/plugins/qa/feature-videos/notes/checkout-builder-layout.php <pre|final|delete> --allow-root`
  - `pre`  = layout before the on-camera drag (no "Photo of your dog" upload field)
  - `final` = layout after the on-camera drag and Save
- Real shared value backed up before any work: `$FV_OUT/checkout-builder/real-checkout-builder-before.json`
  (md5 of `json_encode($settings['checkout_builder'])` = `33872a386f361225b061513e6db09ed3`). Re-checked
  after the duplicate-agent incident (21:22 UTC): unchanged.

Demo layout ("Grain-Free Dog Food" subscription, 4 steps, purple custom colors, Medium width, step bar on top,
Copy Custom Fields to Renewal Orders = on):
1. Your details: Billing Address
2. Your dog: Dog size (Grid Select, required), Any food allergies? (Grid Select Yes/No, required),
   Which ingredients should we avoid? (multi Grid Select, required, Show When allergies Is yes),
   Add a treat to the first box (Product Table: Peanut Butter Biscuits, Chicken Training Treats, Dental Chews,
   Squeaky Tennis Ball), Photo of your dog (File Upload, dragged in on camera)
3. Delivery: Delivery day (Grid Select, required), First delivery (Calendar, Dec 1 – Jan 31), Order Notes
   (renamed "Delivery notes")
4. Payment: Coupon and Notices, Order Info and Payment

## State left behind (checked 23:50 UTC)

- Shared `arraysubs_settings['checkout_builder']`: unchanged (md5 `33872a386f361225b061513e6db09ed3`).
- `fv_cb_overlay` option and `mu-plugins/zz-fv-checkout-builder.php`: still present on purpose so chapters can
  be re-recorded; both only act for the `fv_cb=1` cookie. Remove with the commands below once approved.
- Customer 703 cart: empty. Pass-1 demo order 32572 / subscription 32573 were trashed in pass 2; the order shown
  in the current video is **order 32981 / subscription 32982** (see "Pass 2" below).
- Private checkout-upload attachments from the trashed scouting/recording orders stay in
  `uploads/arraysubs-checkout-uploads` until those orders are deleted for good.

## Undo (after the video is approved)

```bash
source "/Users/emran/Local Sites/wp-test2/app/.envrc"; cd "/Users/emran/Local Sites/wp-test2/app/public"
FV_PRO=1 wp option delete fv_cb_overlay --allow-root
rm wp-content/mu-plugins/zz-fv-checkout-builder.php
```

## Store change log

| When (UTC) | Change | IDs | Undo |
| --- | --- | --- | --- |
| 20:58 | Created private option `fv_cb_overlay` (demo layout, see above) | option `fv_cb_overlay` | `wp option delete fv_cb_overlay` |
| 20:58 | Added mu-plugin `zz-fv-checkout-builder.php` (cookie-gated overlay) | file | delete the file |
| 21:07 | Scouting order placed as customer 687 Hazel Phillips. ArraySubs Pro had just been switched off by an outside session (21:07:48), so no answers were saved | order 32135, subscription 32136, upload attachment(s) in `uploads/arraysubs-checkout-uploads` | Trashed at 21:13 (order -> trash, subscription -> trash). Restore: untrash in admin |
| 21:18 | Scouting order placed by a duplicate instance of this agent as customer 687 (2 x Small bag + Peanut Butter Biscuits, all answers saved) | order 32147, subscription 32148, private upload attachment 32146 (biscuit.jpg) | Trashed at 21:25. Restore: untrash in admin |
| 21:xx | Customer 687 persistent cart + WC session cleared several times while scouting | user 687 | none needed |

Recording orders (customer 703 Willow James) are listed below as they are created.

| When (UTC) | Change | IDs | Undo |
| --- | --- | --- | --- |
| ~22:35 | Pass 3 recording, chapter 6: order placed on `/checkout/` (Grain-Free Dog Food Medium bag $45/month + Peanut Butter Biscuits $8, Direct bank transfer, all answers + photo) | order 32387, subscription 32388, private upload attachment | Superseded; trashed at ~23:20. Restore: untrash in admin |
| ~22:48 | Pass 4 recording, chapter 6: same cart, all answers + photo | order 32478, subscription 32479, private upload attachment | Superseded by the native-resolution pass; trashed at ~23:35. Restore: untrash in admin |
| ~23:32 | Pass-1 native 1920x1080 recording, chapter 6: Grain-Free Dog Food Medium bag $45/month + Peanut Butter Biscuits $8, Direct bank transfer, all answers + photo | order 32572, subscription 32573, private upload attachment | Superseded in pass 2; trashed 2026-11-28 00:42 UTC. Restore: untrash in admin |
| every recording | Customer 703 cart + WC session cleared in setup/teardown and before chapters 5 and 9 | user 703 | none needed |

## Output

- `$FV_OUT/checkout-builder/checkout-builder.mp4` (1920x1080, 30 fps, H.264, no audio, ~4:36) and `poster.webp`
  (chapter 5: the "Which ingredients should we avoid?" follow-up ringed right after "Yes").
- Recorded natively at 1920x1080 after the shared harness switched its default capture (04:41 local); all 9
  segments are 1920x1080. Final length 4:39 (278.9 s), 29.4 MB.
- Captions sit top-right on the classic checkout pages (the 860 px form fills the left, so bottom-right would
  cover the Next button), bottom-right elsewhere, bottom-left for the builder Save moment (the toast is
  bottom-right).

## Storyboard (pass 1; chapters 1, 3-9 were extended in pass 2, see "Pass 2" below)

Intro card: "Checkout Builder" (Pro chip). Outro: "Ask at checkout, not after."

1. **Admin: Your checkout on one canvas.** Builder full screen (pre layout). Step tabs (4 steps), "Your dog"
   step, zoom on cards (red asterisk = required, eye = conditional), Elements palette (28 elements, scrolled
   through Basics and Others), toolbar (Undo, Redo, Reset, Discard, Save): nothing is live until Save.
2. **Admin: Drag in a question.** Real pointer drag of the File Upload tile onto the canvas (blue drop line),
   settings open, type label "Photo of your dog" + help text, zoom on settings, type settings (size, count,
   types), Save -> "Configuration saved!" toast (writes only `fv_cb_overlay`).
3. **Admin: Ask only what applies.** Final layout. Open "Which ingredients should we avoid?", scroll to
   Show When: Field = Any food allergies?, Operator = Is, Value = yes. Open the Field list (any of the store's
   own questions), the Operator list (5 operators), AND/OR, Back, eye icon on the card.
4. **Settings: Match your store.** Settings tab: Copy Custom Fields to Renewal Orders (on), Color Scheme
   (Custom, purple), Container Width / Border Radius / Spacing, Navigation Position + Step Indicator.
5. **Checkout: Short steps, checked.** Customer 703 Willow James, classic checkout page `/checkout/` with
   Grain-Free Dog Food Medium bag ($45 / month). Step bar, step 1 billing, Next, step 2 (zoomed): Medium + Yes
   -> follow-up appears (poster), product table adds Peanut Butter Biscuits, photo upload (real file chooser),
   Next with the follow-up still empty -> "Which ingredients should we avoid? is a required field.", then
   Chicken + Wheat.
6. **Checkout: Delivery, then payment.** Step 3: Wednesday, first delivery date, delivery notes. Step 4:
   coupon box + order summary, Place order -> order received with the "Additional Information" answers.
7. **Admin: Every answer, kept.** WooCommerce order screen "Checkout Builder Fields" box; subscription
   detail "Custom Fields" panel (Selections, Dates & times, Files); subscription edit screen.
8. **My Account: Customers see their answers.** View subscription page rows.
9. **Checkout: On the Checkout block too.** `/checkout-2/` (block checkout, zoomed): fields in Additional order
   information, Show When on "Yes"; steps/columns/design are classic-only. No order placed here.

## Product bugs found (not fixed, worked around)

1. **"Allow Multiple" switch reads off for a saved multi-choice Grid Select / Image Select; editing the field
   then saves it as single choice.**
   - Code: `arraysubs/src/resources/pages/CheckoutBuilder/FieldSettingsPanel.jsx`. `flattenTypeSettings()` maps
     `type_settings.multi` to the form key `ts_multi`, but the Switch is bound to `ts_multiple`
     (grid_select ~line 980, image_select ~line 944). `ConfigSanitizer::sanitizeTypeSettings()` only stores
     `multi`. `handleFinish()` turns `ts_multiple` (undefined/false) into `multi`, overwriting `true`.
   - Repro (done 21:40 UTC on the private overlay only): saved layout with a Grid Select, `type_settings =
     {"multi":true,"columns":4}` -> open ArraySubs > Checkout Builder -> click the field -> "Allow Multiple" is
     off (aria-checked=false) -> type into Help Text -> Save -> stored `type_settings = {"multi":false,"columns":4}`.
     The storefront then renders radios instead of checkboxes.
   - Workaround for the video: the demo layout also stores `type_settings.multiple = true` (display only).
2. Cosmetic: after a failed **Next**, the red "invalid" highlight on Grid Select questions stays until the next
   Next/validation, even after the shopper picks an option (WooCommerce's own validate_field does not listen to
   radio/checkbox cards). Visible briefly in chapter 5.

---

## Pass 2: reviewer critique fixes (2026-11-28, ~06:00-07:30 local / 00:00-01:30 UTC)

All 9 chapters are native 1920x1080 (checked with ffprobe). Chapter 3 was recorded in this pass at 06:3x local;
chapters 1, 2 and 4-9 in the final batch of this pass.

### What changed in the scene (`scenes/checkout-builder.mjs`)

- **Ch 1** now covers step management: hover a step tab (Rename / Delete icons), "Add Step", then the Payment
  step with "Order Info and Payment" ringed ("Place order shows here"). The "Your dog" card zoom was dropped
  (chapter 3 shows the eye icon and required marks).
- **Ch 2** has its own caption for the typing beat ("Name it and add help text..."), types Max Files = 2 and
  Allowed Types = jpg,png on camera, and rings the type settings with the help line inside the ring (label below,
  not on the canvas card). The label/help zoom was dropped to save time (sidebar text is legible at 1080p).
- **Ch 3** no longer zooms the Show When rule (the zoom magnified the repeater header
  "Item 1 - _arraysubs_cf_food_allergies"); the rule is ringed full screen with dimmed surroundings. The AND/OR
  beat rings the Logic select and "+ Add Condition" ("Add more rules and join them with AND or OR"). The eye-icon
  beat has its own caption.
- **Ch 4** ("Match your store") now starts with the address form: Billing Address -> Limit Countries "US,CA",
  Phone switched off, Email label renamed to "Email for delivery updates", then the Settings tab (renewal
  switch zoom with the label below, colors, layout and step bar rings clamped below the sticky tabs), then Save.
- **Ch 5** starts at the page top (`scrollTo(0)` after load, the step script scrolls the bar to the edge),
  bottom-right captions throughout, a padded step-bar zoom (1.35x) so the ring's top is in frame, the trimmed
  billing form on the storefront (country dropdown lists only Canada and United States, no phone field, renamed
  email label), `say(null)` before every Next, cursor parked off content after clicks, labels to the right of
  the form, poster taken after parking the cursor.
- **Ch 6** opens on the answered follow-up and the added treat (the stale "required field" notice is out of
  shot), picks **Saturday** and **12/12/2026** (reads the same day-first or month-first; the recording browser
  is headless Chromium on an en-BD macOS, and its date inputs ignore the context locale; tested `--lang`,
  `LANG/LC_ALL` and the context `locale`, none change it), rings the coupon box and the order rows separately
  with labels in the free right-hand space.
- **Ch 7** clears the localhost "Customer IP ::1" from the demo order before filming (`set_customer_ip_address('')`),
  opens with "Each order keeps the shopper's answers", zooms only the Checkout Builder Fields box, and on the
  subscription edit screen really changes Delivery day (Saturday -> Friday, not saved) and rings the Files row
  ("Remove or add a photo"). The bottom-centre caption keeps the sticky save bar's "Updated at: Not recorded yet"
  off camera. Navigating away does not raise a leave-page dialog (tested).
- **Ch 8** adds the My Account order view ("Additional Information") before the subscription page.
- **Ch 9** label on the left of the block (no longer over the order summary), and the page scrolls 140 px before
  the closing caption so it clears the date input.

### Demo layout changes (`notes/checkout-builder-layout.php`)

- Variants are now `pre` (before the drag), `noaddr` (after the drag, stock address form: chapters 3-4) and
  `final` (noaddr + the chapter 4 address edits: chapters 5-9).
- Labels "Any food allergies?" -> **Food allergies** and "Which ingredients should we avoid?" -> **Ingredients to
  avoid** (meta keys unchanged). This avoids the product's "?:" double punctuation on the admin order box and
  the My Account subscription page without masking it.
- Delivery day options Wednesday / Friday / **Saturday** (was Monday / Wednesday / Friday).
- Photo upload: max 2 files, jpg/png (so the subscription editor says "Up to 2 files", not "Up to 1 files").
- Billing Address in `final`: countries US, CA; billing_phone hidden; billing_email label "Email for delivery updates".
- Still stores the display-only `type_settings.multiple = true` beside `multi` (see product bug 1 below).

### mu-plugin `zz-fv-checkout-builder.php` (cookie `fv_cb=1` only)

Added two display-only filters for the recording browser: the Site Header Menu drops "Checkout Classic" and
"Cart Page" (`render_block_core/navigation-link`), and the admin menu hides the dev site's update-count badges
(`#adminmenu .update-plugins, .awaiting-mod`). Backup of the previous version:
`$FV_OUT/../zz-fv-checkout-builder.v1.php` (scratchpad).

### Store changes in this pass

| When (UTC) | Change | IDs | Undo |
| --- | --- | --- | --- |
| 00:05-00:35 | `fv_cb_overlay` rewritten many times by the layout script and the on-camera Saves | option `fv_cb_overlay` | `wp option delete fv_cb_overlay` |
| 00:40 | Pass-2 test recording order (chapter 6), customer 703: Grain-Free Dog Food Medium + Peanut Butter Biscuits, bank transfer, Saturday, 2026-12-12 | order 32821, subscription 32822, private upload | Superseded; trashed 01:02. Restore: untrash in admin |
| 00:42 | Superseded pass-1 order and subscription trashed | order 32572, subscription 32573 | untrash in admin |
| every recording | Customer 703 cart / WC session cleared in setup, teardown and before chapters 5 and 9 | user 703 | none needed |
| 00:56 | **Final recording order (chapter 6, shown in chapters 6-8 of the final video)**, customer 703 Willow James: Grain-Free Dog Food Medium bag $45/month + Peanut Butter Biscuits $8 = $53.00, Direct bank transfer, answers Medium / Yes / Chicken+Wheat / biscuit.jpg / Saturday / 2026-12-12, note "Please leave it by the side gate." | **order 32981 (on-hold), subscription 32982 (pending)**, private upload attachment | Keep while the video is current; trash both to undo |
| 00:57 | Customer IP cleared on the demo order before filming chapter 7 (`set_customer_ip_address('')`, was the localhost `::1`) | orders 32821, 32981 | none needed (display data on demo orders) |
| 01:02 | Pass-2 test order trashed | order 32821, subscription 32822 | untrash in admin |
| 01:0x | Subscription 32982 edit screen: Delivery day switched Saturday -> Friday on camera, **never saved** (page left without Save) | subscription 32982 | none needed |

Shared `arraysubs_settings['checkout_builder']` re-checked after the final batch (01:02 UTC): md5
`33872a386f361225b061513e6db09ed3`, unchanged. No other shared option was touched. `fv_cb_overlay` and the
cookie-gated mu-plugin stay so chapters can be re-recorded (undo commands above).

### Final output (pass 2)

- `$FV_OUT/checkout-builder/checkout-builder.mp4`: 298.0 s (4:58), 1920x1080, 30 fps, H.264, no audio, 27.6 MB.
  Segments: 28.2 / 33.7 / 35.0 / 44.2 / 57.7 / 36.5 / 27.5 / 13.0 / 22.2 s, all 1920x1080.
- `poster.webp`: chapter 5, step 2 zoomed, the "Ingredients to avoid" follow-up ringed right after "Yes", cursor
  parked in the empty right-hand area.

### Product bugs (pass 2 additions; do not fix from the video work)

3. **Labels ending in "?" get a second colon** ("Any food allergies?:"): `AdminFieldDisplay.php` line 74
   (`<strong>label:</strong>`, order screen box) and `DisplayHooks.php` line 63 (`<th>label:</th>`, My Account
   subscription page). Append ":" only when the label does not already end in ?, : or . (Avoided in the video by
   using labels without a question mark.)
4. **"Up to 1 files"**: `arraysubs/src/resources/pages/subscription-detail/CheckoutUploadEditor.jsx` line 208 uses
   `__()` for "Up to %1$d files · %2$s MB each"; needs `_n()`. (Avoided: the demo field allows 2 files.)
5. **"Updated at: Not recorded yet"** in the subscription edit screen's sticky save bar
   (`arraysubs/src/resources/components/SettingsLastSavedAt.jsx` line 13) reads like debug text; hide it when empty.
6. After a failed **Next**, the step's "X is a required field." notice stays on screen after the shopper answers,
   until the next Next/validation (same root cause as bug 2: no re-validation on grid-select change).
7. Cosmetic: the Checkout Builder Fields box on the WooCommerce order screen sits in the narrow General column
   (labels wrap word by word); product-table action buttons ("Add to Cart / Update / Remove") use unstyled browser
   buttons; the native file input still says "No file chosen" next to the uploaded-file list.
