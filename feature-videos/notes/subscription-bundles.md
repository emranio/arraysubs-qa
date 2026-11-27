# Subscription bundles — feature video notes

Slug: `subscription-bundles` · Tier: Pro · Scene: `scenes/subscription-bundles.mjs`
Output: `$FV_OUT/subscription-bundles/subscription-bundles.mp4` + `poster.webp`
Customer used for every customer-side mutation: **#1115 Grace Watson** (block 1105-1165, owns sub #30212
Quarterly Learning Pass, which gives no member price on coffee, so bundle prices show clean).

## Store change log (every change, with undo)

| When (UTC) | Change | IDs | Undo |
| --- | --- | --- | --- |
| 2026-11-28 | Created draft product "Morning Ritual Bundle" (simple, draft, image #25751 Coffee Gift Set, category #24 Bundles & Boxes, weight 1.2, short description, marker meta `_fv_subscription_bundles=1`). The scene turns it into a Subscription Bundle on camera and publishes it. | product 32209 | `wp post delete 32209 --force --allow-root` |
