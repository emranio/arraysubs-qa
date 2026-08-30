# Product Edit Page — Full Regression QA

**Date:** 2026-08-26
**Site:** https://mirror-help.arrayhash.com (`admin`)
**Plugins under test:** `arraysubs` 1.8.12 (free), `arraysubspro` 1.1.3 (pro), WooCommerce 10.9.4
**Method:** live browser testing with Vercel `agent-browser` (sessions: `admin`, `guest`) + WP-CLI/DB verification + code inspection.
**Issue board:** all findings are filed in `qa/issues/` as tasks **#8 – #19**.
**Scope:** the WooCommerce **Product Data** metabox for every product type and every conditional field, validated against the public shop/product pages; then Subscription Box and Subscription Bundle end-to-end (admin → product page → cart → checkout → post-purchase order/subscription/portal).

All pro modules were confirmed active during the run
(`SubscriptionBundle`, `SubscriptionShipping`, `FlexibleRenewalSync`, `StoreCredit`, `CancellationFlow`,
`ProfileFields`, `CheckoutBuilder`, `FixedPeriodMembership`, `FlexibleSubscription`, `EarlyRenew`,
`RedirectProductPage`, `CartInfoEditor`, `MyAccountEditor` → all `1`).

---

## 1. Fixtures created during this run

| ID | Title | Type | Purpose |
|---|---|---|---|
| 33252 | PEQA Simple Sub A | simple + subscription, $20/month | conditional fields, validation, box child |
| 33260 | PEQA Grouped Leak | **grouped** | subscription-meta leak test |
| 33263 | PEQA Box QA | Subscription Box | box A–Z |
| 33277 / 33278 | order / subscription | — | box purchase result |
| 33290 | PEQA Bundle QA | Subscription Bundle | bundle A–Z |
| 33297 / 33298 / 33302 | order / parent sub / child sub | — | bundle purchase result |
| 33313 (+ 33315, 33317) | PEQA Variable Sub QA | variable + subscription | variation fields, validation |
| 33324 | PEQA Box Switch Test | box → simple | product-type switch test |
| user 484 | `peqa-cust` | customer | customer-role checks |

A stray product category **term 33 named `19`** was created by a mis-typed WP-CLI call and could not be
deleted (the environment's command classifier blocks `wp term delete`). It is empty and harmless, but it
should be removed manually. All PEQA products were re-assigned to **Accessories (19)**.

The store-wide *Members Access → "Private member store"* rule (`block_purchase`, scope `all`, exclusions:
products 31340/31347/31357/31363/63 + category 19) was **not modified**. `arraysubs_settings` was backed
up before testing and was never written to.

---

## 2. Conditional-field matrix actually observed

Measured by driving `#product-type` through every option and reading real `offsetParent` visibility.

| Product type | Visible product-data tabs | `Subscription [ArraySubs]` checkbox | General-tab groups |
|---|---|---|---|
| Simple | General, Inventory, **Subscription\***, Shipping, Linked, Attributes, Feature Manager, Product Redirect, Advanced | visible | pricing, **paddle tax** |
| Grouped | Inventory, Shipping, Linked, Attributes, Product Redirect, Advanced | **hidden but still checked/posted** | — |
| External | General, Inventory, Shipping, Linked, Attributes, Product Redirect, Advanced | **hidden but still checked/posted** | external, pricing, **paddle tax** |
| Variable | General, Inventory, Shipping, Linked, Attributes, Variations, Product Redirect, Advanced | visible | **paddle tax** |
| Subscription Box | General, Inventory, Shipping, Linked, Attributes, Product Redirect, Advanced | hidden (still posted, but skipped by the box saver) | box panel, **paddle tax** |
| Subscription Bundle | General, Inventory, Shipping, Linked, Attributes, Product Redirect, Advanced | hidden (still posted, but skipped by the bundle saver) | bundle panel, **paddle tax** |
| Store Credit | General, Product Redirect, Advanced | hidden (still posted) | **paddle tax**, store-credit |

\* the Subscription tab is additionally gated by the checkbox in `productEditPage.js::toggleSubscriptionTab()`
(`isChecked && isSimple`) — correct.

**Working as intended (verified):**

- Subscription panel sections toggle correctly on the `Subscription [ArraySubs]` checkbox.
- `Different Renewal Price` → `.show_if_renewal_price` toggle: OK (simple and per-variation).
- `Shipping Type = one-time` hides `Renewal Shipping Override` (simple): OK.
- `Virtual` / `Downloadable` hide the whole `Subscription Shipping` section (simple and per-variation): OK.
- Flexible Subscription modes: `fixed` → period+interval+length; `flexible_length` → length relabels to
  "Maximum Length"; `full_flexible` → hides Billing Period + Billing Interval and shows the
  "Available Billing Periods" checkboxes. All correct.
- `Billing Period = Lifetime Deal` disables Billing Interval and injects the hidden `value="1"` backup so the
  disabled field still posts. Flexible Renewal Sync correctly hides for lifetime.
- Store Credit: `Credit Amount Type = Customer Enters Amount` hides the fixed `Credit Amount` field. OK.
- Variable products: the header checkbox syncs to every variation, and when it is unchecked every variation
  subscription input has its `name` stripped to `data-name` (31 inputs verified) so no junk posts. OK.
- Product list column indicators render type + `Subscription` + price/schedule. OK.
- Helper Links (direct add-to-cart / one-click checkout) render for simple products and variations. OK.

---

## 3. Findings

### F-01 — HIGH — Subscription meta is written onto Grouped and External products [skip]

`Hooks::addSubscriptionTypeOption()` gives the checkbox `wrapper_class = 'show_if_simple show_if_variable'`,
so WooCommerce only **CSS-hides** it for other types. It stays checked and keeps posting.
`Hooks::saveProductMeta()` has no product-type guard for grouped/external (only box/bundle opt out through
`arraysubs_skip_subscription_product_save`).

*Reproduction (33260):* new product → tick `Subscription [ArraySubs]` → set price 20, period Week → switch
type to **Grouped** → Publish.

*Observed form data at submit time:*
`product-type=grouped`, `_is_subscription=on`, `_regular_price=20`, `_subscription_period=week`,
`_subscription_interval=1`, `_subscription_length=0`, `_trial_length=0`, `_trial_period=day`.

*Observed DB after save:*

```
33260,_is_subscription,yes
33260,_subscription_period,week
33260,_subscription_interval,1
33260,_subscription_length,0
33260,_trial_length,0
33260,_trial_period,day
33260,_signup_fee,0
33260,_arraysubs_subscription_mode,fixed
type=grouped / status=publish / arraysubs_is_subscription_product(33260)=true
```

*Admin products list row:* `PEQA Grouped Leak | Grouped product | Subscription … – / Every week`.

**Impact:** `arraysubs_is_subscription_product()` returns true for a grouped/external product, so every
downstream subscription code path (price html, add-to-cart text, cart meta, checkout subscription creation)
can be entered for a product type the engine does not support.

**Fix direction:** in `saveProductMeta()` bail (and delete the engine meta) whenever the posted
`product-type` is not `simple`/`variable`; additionally have `productEditPage.js` uncheck the box when the
type changes to one where it is not applicable.

**my take: dont touch it. it's not needed.** 

---

### F-02 — HIGH — Subscription validation is completely absent for variable products and variations

`Hooks::isSubscriptionProductSaveRequest()` returns `false` when `product-type === 'variable'`
(`arraysubs/src/Features/SubscriptionProducts/Services/Hooks.php:2050`), and
`Hooks::saveVariationMeta()` performs no validation of its own. The same values are hard-blocked on a
simple product.

*Reproduction (33313 / variation 33317):* Variations tab → clear `Regular price` → set `Billing Interval` to
**99** (the input's own `max` is 12) → **Save changes**.

*Result:* saved silently, no error, no notice.

```
33317,_price,
33317,_is_subscription,yes
33317,_subscription_interval,99
```

(`_regular_price` deleted entirely.)

*Storefront effect:* WooCommerce drops the priceless variation from the `Tier` dropdown — only `Silver`
remains selectable — with no warning anywhere in the admin, so the merchant believes `Gold` is live.

**Fix direction:** run the same rules per variation inside `saveVariationMeta()` (price > 0, interval 1–12,
renewal-price coherence) and surface them via `WC_Admin_Meta_Boxes::add_error()` / the variation AJAX
response.

**note: after fix, deeply test it, on admin and shop page, my account page as well.** 

---

### F-03 — HIGH — A blocked subscription save silently discards the entered settings; pro meta is still written

When validation fails, `saveProductMeta()` returns before writing any core subscription meta, but the pro
savers (Flexible Subscription, Fixed Period Membership, Flexible Renewal Sync) run regardless.

*Reproduction A (new product, 33252 before it had a price):* tick `Subscription [ArraySubs]`, configure the
panel, leave `Regular price` empty, Publish.

*Result:* stays draft with the correct error, **but** the reloaded form shows `Subscription [ArraySubs]`
**unchecked** and every subscription field back at its default — the merchant's whole configuration is gone.
Meanwhile the pro meta *was* persisted:

```
_arraysubs_subscription_mode = fixed
_arraysubs_flexible_periods  = a:1:{i:0;s:5:"month";}
_arraysubs_fixed_end_date_type = recurring_annual
_arraysubs_fixed_end_date      = 01-01
_arraysubs_fixed_end_renewal   = expire
_arraysubs_flex_sync_seg1_end  = 122
_arraysubs_flex_sync_seg2_end  = 243
```

…i.e. a product with pro subscription meta but no `_is_subscription`.

*Reproduction B (existing product 33252):* change Billing Period to `year` and Trial Length to `7`, clear the
price, Update. Only the price error is shown; `_subscription_period` stays `month` and `_trial_length` stays
`0` — the two unrelated edits are dropped with no indication.

**Fix direction:** validate before deciding to skip, and either (a) keep the posted values in the form after a
blocked save, or (b) list every field that was discarded in the error notice. Pro savers should honour the
same block.

**note: after fix, deeply test it, on admin and shop page, my account page as well.** 

---

### F-04 — HIGH — Switching a Subscription Box away from its type leaves a published, priceless, still-"subscription" product

`ProductType::saveProductMeta()` (box) deletes `_arraysubs_subscription_box` as soon as the posted type is
not the box type, and WooCommerce applies the new `product_type` term — both happen even though the core
subscription save is then blocked by validation.

*Reproduction (33324, a copy of the configured box 33263):* open → change type to **Simple** → Publish.

*Errors shown:*

> Subscription products must have a valid regular price greater than zero.
> Paddle catalogue synchronization failed for product #33324. Review the Paddle sync log, then save the product again.

*Resulting state:*

```
type=simple  status=publish  _arraysubs_subscription_box=''  _is_subscription='yes'
_regular_price=''  _price=''   _arraysubs_box_config = 1120 bytes (retained)
is_purchasable() = false
```

The product stays **published** (`preserveProductStatusForInvalidSubscriptionSave()` keeps the existing
status for an existing post), is no longer a box, is flagged as a subscription, and has no price.

**Fix direction:** treat "type changed away from box/bundle" as part of the same validated transaction —
either block the type change too, or clear `_is_subscription` and force the product to `draft` when the
result is unpurchasable.

**note: after fix, deeply test it, on admin and shop page, my account page as well.** 

---

### F-05 — HIGH — A subscription product with no price renders `$0.00 / month` on the storefront [skip]

`Hooks::subscriptionPriceHtml()` — `arraysubs/src/Features/SubscriptionProducts/Services/Hooks.php:828`:

```php
if (trim($price_html) === '') {
    $price_html = wc_price((float) ($data['price'] ?? 0));
}
```

WooCommerce deliberately returns an **empty** price html for a product with no price; the filter replaces it
with `wc_price(0)` and then appends the billing suffix.

*Verified on 33324:*

```
_price=''   get_price()=''   is_purchasable()=false
get_price_html() = "$0.00 / month"
```

Product page (guest): `PEQA Box Switch Test — $0.00 / month`, no add-to-cart button.

**Impact:** unconfigured/broken subscription products advertise a free price in the shop archive, related
products, search and structured data.

**Fix direction:** only substitute a price when `$data['price']` is a real, non-empty value; otherwise return
`$price_html` untouched.

**note: skip it, its intentional**

---

### F-06 — MEDIUM — The Paddle tax category field is shown on every product type

`PaddleTaxCategoryFields::renderProductField()`
(`arraysubs/src/Features/AutomaticPayments/Services/PaddleTaxCategoryFields.php:84`) wraps the field in
`<div class="options_group show_if_arraysubs_paddle_tax_category">`.

`arraysubs_paddle_tax_category` is **not a registered product type**. WooCommerce's
`show_and_hide_panels()` only hides `.show_if_<type>` for types present in
`woocommerce_admin_meta_boxes.product_types` (\`simple, grouped, variable, external,
arraysubs\_subscription\_box, arraysubs\_store\_credit, arraysubs\_subscription\_bundle\`), so this group is never
hidden and never explicitly shown — it is simply always visible.

*Verified visible on:* Simple, External, Variable, Subscription Box, Subscription Bundle, **Store Credit**
(hidden on Grouped only because the whole General tab is hidden there).

**Fix direction:** use a real gate — e.g. `show_if_simple show_if_variable` plus the existing per-variation
field, or a JS toggle driven by the subscription checkbox.

---

### F-07 — MEDIUM — Admin subscription screen shows no box contents and no bundle contents / child link

*Box subscription 33278* (`…/admin.php?page=arraysubs-mainadmin#/subscriptions/detail/33278`): the page
renders Subscription, Customer, Billing, Gateway, Addresses, Subscription Shipping, Order History, Payment
Timeline and Notes — but **nothing** about the box: no contents, no `Gift message`, no `Logo file`, no
freebie, no discount. The data is present in the DB (`_arraysubs_box_contents` on the subscription) and the
customer portal shows all of it.

*Bundle subscription 33298*: same — no `Bundle contents`, and no link to the child subscription **#33302**
even though `_arraysubs_bundle_child_subscriptions = [33302]` is stored.

**Impact:** a merchant fulfilling a box/bundle renewal cannot see what to ship from the subscription screen.

---

### F-08 — MEDIUM — Box: a required product element renders zero cards when its product is not purchasable, and add-to-cart then fails with a misleading message

`views/box-builder.php:161` skips any child where `!is_purchasable() || !is_in_stock() || price <= 0`, but the
element (and its `*` required marker and `data-min="1"`) is still rendered.

*Reproduction (33263 as **guest**, with the store-wide Members Access rule active):*
Element `Base item` (required, product `SLT Box Item A` #12591) renders **0 cards**:

```
[{"type":"product","min":"1","max":"0","cards":0,"txt":"Base item *"}, …]
```

Clicking **Add to Cart** returns only:

> Please select at least 1 item(s) in this step.

There is nothing to select. The box is permanently unbuyable for that visitor, with no explanation.

*Contrast — the Bundle handles the identical situation correctly.* As guest, clicking `Subscribe Now` on
33290 returns:

> "BUNDLE-CHILD-PLAIN" is currently unavailable, so this bundle cannot be purchased.

**Fix direction:** when a `product`/`categories` element resolves to zero renderable children, either render an
explicit "currently unavailable" state and drop the `required`/min constraint, or block the box at the launcher
with a bundle-style message.

---

### F-09 — MEDIUM — `Lifetime Deal` leaves incompatible fields visible and editable

With `Billing Period = Lifetime Deal` on a simple subscription product (measured visibility):

| Field / section | Lifetime | Should be |
|---|---|---|
| Billing Interval | disabled ✔ | disabled |
| **Subscription Length** | **editable** | hidden/disabled — `saveProductMeta()` force-writes `0` anyway |
| **Trial Settings** | **visible** | n/a for a one-time lifetime purchase |
| **Different Renewal Price** | **visible** | n/a — there are no renewals |
| **Fixed Period Membership** | **visible** | contradicts "never expires" |
| Flexible Renewal Sync | hidden ✔ | hidden |

`Subscription Length` is the worst case: a merchant can type `5`, save, and the value is silently rewritten to
`0` by `Hooks.php` (`if (get_post_meta(...'_subscription_period') === 'lifetime') { update_post_meta(..., '_subscription_length', 0); }`).

The same applies per-variation (`applyVariationLifetimeState()` only touches the interval), and `Lifetime Deal`
is still selectable while Flexible Subscription mode is `flexible_length` / `full_flexible`, which is
contradictory.

---

### F-10 — MEDIUM — `Feature Manager [AS]` is unavailable on Variable, Subscription Box and Subscription Bundle products

Tab classes: `arraysubs_features_options arraysubs_features_tab show_if_simple hide_if_grouped hide_if_external`.
Because the class list contains only `show_if_simple`, WooCommerce hides the tab for every other registered
type. Boxes and bundles are full subscription products (they set `_is_subscription = yes`), so membership
features cannot be attached to them. Please confirm whether this is intended; if it is, the box/bundle
configurator should say so.

**NOTE: need to fix it. the issue was not intentional, it is a bug. after fix, properly test visually on browser, it's very complicated, ui, need to qa properly.** 

---

### F-11 — LOW — Box/bundle contents show a unit price next to a quantity, so the numbers do not add up

`CartHooks::appendCartItemDataRows()` formats each row as `%1$s × %2$d — %3$s` where `%3$s` is the **unit**
price (the code comment confirms this is deliberate).

*Cart / checkout / order-email for order 33277:*

```
Box contents:
  SLT Box Item A × 2 — $4.00      <- line total is $8.00
  Jacket × 1 — $25.00
  Shirt × 1 — $20.00 $18.00
Box discount: 10% off (−$5.10)
Total: $45.90
```

`4 + 25 + 18 = 47`, while the real subtotal is `51.00`. The bundle does the same
(`BUNDLE-CHILD-PLAIN × 2 — $5.00`, `BUNDLE-CHILD-SUB × 2 — $10.00`, subtotal `$30.00`).
The bundle **admin** summary table gets this right (`QTY | UNIT PRICE | LINE TOTAL`).

**Fix direction:** print the line total, or label the column, e.g. `SLT Box Item A × 2 — $4.00 each ($8.00)`.

---

### F-12 — LOW — Uploaded box file is a link on the order page but plain text in the customer portal

*Order-received / order details* (via `OrderHooks::linkUploadedInputFile()`):

```html
<strong class="wc-item-meta-label">Logo file:</strong>
<p><a href="…/uploads/arraysubs-box-uploads/pDwqQLRwgUlKSfz3aVkF.png" target="_blank" …>logo.png</a></p>
```

*Customer portal, subscription 33278:*

```html
<tr class="arraysubs-box-portal-row arraysubs-box-portal-row--input">
  <th>Logo file:</th><td>logo.png</td>
</tr>
```

The same filter is not applied in `PortalHooks`, so the customer cannot re-download what they uploaded.

**note: fix it**

---

### F-13 — LOW — A bundle child subscription looks like a standalone $0.00 subscription in the portal list

`My account → Subscriptions` shows:

```
#33302  BUNDLE-CHILD-SUB   Active   26 September, 2026   $0.00 Every month   View
#33298  PEQA Bundle QA     Active   26 September, 2026   $25.00 Every month  View
```

The **detail** page for 33302 is exactly right ("Part of: PEQA Bundle QA (#33298) — Included in a subscription
bundle … Manage subscription bundle" and no independent actions), but the list row has no bundle badge and
invites a confused customer to open a $0.00 subscription.

**note: add badge to solve this issue.**

---

### F-14 — LOW — `Change Plan` is always offered on box/bundle subscriptions and always dead-ends

On subscription 33278 (box), `Change Plan` opens a modal that only says
*"No plan changes available for this subscription."* The button should be hidden when there is nothing to
switch to.

**note: yes, add proper condition but it's a very critical button, so properly QA it on the browser a-z after the fix with variaous conditions.** 

---

### F-15 — LOW — A variable subscription parent shows a price range with no billing suffix

Product page 33313 before a variation is chosen: `$15.00 – $25.00` (shop archive shows the same for
`[QA] Multi-Plan Bundle`: `$1,800.00 – $50,000.00`). Nothing indicates the product is recurring. After
selecting `Silver` the correct `$15.00 / month` appears.
Cause: `subscriptionPriceHtml()` early-returns unless the product `is_type(['simple','variation'])`.

**note: fix it, but after fix, deeply test it, on admin and shop page, my account page as well.** 

---

### F-16 — LOW — Bundle discount input is not clamped to the subtotal

Entering `50` for a fixed discount on a `$30.00` bundle leaves `50` in the field while the preview correctly
clamps: `Subtotal $30.00 / Discount −$30.00 / Bundle total $0.00`. The field should clamp on blur, and a
`$0.00` recurring bundle total should probably be rejected outright.

**note: fix it.**

---

### F-17 — LOW — Variation `Renewal Shipping Override` is always visible [skip]

`views/simple-product-fields.php` wraps it in `.show_if_recurring_shipping` and hides it when
`Shipping Type = one-time`; `views/variation-fields.php` has no such wrapper, so a variation shows a
"Renewal Shipping Override" field that has no effect under one-time shipping.

---

### F-18 — LOW — Hidden-but-posted fields write meaningless meta [skip]

Fields inside a hidden section still submit, so every save persists values the merchant never sees:

- `_renewal_price = 0`, `_renewal_price_after = 1` are written even when `Different Renewal Price` is off
  (verified on 33252).
- `_arraysubs_shipping_type` / `_arraysubs_initial_shipping_override` / `_arraysubs_renewal_shipping_override`
  are written for Virtual/Downloadable products where the section is hidden.
- Box (33263) and Bundle (33290) products end up with Flexible-Subscription and Fixed-Period-Membership meta
  from the hidden subscription panel: `_arraysubs_subscription_mode=fixed`,
  `_arraysubs_flexible_periods=a:1:{i:0;s:5:"month";}`, `_arraysubs_fixed_end_renewal=expire`.
  These are currently inert because the box/bundle own the schedule, but they are a live footgun for anyone
  who later reads them.

---

### F-19 — LOW — `Product Redirect [AS]` tab has no product-type gate

Tab classes are `arraysubs_redirect_options arraysubs_redirect_tab` with no `show_if_*` / `hide_if_*`, so it
renders for every type including **Store Credit** and **Grouped**. Confirm this is intended.

**note: it is supported for for all product types. that's why. so it will visible always.**

---

### F-20 — LOW — Dead code / dead CSS found while testing

- `arraysubspro/src/Features/FixedPeriodMembership/views/simple-product-fields.php:29` adds
  `arraysubs-fixed-end-date-section--hidden`, but **no CSS rule for that modifier exists** anywhere in
  `arraysubs`/`arraysubspro`. Verified in the browser: `getComputedStyle(...).display === "block"` with the
  modifier applied. The section is only ever hidden by the `show_if_subscription` JS.
- `arraysubs/src/Features/SubscriptionProducts/Services/Validation.php::validateBeforeSave()` is never
  hooked — the constructor only registers `handleCustomQuery`. All real validation lives in
  `Hooks::validateProduct()`. The duplicate rules in `Validation.php` are dead and will drift.

**note: deeply and properly investigate befoe cleaning it up. because to clean something if u break any esxistying part, i will scold u hardly.**

---

### F-21 — LOW (security hygiene) — Box uploads are world-readable by URL

Files land in `wp-content/uploads/arraysubs-box-uploads/` with a random 20-character basename and an
`index.html` guard. Directory listing is blocked (404), but the file itself is served to anyone:

```
GET /wp-content/uploads/arraysubs-box-uploads/pDwqQLRwgUlKSfz3aVkF.png   -> 200 (unauthenticated)
```

The upload element also allows **PDF** and **CSV**, so a merchant asking for e.g. an ID document or a customer
list gets security-by-obscurity only. Consider serving these through a signed/authenticated PHP endpoint, as
the Members Access download rules already do.

**note: must fix it, and QA propely.**

---

### F-22 — INFO — Paddle catalogue sync noise

- Saving a subscription product triggers an automatic Paddle sandbox product/price binding
  (`_arraysubs_gateway_paddle_product_binding_sandbox`, `…_price_id_sandbox`, `…_synced_at_sandbox` were all
  written for 33252).
- Saving the priceless product 33324 produced
  \*"Paddle catalogue synchronization failed for product #33324. Review the Paddle sync log, then save the
  product again."\* alongside the price validation error. Expected given the broken state, but it is a second
  scary notice with no link to the log it names.

**note: investigate properly what's going on, and write the findings and issues on a separate .md file here.**

---

## 4. Subscription Box — end-to-end result (33263)

**Admin configurator** — all three wizard steps exercised.

- Schedule: Month / 1 / 0 / Keep signup fees off. Periods offered are `day, week, month, year` (no lifetime) ✔.
- Element types available: product, categories, text, textarea, checkbox, select, multiselect, upload ✔.
- **Cycle filter works**: searching products for a monthly box returns `SLT Box Item A (#12591)` and
  `SLT Box Item B (#12594)` and correctly excludes `SLT Box Sub Item (#12597)` (day/2 subscription).
  Category search reports the eligible count (`Clothing (7 products)`), not the raw count (10) ✔.
- **Freebie search is intentionally unfiltered** and does return cycle-mismatched subscription products
  (`SLT Box Sub Item`). The inline help states this explicitly ("Freebies are not tied to the box billing
  cycle"), so it is by design — but a subscription product handed out as a free box gift is worth a
  deliberate decision, since it is added at $0.00 with no subscription of its own.
- Discounts: `MAX AMOUNT TO CONFIGURE` is required before range points can be added ✔; range slider,
  percent/fixed discount and freebies all persisted correctly.
- Wizard chips (1/2/3) are clickable and navigate ✔.
- Config JSON round-trips through `#arraysubs_box_config_input` and reloads correctly on re-open ✔.

**Saved product meta** (33263) — correct:

```
_arraysubs_subscription_box=yes  _is_subscription=yes
_subscription_period=month _subscription_interval=1 _subscription_length=0
_trial_length=0 _signup_fee=0 _sold_individually=yes
_price='' _regular_price='' _sale_price=''
```

**Storefront** — `Billed: Every month` + `Create Subscription Box` launcher; shop archive shows
`Priced by your selection / month` ✔.

**Builder** (admin context, all children purchasable): 1 product card + 7 category cards.
Selected `SLT Box Item A ×2` ($8), `Jacket ×1` ($25), `Shirt ×1` ($18 on sale from $20) → subtotal **$51.00**,
crossing the $50 boundary:

```
Total: $51.00  $45.90 / month   You save $5.10   Includes free gift: SLT Box Item B
```

Pricing math correct ✔. `Required` validation fired correctly on the `Gift message` text element
("This field is required.") ✔. PNG upload accepted and echoed as `logo.png` ✔.

**Cart** — correct schedule, today's charge, next charge, duration, box contents, freebie, discount, both
customer inputs and the shipping badge (see F-11 for the unit-price wording).

**Checkout** — identical summary; order placed with the saved Stripe card.

**Order 33277 / Subscription 33278**

```
PEQA Box QA (Every month) × 1        $45.90
  ↳ SLT Box Item A × 2               $0.00
  ↳ Jacket × 1                       $0.00
  ↳ Shirt × 1                        $0.00
  ↳ SLT Box Item B (Free gift) × 1   $0.00
Total: $45.90     Subscription #33278 Active, next 26 Sep 2026
```

Child items carry `_arraysubs_box_child=yes`, `_arraysubs_box_parent_key=855007c6…` and the freebie carries
`_arraysubs_box_freebie=yes`. The parent line carries the frozen `_arraysubs_box_contents` snapshot, which is
also copied onto the subscription ✔. Stock was reduced for the stock-managed child (`_reduced_stock` on the
Shirt line) ✔.

**Customer portal** — full box contents, freebie, discount, both inputs, shipping address, early renew,
vacation mode, notes ✔ (see F-12, F-14).

**Not covered:** an actual renewal cycle (Action Scheduler jobs `_renewal_reminder_action_id=31876`,
`_renewal_invoice_action_id=31877`, `_renewal_action_id=31878` were scheduled but not run), and the
box-specific Flexible Renewal Sync segment plan (configured in the UI, then disabled to keep the first
charge unprorated for the pricing check).

---

## 5. Subscription Bundle — end-to-end result (33290)

**Admin configurator** — all three steps exercised.

- Cycle filter works: for a monthly bundle the search returns `BUNDLE-CHILD-PLAIN (#11091) $5.00` and
  `BUNDLE-CHILD-SUB (#11089) Subscription $10.00`, and excludes `BUNDLE-CHILD-WEEKLY (#11093)` ✔.
- Re-picking a product already in the bundle increments its quantity, exactly as the help text says ✔.
- Live subtotal: `2 × $5.00 = $10.00` + `2 × $10.00 = $20.00` → `BUNDLE SUBTOTAL $30.00` ✔.
- Fixed discount `$5` → preview `Subtotal $30.00 / Discount −$5.00 / Bundle total $25.00` ✔ (cap behaviour: F-16).
- **Schedule change guard is excellent:** switching Month → Week re-flags the now-ineligible child as
  *"Product #11089 — Not available on this billing cycle — remove it or change the schedule."*, drops it from
  the subtotal ("Products that could not be priced are left out of this total"), blocks `Continue to Discount`
  and shows *"Some products no longer fit this billing cycle. Remove them, or set the schedule back."* ✔
  (Note: the wizard's own help text claims such products are **removed**; they are flagged instead — the
  behaviour is better than the text, so the text should be corrected.)
- Config round-trips on re-open, and the read-only summary table renders QTY / UNIT PRICE / LINE TOTAL ✔.

**Saved product meta** (33290) — correct, including the catalog mirror:

```
_arraysubs_subscription_bundle=yes  _is_subscription=yes
_price=25   _regular_price=30   _sale_price=''   _sold_individually=yes
_subscription_period=month _subscription_interval=1 _subscription_length=0
```

**Storefront** — `$30.00 → $25.00 / month` with a struck-through "was", a `What's included` table
(`BUNDLE-CHILD-PLAIN × 2 $5.00`, `BUNDLE-CHILD-SUB × 2 $10.00`, `Subtotal $30.00`, `Bundle discount −$5.00`,
`Bundle total $25.00 / month`), quantity forced to a hidden `1` (sold individually) ✔.

**Restriction handling (guest)** — clicking `Subscribe Now` correctly refuses:

> "BUNDLE-CHILD-PLAIN" is currently unavailable, so this bundle cannot be purchased.

**Cart / checkout** — `Previous price: $30.00 / Discounted price: $25.00`, renewals, today's charge, next
charge, duration, bundle contents, bundle discount, `Save $5.00` ✔.

**Order 33297 / Subscriptions 33298 + 33302**

```
PEQA Bundle QA (Every month) × 1          $25.00
  ↳ BUNDLE-CHILD-PLAIN × 2                $0.00
  ↳ BUNDLE-CHILD-SUB (Every month) × 2    $0.00
Related Subscriptions:
  #33298 Active  26 Sep 2026  $25.00 / Every month   (parent)
  #33302 Active  26 Sep 2026  $0.00  / Every month   (child, BUNDLE-CHILD-SUB)
```

Linkage is correct in both directions:
`33298._arraysubs_bundle_child_subscriptions = [33302]`,
`33302._arraysubs_bundle_parent_subscription = 33298`, `33302._arraysubs_bundle_included = yes`,
`33302._quantity = 2`, `33302._recurring_amount = 0` ✔.

**Customer portal** — parent shows the bundle contents and discount; the child page shows the correct
"Included in a subscription bundle … manage everything from the bundle" state with no independent actions ✔
(see F-13 for the list row, F-07 for the admin side).

**Not covered:** a renewal cycle, and editing the bundle contents after purchase to confirm the documented
"recalculated from the current contents on every payment" behaviour on a live subscription.

---

## 6. Other observations (not defects)

- **Page cache interference.** Product pages are cached; a box built as admin initially rendered the
  guest-cached markup. Every storefront assertion in this report was re-taken with a cache-busting query
  string. Anyone re-running this QA must do the same or they will misread F-08.
- **"Connection lost. Saving has been disabled…"** appears on the product edit screen after long idle
  periods. All `admin-ajax.php` requests returned 200 and no console errors were logged; treated as
  environment/heartbeat noise, not a plugin defect.
- **Members Access message text** on the storefront reads *"…Join now to unlock purchasing 2."* — the
  trailing `2` is a QA fixture value stored in `arraysubs_settings.members_access.ecommerce_rules[0].message`,
  not a bug.
- Admins and shop managers bypass Members Access shop rules, as documented — this is why the box/bundle
  purchase flows had to be executed as `admin`.

---

## 7. Suggested priority

1. **F-01** subscription meta on grouped/external
2. **F-02** no validation for variations
3. **F-04** box→simple leaves a broken published product
4. **F-05** `$0.00 / month` for priceless subscription products
5. **F-03** blocked save discards settings / writes partial pro meta
6. **F-08** unfulfillable required box element
7. **F-06** Paddle tax category on every type
8. **F-07** admin subscription screen missing box/bundle contents
9. **F-09** lifetime field gating
10. everything else