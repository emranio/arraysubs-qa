# Member details Free-only baseline — 2026-10-23

QA issue: #85. Active QA task ID/scheduled day and plan reference: N/A (targeted pre-fix reproduction). This baseline does not reuse an earlier QA cycle.

## Result

Confirmed twice: with only ArraySubs Pro temporarily deactivated on the local test site, the customer name in subscription details opens Manage Members but displays **Failed to load member data**. The actual browser request `GET http://localhost:10013/wp-json/arraysubs/v1/members/224` returns HTTP **404**. The subscription details request returns HTTP 200.

## Environment and fixtures

- Site: `http://localhost:10013`, real Local WordPress site.
- Browser: isolated agent-browser session `member-free-qa`, administrator `admin`, authenticated through the documented `http://localhost:10013/wp-admin/?localwp_auto_login=1` route.
- ArraySubs 2.0.9; ArraySubs Pro 1.2.8 temporarily inactive for this baseline; WooCommerce 11.1.2; WordPress UI reports 7.1.3.
- Existing subscription: **#8326**, **Demo · Lifetime Resource Library**, active lifetime subscription.
- Existing customer: **#224**, **Finley Sullivan**, login `demo260927_220`, email `demo.260927.220@example.test`, roles `customer`, `demo_member`, `demo_premium`. No customer browser session used.
- Order IDs: N/A for the reproduced navigation failure; no order action was performed.
- No fixtures, settings, profiles, subscriptions, orders, or product source files were edited.

## Reproduction

1. With Pro inactive, open `http://localhost:10013/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions`. See [step 1](step-1-subscriptions.png).
2. Click **#8326 Demo · Lifetime Resource Library** to reach `#/subscriptions/detail/8326`. The page and Customer Information render successfully. See [step 2](step-2-subscription-8326.png).
3. Click **Finley Sullivan** in Customer Information. The link target is `#/manage-members/224`. Manage Members displays **Failed to load member data** and never renders the customer profile. See [step 3](step-3-member-error.png) and [snapshot](member-error-snapshot.txt).
4. Browser request evidence shows the detail endpoint succeeds while the member endpoint returns 404. See [focused requests](network-member-224.txt) and [complete request inventory](network-repro.txt). Two identical calls are visible in the development React runtime.

Repro video: [subscription customer link](subscription-customer-link.webm).

Expected: a registered customer's member details page loads using Free alone. Actual: missing member endpoint and error toast.

## Restoration

ArraySubs Pro was reactivated immediately after capturing baseline evidence. Both the exact `active_plugins` array and the `arraysubs_settings.modules` value match their initial snapshots; see [restoration check](restoration-check.json), [initial state](state-initial.json), and [restored state](state-restored.json). Full plugin inventories are in `plugins-initial.json` and `plugins-restored.json`.

The authenticated isolated browser session remains available for the lead's post-fix verification. No remote site, global browser session, or unrelated checkout edits were touched.
