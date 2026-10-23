# Native billing editor follow-up

Verified October 24, 2026 against `http://localhost:10013` using the isolated `native-billing` agent-browser session. This follow-up supersedes the saved-plan modal behavior in the original report: existing plans now use WooCommerce; Add new plan retains the creation wizard.

| Check | Observation |
| --- | --- |
| Real Plans row action | Clicking Edit subscription billings for product9624 navigates to `post.php?post=9624&action=edit&arraysubs_from=plans&arraysubs_edit=billing`. No saved-plan modal opens. |
| Simple billing | Subscription Billings [AS] is active. Product data starts80.6px from the viewport top, below the fixed admin headers. |
| Variable product6722 | Variations opens with both existing variations available; Product data starts80.6px from the top. |
| Box5900 / bundle5905 | General opens with each native configuration button and billing summary visible; Product data starts80.6px from the top. |
| Regular Edit product | The editor opens at the top on General, without the billing query flag. Back to plans returns to the catalog. |
| Collapsed panel | Collapsed Product data on the temporary fixture, then reloaded the billing URL. It reopened automatically with Subscription Billings active and correct scrolling. |
| Creation regression | Completed all eight simple-plan creation steps, confirmation and success screen. One published, virtual,29/month product20666 appeared in the catalog. The success Edit product link retains the Plans origin. |
| Native save | Changed fixture20666's billing interval1→2 in the native billing panel and clicked Update. WordPress displayed Product updated; persisted `_subscription_interval` is2. The redirect retains `arraysubs_from=plans`, and Back to plans appears after initialization. |
| Mobile | At390×844 the billing shortcut opens the native billing panel below the headers; saved interval2 is visible. |
| Runtime errors | No uncaught browser errors were reported by agent-browser. Existing unrelated WordPress/plugin development warnings remain outside this change. |
| Removed edit API | The retired saved-plan GET route `/arraysubs/v1/quick-products/9624` returns404. Source has no saved-plan controller, service, variation picker or Pro editing adapter references. |

An unrelated installed subscription plugin starts a document scroll-to-top animation on product-editor load. The billing shortcut stops queued document animations before positioning Product data, and waits for initial description-editor layout. Desktop screenshots confirm the final position remains correct.

Evidence: [simple](evidence/native-editor/arraysubs-native-billing-simple.jpg), [variable](evidence/native-editor/arraysubs-native-billing-variable.jpg), [box](evidence/native-editor/arraysubs-native-billing-box.jpg), [bundle](evidence/native-editor/arraysubs-native-billing-bundle.jpg), [mobile](evidence/native-editor/arraysubs-native-billing-mobile.jpg), [creation review](evidence/native-editor/arraysubs-native-billing-create-review.jpg), [created](evidence/native-editor/arraysubs-native-billing-created.jpg).

Core production build and touched PHP syntax checks passed. Core, Pro and documentation diffs passed whitespace checks. All touched source files remain below3000 lines. PHPCS was not run. The historical modal-specific104-assertion suite was not rerun because its edit endpoints have been removed.

Cleanup: temporary product20666 was removed after verification. Existing products9624,6722,5900 and5905 were inspected without saving changes. The catalog returned to its original35 plans; Pro remained active. No subscriptions, orders or customers were changed.
