# ArraySubs — Deep Settings & Forms QA Report

**Date:** 2026-08-23
**Environment:** `https://mirror-help.arrayhash.com/wp-admin` (admin)
**Plugins under test:** `arraysubs` (free) + `arraysubspro` (pro)
**Method:** Real end-to-end browser testing via Vercel `agent-browser` (Chrome/CDP), with every save verified against the database through WP-CLI (`wp option get arraysubs_settings`, `arraysubs_profile_fields_config`, `arraysubs_myaccount_menu_config`, post meta, comments).
**Code changes made:** none. This is a test-only pass.

---

## 1. Scope

Every ArraySubs admin settings page, plus the builder/editor screens that contain forms, modals, nested/conditional fields and repeaters. For each surface the following were exercised:

1. Enumerate every control (text, number, textarea, select, multi-select, radio, checkbox, switch, repeater, drag handle).
2. Mutate values.
3. Save.
4. Verify what landed in the database.
5. Reload the page and verify the UI redisplays the saved values.
6. Exercise disable / delete / discard / reorder where present, and confirm those persist too.

### Pages covered

| # | Page / route | Result |
|---|---|---|
| 1 | `#/settings/general` | Pass |
| 2 | `#/settings/toolkit` | Pass |
| 3 | `#/settings/plan-switching` | Pass |
| 4 | `#/settings/refunds` | Pass |
| 5 | `#/settings/skip-pause` | Pass |
| 6 | `#/settings/feature-manager` | Pass (minor: no Discard button) |
| 7 | `#/settings/integrations` | Pass |
| 8 | `#/settings/gateways` (Gateway Logs) | **1 finding** |
| 9 | `#/retention-flow` | Pass (repeater fully exercised) |
| 10 | `#/store-credit/settings` | Pass (minor: no Discard button) |
| 11 | `#/member-styling` | Pass (minor: no Discard button) |
| 12 | `#/checkout-builder` (editor) | Pass |
| 13 | `#/checkout-builder/settings` | Pass |
| 14 | `#/profile-builder/profile-form` | Pass (minor: no delete confirm) |
| 15 | `#/profile-builder/my-account` | **1 finding** |
| 16 | `#/cart-info-editor` | Pass |
| 17 | `#/manage-members` + member detail | Pass |
| 18 | `#/subscriptions` (list) | Pass (2 minor notes) |
| 19 | `#/subscriptions/detail/:id` | Pass (notes add/delete) |
| 20 | `#/easy-setup`, `#/shortcodes`, `#/reports`, `#/license` | Informational only — no editable forms |

---

## 2. Findings

### F-1 — Gateway Logs: "Event Types" filter only lists Stripe events (MEDIUM)

**Where:** ArraySubs → Audits → Gateway Logs (`#/settings/gateways`), *Webhook Event Log* card.

**Problem:** The Event Types dropdown is a fixed Stripe-only list. It does not change with the selected gateway, so Paddle / Mollie / PayPal webhook events cannot be filtered at all.

Offered options (identical regardless of gateway filter):
```
payment_intent.succeeded, payment_intent.payment_failed,
customer.subscription.updated, invoice.payment_succeeded, invoice.payment_failed
```

Event types actually present in Paddle rows:
```
transaction.updated, transaction.completed, transaction.paid, transaction.billed,
transaction.created, subscription.updated, price.created, product.updated, product.created
```

**Repro:**
1. Open Gateway Logs.
2. Set Gateway = **Paddle** → 50 rows, 6 pages of `transaction.*` events.
3. Set Event Type = `payment_intent.succeeded`.

**Expected:** the event list is scoped to event types that exist for the selected gateway (or at least the union across gateways).
**Actual:** 0 rows and the empty state *"No webhook events recorded yet."* — with 254 Paddle events in the table.

**Backend is correct** — verified directly, the REST filter works and is not the cause:

| query | total | pages |
|---|---|---|
| (none) | 590 | 12 |
| `gateway=stripe` | 336 | 7 |
| `gateway=arraysubs_paddle` | 254 | 6 |
| `gateway=arraysubs_paypal` | 0 | 0 |
| `event_type=payment_intent.succeeded` | 148 | 3 |

So the defect is in how the frontend populates the Event Types option list.

---

### F-2 — My Account: a custom menu item is silently discarded when "Linked Content" is empty (MEDIUM)

**Where:** ArraySubs → Profile Builder → My Account (`#/profile-builder/my-account`).

**Problem:** *Add Custom Item* creates a row. If **Linked Content** is left unset, *Save Configuration* discards the whole item — with no validation error, no field highlight, and no toast. The row simply disappears on the next load.

**Repro A (data loss, no warning):**
1. Click **Add Custom Item**, expand it.
2. Fill **Menu Label** = `QA Custom Tab`, **Endpoint Slug** = `qa-custom-tab`. Leave **Linked Content** empty.
3. Click **Save Configuration**.

**Expected:** either the item saves, or a visible required-field error on *Linked Content*.
**Actual:** save reports no problem; `arraysubs_myaccount_menu_config` still holds 11 items and contains no `"type":"custom"` entry. Reloading shows the row gone.

**Repro B (control — proves Linked Content is the gate):** same steps but also pick a Linked Content item, then save:
```json
{"id":"custom-1787510407651","label":"QA Custom Tab","type":"custom","enabled":true,
 "position":11,"content_id":53,"content_type":"product","endpoint":"qa-custom-tab",
 "prevent_direct_access":false}
```
Item count 11 → 12, and it redisplays correctly after reload.

**Impact:** an admin who fills in the label and slug but has not yet chosen the page loses the work on save without ever being told why.

---

### F-3 — Sticky Save/Discard bar overlays page content (LOW, systemic)

The floating action bar is positioned over the scroll content instead of reserving space, so it hides whatever sits behind it. Observed on at least 5 screens:

| Page | Content hidden behind the bar |
|---|---|
| Settings → General | "One Subscription per Product" row |
| Settings → Refunds | "Gateway Refunds" section heading |
| Settings → Feature Manager | "Show in My Account" switch |
| Retention Flow | "Reason 1 - too_expensive" repeater row |
| Profile Builder → My Account | the "Store Credit" menu row |

The hidden controls are still reachable by scrolling, so this is cosmetic, but it is consistent enough to look like a shared layout/CSS issue rather than a per-page one.

---

### F-4 — Modal container is not exposed as a dialog to assistive tech (LOW, a11y)

`.arraysubs-modal` renders without `role="dialog"`, `aria-modal`, or an accessible name:

```json
{"role": null, "ariaModal": null, "ariaLabel": null}
```

Checked on the Checkout Builder *Reset to Defaults* confirmation. Screen readers will not announce it as a modal dialog, and focus is not advertised as trapped. Functionally the modal works (Cancel/confirm both behave correctly).

---

### F-5 — Destructive actions confirm inconsistently (LOW)

| Action | Confirmation? |
|---|---|
| Member Styling → delete rule | Yes — *"Are you sure you want to delete this rule?"* |
| Subscription detail → delete note | Yes — *"Delete Note — Are you sure you want to delete this note?"* |
| Checkout Builder → *Reset* | Yes — *"Reset to WooCommerce defaults? Your custom configuration will be lost."* |
| Profile Builder → **Remove field** | **No** — deletes immediately |
| Checkout Builder → **Remove element** | **No** — deletes immediately |
| Checkout Builder → **Discard** | **No** — discards all unsaved edits immediately |

The two "Remove" cases destroy configuration in one click. Checkout Builder's *Discard* is lower-risk (it only reverts to the last save) but is still an unconfirmed one-click loss of in-progress work.

---

### F-6 — "Discard Changes" is missing on three settings screens (LOW)

Audited across all settings-style pages:

| Page | Save | Discard |
|---|---|---|
| General, Toolkit, Plan Switching, Refunds, Skip & Pause, Integrations, Retention Flow, Cart Info Editor | Yes | Yes |
| **Feature Manager** | Yes | **No** |
| **Store Credit → Settings** | Yes | **No** |
| **Member Styling** | Yes | **No** |

---

### F-7 — Subscriptions list: minor filter/search notes (LOW / INFO)

- **Search requires Enter.** Typing into the search box (`Subscription ID, customer, or product…`) does not filter, even after 8 s. Pressing Enter filters correctly (`slt2-core2` → 1 row, `#31618`). If a live/debounced search is intended, it is not firing; if explicit submit is intended, this is fine as-is.
- **Item count disappears when a filter is applied.** `All → 418 items`, `Active → 27 items`, but after adding the Gateway = Stripe filter the count label renders empty while 19 rows show.

---

### F-8 — Information architecture: Gateway Logs lives under a Settings route (INFO)

The Audits tab bar links *Gateway Logs* to `#/settings/gateways`, while its five sibling tabs use `#/audits/*`. `#/audits/gateway-logs` is not a registered route and logs `No routes matched location "/audits/gateway-logs"` to the console. The nav link itself works; only a hand-typed/bookmarked `#/audits/gateway-logs` breaks.

Also noted: Gateway Logs takes roughly 10 s to first paint, showing *"Loading gateway data…"* for the whole period with no skeleton or spinner.

---

## 3. Verified working

Everything below was mutated, saved, read back from the database, and re-checked after a full page reload.

### Settings → General (11 values)
`multiple_subscriptions.allow_multiple_in_cart`, `multiple_subscriptions.one_per_product`, `button_text.add_to_cart`, `renewals.invoice_before_due_value` (6→9), `renewals.invoice_before_due_unit` (hours→days), `trials.require_payment_method`, `trials.one_trial_per_customer`, `emails.renewal_upcoming.days_before` (3→5), `audits.job_log_retention_days` (30→45), `cancellation.cancel_immediately`, `automatic_payments.allow_auto_renew_toggle`.
**Discard Changes** verified: an edited switch and an edited text field both reverted to their last-saved values.

### Settings → Toolkit
Switches, the redirect single-select (`my_account` → `not_found`), and the **Allowed roles multi-select** — both adding a chip (Editor) and removing one (Shop manager) persisted: `allowed_roles: ["shop_manager"] → ["editor"]`. Enabling *Hide WordPress login page* correctly revealed its dependent redirect select.

### Settings → Plan Switching
`allow_crossgrades` off, `proration_type` → `apply_at_renewal`, `switch_fees` `{upgrade:12, downgrade:7, crossgrade:3}`, `minimum_charge:5`.

### Settings → Refunds
Radio group → `cancellation_behavior: "none"`, `auto_gateway_refund` off, `minimum_amount: 25`.

### Settings → Skip & Pause (8 values)
Enabling *Skip Renewal* correctly revealed the nested `max_cycles` / `cutoff_days` / *Allow Customers to Skip* group. All persisted: `skip_renewal {enabled, max_cycles:6, cutoff_days:4, customer_can_skip:false}`, `pause_subscription {max_duration_days:60, max_pauses_per_subscription:5, min_days_between_pauses:14, require_reason:false, access_during_pause:"limited"}`.

### Settings → Feature Manager
Text field, radio group (`aggregation_mode` → `combine`), and the comparison switch.

### Settings → Integrations
Enabling LearnPress revealed its conditional message textarea; both the switches and the textarea content persisted.

### Gateway Logs
Gateway filter (all / stripe / paddle / paypal), pagination (Page 1 → 2 → 1 of 12), and the empty state (*"No webhook events recorded yet."*) all behave correctly. Only the event-type option list is wrong (F-1).

### Retention Flow — repeater exercised in full
- **Add** row → live title update (`Reason 9 - qa_probe_reason`).
- **Delete** row (`shipping_issues`) → remaining rows re-index correctly.
- **Reorder** via the dnd-kit keyboard sensor (focus handle → Space → ArrowUp ×2 → Space) → order changed, saved, and survived reload.
- The Discount Offer *"Show for these reasons"* multi-select reactively reflects live repeater edits — the deleted reason disappears and the newly added one appears without a save.
- Saved payload verified: 8 reasons in the new order, `trigger_reasons: ["too_expensive","qa_probe_reason"]`, `discount_percent:35`, `discount_cycles:6`, `headline:"QA Headline"`.

### Checkout Builder (editor)
Add element (keyboard drag from the palette into the active step), the *Edit Element* panel (label, key, placeholder, help text, Required switch, CSS class), Add Step, step switching, Remove element, **Discard** (correctly restored 5 elements and removed the unsaved step), and the **Reset** confirmation modal (Cancel leaves the layout intact). Saved field verified in `checkout_builder.multistep.steps[0].fields`.
Key-slug behaviour is correct: the key auto-derives from the label, and a key typed *after* the label is preserved (`_arraysubs_cf_custom_key`).

### Checkout Builder → Settings
`uploads_max_size` 5→12 and `copy_to_renewal` off.

### Profile Builder → Profile Form
Add field, expand, label/key/placeholder/help text, type select (`text` → `textarea`), **Required** checkbox, **Move up** reorder (positions swapped in the DB), **Disable field**, and **Remove field** — all persisted to `arraysubs_profile_fields_config`.

### Profile Builder → My Account
Visibility toggle (Disable/Enable) and Add Custom Item *with* Linked Content both persist and redisplay. (Without Linked Content — see F-2.)

### Member Styling
Add rule → name, body classes, custom CSS, *Also apply in wp-admin* → saved to `member_styling.rules[0]`. Rule disable persisted (`enabled:false`). Delete flow verified both ways: **Cancel** keeps the rule, **Delete** removes it, and the removal persisted (`rules: 0`).

### Cart Info Editor
`hide_first_billing_cycle_info` and `hide_duration_info` on, middle switch untouched — exact match in the DB.

### Store Credit → Settings
Five numeric fields (`min_order_amount:15`, `expiration_days:90`, `min_purchase_amount:25`, `max_purchase_amount:900`, `default_purchase_amount:75`) and `auto_apply_to_renewals` off.

### Manage Members
Member search returns matching users with subscription counts; clicking a row routes to `#/manage-members/:id`; the detail screen renders totals, orders, subscriptions, store credit and refunds. **Edit Details** switches to an inline edit form (22 fields) — First Name edit saved to user meta and read back correctly.

### Subscription detail (`#/subscriptions/detail/31645`)
Add note through the TinyMCE editor (POST 201, note rendered, survives reload) and delete note (confirmation dialog → DELETE 200 → row removed from the list immediately, and still gone after reload).

### Subscriptions list
Status tabs (`All 418` → `Active 27`), gateway filter (+Stripe → 19 rows), search on Enter (1 row), and pagination.

---

## 4. Checked and cleared (not bugs)

These looked like defects during testing and were chased down to a non-plugin cause. Recording them so they are not re-investigated.

| Symptom | Actual cause |
|---|---|
| "One Trial per Customer" switch would not toggle | Stale element ref after a sibling toggle re-rendered the page. Toggles fine when addressed by its stable id (`#trials-one_trial_per_customer`). |
| Gateway filter appeared to ignore "PayPal" and "All" | Test drove the wrong page instance and used option values that do not exist (`paddle` vs `arraysubs_paddle`). Filters are correct. |
| Checkout Builder *Reset* looked inert | The confirm modal exists but is not matched by `[role=dialog]` — see F-4. Reset itself is fine. |
| Repeater drag-to-reorder did not respond | dnd-kit's PointerSensor needs trusted pointer events with capture; synthetic events cannot drive it. Reorder was proven working through the KeyboardSensor, and the code wires `DndContext` + `PointerSensor` + `SortableContext` + `arrayMove` correctly. |
| Checkout Builder element settings appeared not to save | The Save button was clicked through a script-side text match that hit the wrong node. Saving through the real button persists every field. |
| Subscription note would not accept text | The note editor is TinyMCE; the visible surface is an iframe and the `textarea` is only its backing store. Setting content through the TinyMCE API works. |
| Deleted note appeared to linger in the list | Not reproducible on a clean re-test — the list drops the row within ~2 s of confirming. |
| "No search input" on the subscriptions list | There is one (`input[type=search]`); its placeholder just does not contain the word "search". |

---

## 5. Test-data restoration

All mutations made during this pass have been reverted. Every ArraySubs option was snapshotted before testing and restored afterwards:

- `arraysubs_settings` — **0 keys drifted** from the pre-test baseline (verified by a recursive flatten-and-diff of all 25 top-level sections).
- `arraysubs_myaccount_menu_config` — back to 11 items, 0 custom.
- `arraysubs_profile_fields_config` — back to 1 field (`company_name`).
- User 474 `first_name` restored to `SLT`.
- QA notes added to subscription 31645 were deleted.
- The `member_styling` and `cart_info` sections created during testing were removed with the restore.

---

## 6. Automation limitations

Two things could not be driven by the browser automation and were verified by other means; a human should still click them once:

1. **Pointer drag-and-drop** (repeater rows, Checkout Builder palette → canvas, My Account menu reorder). dnd-kit's PointerSensor requires trusted events. Reordering was proven through the keyboard sensor instead, and the palette-to-canvas insert was proven the same way.
2. **License → Remove License** was deliberately not exercised, since deactivating the licence on this environment would disable pro features mid-test.
