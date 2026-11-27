# Feature Manager video (slug `feature-manager`)

Tier on the website page: **Pro** (`catalogItem('feature-manager')`, FAQ: "Feature Manager needs ArraySubs Pro").
Scene: `scenes/feature-manager.mjs`. Output: `$FV_OUT/feature-manager/feature-manager.mp4` + `poster.webp`.

## Store changes (all reversible)

| When | Change | IDs | Undo |
| --- | --- | --- | --- |
| 2026-11-28 | Recorded demo usage (Developer API `Features::forSubscription()->set()`, reason "feature video demo usage") | Luna Anderson (user 842): sub #29307 Monthly Downloads = 8, Team Seats = 3; sub #30240 Monthly Downloads = 4, Team Seats = 1. The scene's setup resets these values before each recording; chapter 5 records one more download on camera (8 -> 9) | `wp eval 'foreach([29307,30240] as $s){ foreach(["Monthly Downloads","Team Seats"] as $f) delete_post_meta($s, arraysubs_get_feature_usage_meta_key($f)); }' --allow-root` |
| 2026-11-28 | New hidden demo product **Creative Studio Membership** (simple subscription, $29 / month, no signup fee, catalog visibility hidden, image 25764, category Demo Member Plans, meta `_fv_feature_video=feature-manager`) | product 32093, slug `creative-studio-membership` | `wp post delete 32093 --force --allow-root` |
| every recording | Scene setup resets product 32093 `_arraysubs_features` to 4 rows (Team Seats 5, Masterclasses Yes, Private Coaching No, Support Level Priority); chapter 2 adds Monthly Downloads 20 / Monthly on camera, moves it to row 2 and clicks Update | product 32093 | delete the product (above) |

Shared settings: see the "Settings window" section below (filled in after recording).

## Display-only renames (`clean-map.d/feature-manager.json`)

Only for the recording browser (`fv_clean=1` cookie). Real data is unchanged.

## Customers used

- Luna Anderson (842, in block 800-860): Membership Tier - Professional (#29307) + Plus Membership (#30240). Hero customer for My Features, Access Scope, Feature Log and the Combine demo.
- John Hamilton (805, block): Starter Membership (#29079), 1 team seat. Shown locked out of Team Workspace (read only).

## Storyboard (8 chapters, target ~4 min)

Intro card: "Feature Manager", Pro chip.

1. **Settings** (`#/settings/feature-manager`): Display Settings card (4 switches: product page, My Account, usage in My Account, usage in admin), built-in Product setup guide.
2. **Admin, product editor** (product 32093): Feature Manager [AS] tab, preview table (4 perks), Manage Features modal, Value Type column (admin only), Add Feature: Monthly Downloads / Number / 20 / Reset allowance Monthly, confirm, Move up x3, Save list, Update product.
3. **Storefront**: Creative Studio Membership product page, What's Included box (20 / Monthly, cross for No). Poster frame.
4. **Tiers**: Membership Tier (6722) Variations tab, Basic variation's own Feature Manager list; storefront tier select Basic -> Professional swaps the box.
5. **My Account** (Luna 842): My Features, one table per plan, usage + reset date; one download recorded off camera through the Developer API (8 -> 9 / 15) and the page reloads; Memberships cards with Access Scope.
6. **Admin**: subscription #29307 detail -> Feature Log link -> Feature Log (Type column admin only, usage); Manage Members profile -> Show Features.
7. **Settings, Combine**: Aggregation Settings -> Combine -> Save Settings; Luna's My Features shows one Combined Features table (Team Seats 6, Monthly Downloads 25). Setting restored to Per Subscription right after the chapter (finally block) and in teardown.
8. **Access**: Team Workspace page (9226, rule Team Seats >= 5, aggregation max): John Hamilton (Starter, 1 seat) sees the lock message; Luna (Professional, 5 seats) sees the page.

Outro card.

## Other store actions

- 2026-11-28 ~03:16 local (21:16 UTC): `FV_PRO=1 wp rewrite flush --allow-root`. ArraySubs Pro had been deactivated in the DB by someone outside the workflow (21:07:48 UTC), and the rewrite rules lost the Pro My Account endpoints (`as-features`, `as-store-credit`), so `/my-account/as-features/` was a 404 for the recording browser. The flush (with Pro loaded through the mu-plugin) restored them. Coordinator was told; the mu-plugin now self-heals this. Nothing to undo.
- Chapter 4 replaces the seeded placeholder description sentence ("... demonstration store.") on the Membership Tier product page inside the recording browser DOM only (`v.eval`). No data changed.
