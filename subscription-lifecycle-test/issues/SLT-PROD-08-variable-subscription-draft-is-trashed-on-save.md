# SLT-PROD-08: variable subscription draft and generated variations land in trash during setup

- Status: open
- Severity: high
- Date found: 2026-08-06
- QA progress task ID / stage: card `71` / `SLT-PROD-08` / D04
- QA plan task file: `/home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public/wp-content/plugins/qa/subscription-lifecycle-test/kanban/tasks/071-slt-prod-08-create-slt-variable-daily-with-four.md`

## Task / stage / plan

- QA progress task: `#71` / `SLT-PROD-08`
- Stage: `D04`
- Plan path: `qa/subscription-lifecycle-test/kanban/tasks/071-slt-prod-08-create-slt-variable-daily-with-four.md`

## Affected IDs

- Affected subscription ID(s): N/A
- Affected order ID(s): N/A

## Affected user / customer context

- Affected WordPress user/customer ID(s): N/A
- Browser / user context: `agent-browser --session admin-SLT-PROD-08`, logged in as `admin`

## Exact routes / browser context

- Browser / user context: `agent-browser --session admin-SLT-PROD-08`, logged in as `admin`
- Exact admin route(s):
  - `https://mirror-help.arrayhash.com/wp-admin/post-new.php?post_type=product`
  - `https://mirror-help.arrayhash.com/wp-admin/post.php?post=13012&action=edit`

## Reproduction

1. Open the Add Product admin screen at `post-new.php?post_type=product`.
2. Enter:
   - title `SLT Variable Daily`
   - description `SLT window product. Variable subscription, four daily tiers. Delete on 2026-08-15.`
3. Change the product type to `Variable product`.
4. Tick the header checkbox `Subscription [ArraySubs]`.
5. In Attributes, add one custom attribute:
   - name `SLT Tier`
   - values `Starter | Plus | Trialist | Zero Probe`
   - `Visible on the product page` = checked
   - `Used for variations` = checked
6. Save the attribute.
7. Attempt to generate the variations and/or save the draft state.
8. Reopen the exact product edit URL for the saved post ID.

## Expected result

- The parent product remains editable as a draft or published product.
- The product stays `variable`.
- The four intended variations remain attached and editable, not trashed.
- The admin edit URL loads the normal WooCommerce product editor so the remaining variation pricing/subscription fields can be completed.

## Actual result

- The product save path produced parent post `13012` titled `SLT Variable Daily`, but its status became `trash` immediately.
- The edit slug became `__trashed`.
- Four generated variations were created as child posts, but all four also landed in `trash`:
  - `13013` Starter
  - `13015` Plus
  - `13017` Trialist
  - `13019` Zero Probe
- Reopening `post.php?post=13012&action=edit` returns a WordPress admin error instead of the product editor.

## Concrete proof

- Screenshot: `/home/server-manager/slt-evidence/SLT-PROD-08-07-edit-error.png`
- Attribute setup screenshot: `/home/server-manager/slt-evidence/SLT-PROD-08-01-attributes.png`
- Exact post record:
  - `wp post get 13012 --fields=ID,post_title,post_status,post_name,post_type,post_date,post_modified --allow-root`
  - result: parent product `13012`, title `SLT Variable Daily`, status `trash`, slug `__trashed`, type `product`
- Exact child-variation state:
  - `wp db query "SELECT ID, post_parent, post_title, post_status, post_type FROM wp_posts WHERE post_parent = 13012 OR ID = 13012 ORDER BY ID;" --allow-root`
  - result: parent plus all four variations exist and all are `trash`
- Exact product meta confirms the setup did partially persist before trashing:
  - `_is_subscription=yes`
  - `_product_attributes` contains the custom attribute `SLT Tier` with values `Starter | Plus | Trialist | Zero Probe`
- Mail proof:
  - baseline `M0=1YCZEsnunjb685g8ZYqhWx`
  - `mailpit-agent list 80 | rg 'SLT Variable Daily|13012|SLT-PROD-08'` returned no task-attributable mail

## Known scope / counterexamples

- Existing variable product counterexample: `SLT Flex Variable Daily` (`12385`) is present and listed normally in WooCommerce Products, so variable products as a class are not universally broken in this environment.
- This failure was hit on the new `SLT Variable Daily` creation flow after the custom variable-subscription setup began; it blocked completion of the card’s later variation pricing and storefront verification steps.
