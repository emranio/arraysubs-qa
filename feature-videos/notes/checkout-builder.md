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

## Storyboard

Intro card: "Checkout Builder" (Pro chip). Outro: "Ask at checkout, not after."

1. **Admin: Your checkout on one canvas.** Builder full screen (pre layout). Step tabs (4 steps), "Your dog"
   step, zoom on cards (red asterisk = required, eye = conditional), Elements palette (28 elements, scrolled
   through Basics and Others), toolbar (Undo, Redo, Reset, Discard, Save): nothing is live until Save.
2. **Admin: Drag in a question.** Real pointer drag of the File Upload tile onto the canvas (blue drop line),
   settings open, type label "Photo of your dog" + help text, zoom on settings, type settings (size, count,
   types), Save -> "Configuration saved!" (writes only `fv_cb_overlay`).
3. **Admin: Ask only what applies.** Final layout. Open "Which ingredients should we avoid?", scroll to
   Show When: Field = Any food allergies?, Operator = Is, Value = yes. Open the Operator list (5 operators),
   AND/OR, Back, eye icon on the card.
4. **Settings: Match your store.** Settings tab: Copy Custom Fields to Renewal Orders (on), Color Scheme
   (Custom, purple), Container Width / Border Radius / Spacing, Navigation Position + Step Indicator.
5. **Checkout: Short steps, checked.** Customer 703 Willow James, classic checkout page `/checkout/` with
   Grain-Free Dog Food Medium bag ($45 / month). Step bar, step 1 billing, Next, step 2: Next with empty
   required answers -> notice, pick Medium + Yes -> follow-up appears (poster), Chicken + Wheat, product table
   adds Peanut Butter Biscuits, photo upload (file chooser).
6. **Checkout: Delivery, then payment.** Step 3: Wednesday, first delivery date, delivery notes. Step 4:
   coupon box + order summary, Place order -> order received with the "Additional Information" answers.
7. **Admin: Every answer, kept.** WooCommerce order screen "Checkout Builder Fields" box; subscription
   detail "Custom Fields" panel (Selections, Dates & times, Files); subscription edit screen.
8. **My Account: Customers see their answers.** View subscription page rows.
9. **Checkout: On the Checkout block too.** `/checkout-2/` (block checkout): fields in Additional order
   information, Show When on "Yes"; steps/columns/design are classic-only.
