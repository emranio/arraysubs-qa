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

## Settings window (shared setting)

- `arraysubs_settings['feature_manager']['aggregation_mode']`: before = `per_subscription`. Chapter 7 switches it to `combine` on camera (Save Settings) for about 30-60 seconds per take, then restores `per_subscription` in a `finally` block; `setup`/`teardown` also force `per_subscription`. Recorded twice (04:24-04:35 and 04:54-05:11 local, 2026-11-28; each window only the middle part of the chapter). Verified after the last take: `per_subscription`.
- No other settings changed.

## Final usage state left in the store

Luna Anderson (842): #29307 Monthly Downloads 9/15, Team Seats 3/5; #30240 Monthly Downloads 4/10, Team Seats 1/1 (December allowances reset on 1 December). Undo command is in the table above.

## Known weak spots

- Combine mode shows "Support Level: Community" for Luna (Priority + Community). Documented behavior: text keeps the longer wording (user guide, combine-features-across-subscriptions).
- The Access Restriction rule on Team Workspace is not shown in the admin (the block editor sidebar renders cramped inside the 1440 px stage); chapter 8 proves the rule only from the storefront side.

## Output

- `$FV_OUT/feature-manager/feature-manager.mp4`: 1920x1080, 30 fps, no audio, 239.9 s, 8 chapters, all segments native 1920x1080 (chapters 1-3 were re-recorded because an earlier take rendered at 1440x810).
- `poster.webp`: chapter 3, What's Included box ringed with "Read live from the plan".
- Recorded chapter by chapter with retries (`scratchpad/fvscripts/rec.sh`) because the store was saturated (load average ~45, page loads over 60 s).

## Revision 2 (2026-11-28, after review): what changed

### New store records and changes

| When | Change | IDs | Undo |
| --- | --- | --- | --- |
| 2026-11-28 ~06:06 local | New page **Team Workspace** (slug `team-workspace`, published, author admin, meta `_fv_feature_video=feature-manager`) with real member content (welcome line + 3 bullets) and a page restriction saved through `PostRestrictionConfig::save()` in the current format: login required, Plan Feature `Team Seats`, "At least one plan provides at least" 5, custom denied message "The Team Workspace opens on plans with 5 or more team seats. Compare plans." Replaces the seeded page 9226 (legacy restriction format, placeholder copy) in chapter 8. Page 9226 is untouched. | page 32765 | `wp post delete 32765 --force --allow-root` |

### Request-scoped overrides (nothing written to the database)

The scene's `setup` adds the cookie `fv_clip=fv-feature-manager` after every `v.as()`, so the existing
`mu-plugins/zz-fv-clips.php` applies `clips/overrides/fv-feature-manager.json` to the recording browser's
requests only (other agents and visitors are unaffected):

- Post meta: Plus Membership (6711) `_arraysubs_features` reads with Support Level **Email** instead of Community. Real logic then runs on it: Combine keeps the longer text, so Luna's combined Support Level is **Priority** (was "Community", which read as a downgrade). Chapter 5's Access Scope chip shows "Support Level: Email".
- Text: header nav items Checkout / Checkout Classic / Cart Page removed; footer theme filler Authors / Patterns / Themes shown as Contact / Gift Cards / Shipping; SKUs `DEMO-260927-VARIABLE-PLAN` / `-TIER-BASIC` / `-TIER-PROFESSIONAL` shown as `MEMBERSHIP-TIER` / `-BASIC` / `-PRO`; `luna.anderson@example.com` shown as `luna.anderson@brightside.market`.
- A small stylesheet (via `</head>`): My Account nav puts Log out last (CSS `order`), hides the "Need to modify or cancel this subscription?" notice, the product editor permalink box, catalog visibility row, ArraySubs helper-link rows (direct add-to-cart / one-click checkout URLs) and the "Active Subscriptions" warning box.

Undo: delete `clips/overrides/fv-feature-manager.json` (only read when the cookie is present).

### Scene changes (all chapters re-recorded at native 1920x1080)

- Ch1: display-switch label moved beside the four switches (no longer covers the guide note).
- Ch2: Value Type column ringed as a whole with the label above; modal open / Add Feature / row save / modal close use hard cuts (no ghosted double images); translate icon called out; refill dropdown drawn open (display-only) to show its six choices before Monthly is picked; Enabled switch called out ("Switch a row off to hide it without deleting it"); "Now second in the list" label sits in the empty Value space.
- Ch3: labels point at the values from the empty middle of each row.
- Ch4: editor opens straight on the Variations tab behind the chapter card (no permalink, description, SKU or Active Subscriptions box on camera), first caption right away; storefront scrolled 150 px so the caption no longer covers the tier price.
- Ch5: a PHP code card (Developer API `Features::forSubscription()->consume()`) is shown while the same call runs off camera, then the page reloads with 9 / 15.
- Ch6: labels moved off the column headers; no zoom crop; caption cleared before Manage Members; Show Features is clicked and the log shown.
- Ch7: combined Support Level is Priority (override above).
- Ch8: rebuilt. Admin page editor (Document-Isolation-Policy header stripped for the recording browser, as in content-gating) shows the Access Restriction panel and the Plan Feature rule (Team Seats, one plan at least 5). John's Memberships card (Team Seats: 1) then his locked view with the new message; Luna's card (Team Seats: 5) then her unlocked page. Captions are cleared before every switch of viewer.

### Revision 2: settings window, final state, output

- Shared setting `arraysubs_settings['feature_manager']['aggregation_mode']` was switched to `combine` on camera once more (chapter 7 take at 06:33-06:37 local, 2026-11-28; the Combine window is only the middle of that chapter) and restored to `per_subscription` in the chapter's `finally` and in teardown. Verified after the last take: `per_subscription`. No other settings changed.
- Usage left on Luna (842): #29307 Monthly Downloads 9 / 15, Team Seats 3 / 5; #30240 Monthly Downloads 4 / 10, Team Seats 1 / 1 (undo command in the first table). Product 32093 keeps the 5-row list chapter 2 saves on camera.
- Recording ran in two parallel tracks where safe (chapters that touch usage or the shared mode, 5/6/7, always ran one at a time). Scratch helper: `scratchpad/fm2/rec.sh`.
- Output: `$FV_OUT/feature-manager/feature-manager.mp4`, 273.8 s, 1920x1080, 30 fps, no audio; every segment re-recorded natively at 1920x1080 (seg-01 23.6 s, seg-02 61.5, seg-03 18.5, seg-04 29.6, seg-05 31.7, seg-06 30.0, seg-07 29.1, seg-08 49.8). `poster.webp`: chapter 3, What's Included ringed with "Read live from the plan".

### Product observations (not fixed: plugin code is off limits)

- My Account menu order: the stored `arraysubs_myaccount_menu_config` on this store lists `features` after `customer-logout`, so "My Features" renders below "Log out". `MyAccountEditor\Services\Hooks::applyMenuConfig()` also appends any item missing from a saved config at the very end, i.e. after Log out, so a module switched on after the menu was saved lands below Log out. Worked around in the recording with a CSS `order` rule (override file).

### Known weak spots (revision 2)

- Plus Membership's Support Level is shown as "Email" through a request-scoped meta override (the stored value is "Community"); this keeps the Combine table from reading as a downgrade without changing a product other recordings use.
- "Every member is updated at once" is stated in chapter 2 but not shown reaching an existing subscriber (product 32093 has no subscribers).
- Not covered: Unlimited values, the Highest value result, box/bundle and one-time products, "Clearing the choice brings the default back" (Membership Tier has no parent list), Login as Customer, settings export/import, the effect of a display switch.
- The refill dropdown is drawn open with a display-only `size` attribute (headless capture cannot show a native dropdown).
