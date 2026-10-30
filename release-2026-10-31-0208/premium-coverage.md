# Premium/admin rule coverage shard

Both plugins active; profile A unless stated. Browser session rqa0208-premium-admin. Source inventory supports discovery; results here need browser evidence.

|capability|current URL|role|profile/fixture|check depth|expected → observed|result|proof/issue|
|---|---|---|---|---|---|---|---|
|Role Mapping rules|https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/members-access|admin|A; 1 existing rule|save+reload|Display name suffix saved+reload then restored+reload; eligibility unchanged|PASS|evidence/premium-role-saved.png; premium-role-restored.txt|
|URL rules display name|https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/members-access/url-rules|admin|A; rule 4 Manual Screenshot Conflict URL Rule|save+reload|Harmless name suffix persists → persisted after isolated save, visible Saving…; restored original and verified reload|PASS|evidence/premium-url-save.png; premium-url-after-reload.txt; premium-url-restored.txt; eligibility/outcome unchanged|
|Discount Rules|https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/members-access/discount-rules|admin|A; Gold members save 15%|save+reload|Display name suffix saved+reload then restored+reload; eligibility unchanged|PASS|evidence/premium-discount-saved.png; premium-discount-restored.txt|
|Download Rules|https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/members-access/downloads-rules|admin|A|render|Correct template → 0 rules; clean empty state; screenshot inspected|PASS|evidence/premium-downloads.png; independent save NOT-TESTED|
|Shop Access Rules|https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/members-access/ecommerce-rules|admin|A; one disabled rule|save+reload|Changed display name persisted; coordinator restored exact original with checkbox false after reload|PASS|evidence/premium-shop-save-result.png; evidence/shop-rule-restored.txt|
|CPT Rules|https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/members-access/cpt-rules|admin|A|render|Correct template → 1 enabled rule; screenshot inspected|PASS|evidence/premium-posttypes.png; independent save NOT-TESTED|
|Comment Restriction Rules|https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/members-access/comment-rules|admin|A|render|Correct template → 0 rules; clean empty state; screenshot inspected|PASS|evidence/premium-comments.png; independent save NOT-TESTED|
|Purchase Limit Rules|https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/members-access/purchase-limit-rules|admin|A|render|Correct template → 0 rules; clean empty state; screenshot inspected|PASS|evidence/premium-purchase-limit.png; independent save NOT-TESTED|
|Login Limit Rules|https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/members-access/login-limit|admin|A|render|Correct template → 0 rules; global prevention disabled, maximum sessions 1, admin disabled; screenshot inspected|PASS|evidence/premium-login-limit.png; independent save NOT-TESTED|
|Member Styling Rules|https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/member-styling|admin|A|render|Correct template → enabled; 0 rules; clean empty state; screenshot inspected|PASS|evidence/premium-styling.png; independent save NOT-TESTED|
|Profile Form + existing field editor|https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/profile-builder/profile-form|admin|A; Company Name field|interaction|Open edit row → Label/Key/Type/Placeholder/Help/Required controls and avatar settings load|PASS|evidence/premium-profile.png; premium-profile-field.png; save NOT-TESTED (coordinator owns global saves)|
|My Account menu editor and item detail|https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/profile-builder/my-account|admin|A; 10 default items|interaction|Expand Dashboard → optional custom label; required Orders/Subscriptions controls retained; other menu rows readable|PASS|evidence/premium-account-editor.png; premium-account-item.png; save NOT-TESTED|
|Checkout Builder canvas|https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/checkout-builder|admin|A; single Checkout step with 4 elements|interaction|Canvas opens → 4 required elements shown; Elements/Settings tabs and billing editor open without console errors|PASS|evidence/premium-checkout.png; premium-checkout-billing-editor.png; save NOT-TESTED|
|Checkout Builder settings|https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/checkout-builder|admin|A; Light/860px/8px/Comfortable/Top/Numbers|render|Settings controls → values rendered; deployed UI retains older Copy Custom Fields to Subscriptions control|PASS|evidence/premium-checkout-settings.txt/png; deployment mismatch limits current-release coverage; independent save NOT-TESTED|
|Cancellation Flow overview and wizard|https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/cancellation-flow|admin|A; require reason=true, retention=false|interaction|Open 7-step wizard; Back/Continue; retention off skips 4 offer steps → Overview; save disabled without changes|PASS|evidence/premium-cancel-overview.png; independent save NOT-TESTED|
|Cancellation offer field editors and invalid recovery|https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/cancellation-flow|admin|Unsaved editor-only contrast; enable offers locally|interaction|All 4 offer fields render; discount 101% rejected with visible toast, 20% recovers and Continue advances; no settings saved|PASS|evidence/premium-cancel-invalid-discount.png; premium-cancel-pause-fields.png; premium-cancel-downgrade.png; premium-cancel-contact.png|
|Store Credit list/search/detail|https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/store-credit|admin|A; run-owned customer 3252|interaction|Search rqa0208-a, open detail → correct customer, baseline 0.00 and no history|PASS|evidence/premium-credit-search.png; premium-credit-detail.png|
|Store Credit grant→account→checkout→admin reconciliation|https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/store-credit/3252|admin + customer A (commerce handoff)|A; customer 3252 / order 109510 / subscription 109511|end-to-end|Grant 1.00 → visible Processing… → reload 1.00 and single history entry; account confirmed; checkout uses 1.00; admin reload 0.00 with exactly +1/−1 entries|PASS|evidence/premium-credit-granted.png; premium-credit-reconciled.png; commerce-credit-receipt.png|
|Store Credit global history|https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/store-credit/history|admin|A|interaction|History table, filters and pagination render; Admin Adjustment filter removes other operation types|PASS|evidence/premium-credit-history-filtered.txt/png; grant 109499 / debit 109515 identified|
|Cancellation repeated Escape after Keep editing|https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/cancellation-flow|admin|Unsaved local draft|interaction|Shared discard prompt then Keep editing / repeated Escape → renderer unresponsive; saved baseline retained in recovery|BLOCKED|premium-findings.md suspected #127 recurrence; deployed code alignment unknown|
|Store Credit settings|https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/store-credit/settings|admin|A; auto apply=true, checkout=true, minimum 5, expiry 0, purchases 10–500 / default 50|render|Application, expiry and purchase controls render; current values captured|PASS|evidence/premium-credit-settings.txt/png; save NOT-TESTED by worker (coordinator assigned)|
|Feature Manager settings|https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/settings/feature-manager|admin|A; all 4 display switches true; Per Subscription|render|Display and aggregation controls render without fatal errors|PASS|evidence/premium-features.txt/png; save NOT-TESTED by worker (coordinator assigned)|

## Independent save-editor reconciliation

|Editor|Worker save result|Proof / gap|
|---|---|---|
|Role Mapping|PASS|Real name change, reload and restoration|
|Discount|PASS|Real name change, reload and restoration|
|URL|PASS|Real name change, reload and restoration|
|Shop Access|PASS|Changed name persisted after CLI input; coordinator restored exact original and reloaded, checkbox false. Proof: evidence/shop-rule-restored.txt.|
|CPT/Post Types|NOT-TESTED|Queue stopped at coordinator request|
|Downloads|NOT-TESTED|No existing rules; creation not exercised|
|Comments|NOT-TESTED|No existing rules; creation not exercised|
|Purchase Limit|NOT-TESTED|No existing rules; creation not exercised|
|Login Limit|NOT-TESTED|No existing rules; global prevention off; on-state enforcement not tested|
|Member Styling|NOT-TESTED|No existing rules; creation not exercised|
|Profile Form|NOT-TESTED|Editor interaction only; coordinator may save avatar field|
|My Account menu|NOT-TESTED|Display-item editor interaction only|
|Checkout Builder|NOT-TESTED|Canvas, settings and billing-editor interaction; no changed configuration save|
|Cancellation Flow|NOT-TESTED|Wizard and validation only; local draft not saved; renderer freeze blocks discard proof|
|Store Credit adjustment|PASS|Grant 1.00, reload → checkout debit → 0.00 reconciliation|
|Store Credit settings|NOT-TESTED|Coordinator owns independent global save|
|Feature Manager settings|NOT-TESTED|Coordinator owns independent global save|

## Known security risk check

|capability|current URL|role|profile/fixture|check depth|expected → observed|result|proof/issue|
|---|---|---|---|---|---|---|---|
|Private box-upload raw path|https://mirror-help.arrayhash.com/wp-content/uploads/arraysubs-box-uploads/pDwqQLRwgUlKSfz3aVkF.png|guest|A; existing synthetic file from issue19|interaction|Unauthenticated path denied → browser renders8×8PNG, main-document HTTP200|FAIL|security-findings.md; existing blocked issue19; evidence/security-box-raw-network.txt/png|
