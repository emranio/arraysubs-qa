# Short website clips: store-change log (2026-11-28)

Short 16:10 clips and stills for the features hub, the inner feature pages and the homepage, recorded with `clip.mjs` (scenes in `clips/`) and copied to web-content with `publish-clips.mjs`.

| Media key | Scene | Used on |
| --- | --- | --- |
| `my-account/plan-switch` (video) | `clips/plan-switching.mjs` | `/features/plan-switching/` hero and chapter, features hub |
| `plan-switching/paths`, `plan-switching/settings`, `plan-switching/pending-switch` (images) | `clips/plan-switching.mjs` | `/features/plan-switching/` |
| `checkout/mixed-cart` (video) | `clips/mixed-cart.mjs` | features hub, `/features/flexible-billing/#mixed-checkout` |
| `cancellation/feedback` (video), `cancellation/feedback-details` (image) | `clips/cancellation-feedback.mjs` | features hub, `/features/cancellation-flow/#cancellation-feedback` |

## How overrides work (no shared setting changes)

- `wp-content/mu-plugins/zz-fv-clips.php` (temporary) acts only on requests with the `fv_clip=<scene>` cookie, which only `clip.mjs` sets. It reads `clips/overrides/<scene>.json` (written by `clip.mjs` from the scene's `overrides`, deleted after the run) and overrides options, `arraysubs_settings` keys, single post-meta reads, product prices and display text for those requests only. A guard keeps `arraysubs_settings` unchanged if a save happens in such a request. Undo: delete the file.
- `lib/director.mjs`: `FV_W`, `FV_H`, `FV_DPR` override the stage size (defaults unchanged), and `FV_CAPTURE=pw` captures with Playwright's screenshot, which honours the device scale factor. The default raw CDP capture returns CSS-pixel frames in this headless shell, so 1440x810 stages come out 1440x810, not 1920x1080.

Overrides used: plan switching (crossgrades on; Premium Membership 6712 upgrade path to Team Membership 26179, downgrade path to Plus 6711, crossgrade path to QA product #5908 shown as "Creator Membership" at $69.00 every month); mixed cart (One Click Checkout set to Default); cancellation feedback (customer cancellation allowed, cancel at end of period); all three: currency position left.

## Before the overrides existed (restored)

Early scouting changed shared state directly for a few minutes, then restored it (all verified afterwards):

- `arraysubs_settings[plan_switching][allow_crossgrades]` false -> true -> false.
- `_arraysubs_crossgrade_products` on 6711 added, then deleted.
- Product #5908 `_subscription_interval` 2 -> 1 -> 2.
- `woocommerce_currency_pos` right -> left -> right.

## Records that stay

- Two bacs orders for demo customer 1221 (Priya Raman), $55.00 each, status On hold: order **32157** -> subscriptions **32158**, **32161**; order **32172** -> subscriptions **32173**, **32176** (Weekly Coffee Delivery and Fortnightly Tea Delivery, both Pending). Order emails went to the local mail catcher. Later retakes reuse order 32172's order-received page and place no order.
- Subscription **31605** (Emma Lawson, Annual Library Pass): cancellation scheduled several times with reason Other and the text "Moving abroad for six months. I will be back in the spring.", undone each time; it is Active with no scheduled cancellation. Its subscription notes, the cancellation and undo emails (mail catcher) and Pro retention log rows stay. Meta `_cancellation_reason_at_offer=other` and `_refund_cancellation_behavior=none` stay.
- Customer 1221's cart is emptied after each run.
