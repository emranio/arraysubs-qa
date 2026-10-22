# Member details Free/Pro fix — 2026-10-23

Targeted issue: #85. No active full QA cycle or scheduled execution board was reused. Work applies to the local WordPress test site at http://localhost:10013; the shared mirror-help site was not deployed or changed.

## Implementation

- Core now boots Member Insight and owns member search, read and permitted profile/address editing endpoints.
- Core supplies subscription-list, order, user-profile and WooCommerce Customers shortcuts. The Customers shortcuts stay visible for mouse and keyboard access.
- Manage Members is an always-on core module, and its Reports card/readme no longer describe it as requiring Pro.
- Pro extends the core member response and customer shortcuts with Store Credit only while that feature is live. Pro retains its subscription-status reporting extension. The duplicate Pro member controller and user-profile asset entry were removed.
- Subscription totals now use the shared effective recurring-amount helper and the stored subscription currency. Paid fixture #8326 displays 349 USD instead of an unused order metadata value of zero.

## Browser verification

See [baseline reproduction](before/report.md) and [post-fix observations](after/report.md) for screenshots, requests and exact steps. The initial failure was reproduced twice: subscription #8326 -> customer #224 returned member HTTP 404 and the reported toast.

| Configuration | Flow | Result |
| --- | --- | --- |
| Free only | Subscription detail -> member #224 | Profile and subscriptions render; member request HTTP 200 |
| Free only | Search/select #223, then return to #224 | URL and displayed profile remain aligned |
| Free only | Edit Details, save unchanged profile/address values | Visible Saving state, successful POST and toast |
| Free only | Subscription list and native user edit shortcuts | Open the member page |
| Free only | Reports member card | Available without a Pro badge |
| Free only | WooCommerce Customers member shortcut | Correct member URL and successful destination |
| Free only | Store Credit, Checkout Builder, Feature Log, Cancellation Flow direct routes | Expected Pro lock; zero new Fetch requests |
| Pro active | Member profile and premium sections | Store Credit and Feature Manager links remain available |
| Pro active | WooCommerce Customers | One member shortcut and one Store Credit shortcut per registered row |

The last customer-shortcut keyboard/ordinary-click observations are recorded in the post-fix report. Native WordPress `.row-actions` hide links offscreen until hover; the new core script uses the standard `visible` class to keep these React-table shortcuts accessible.

## REST integration and builds

[The regression script](member-rest-regression.php) ran against real WordPress REST dispatch in both configurations: [20 Free checks](rest-free.json) and [20 Pro checks](rest-pro.json), all passed. It verifies shared data, core route ownership, one GET handler, search, missing users, unchanged-account rejection of invalid emails, paid amounts/currency, disabled Store Credit payload/shortcuts, anonymous/customer denial, and shop-manager edit restrictions. The disabled-credit check uses a request-local filter; it does not save module settings or change products.

Disposable shop-manager accounts #381 and #382 were deleted after their respective runs. Existing customer #224's profile, roles and billing/shipping address values remained unchanged after the browser save. WooCommerce updated normal save bookkeeping, and an absent additional-address metadata key became an empty saved value; see the post-fix report.

Both production builds passed: [core](core-build.log), [Pro](pro-build.log). New core shortcut assets exist and the obsolete Pro user-profile asset is no longer built. Touched product source files are below 3000 lines. Both canonical changelog drafts were updated while preserving prior entries.

## Broader boundary audit

[Audit record](free-pro-boundary-audit.md) covers other admin module gates, Reports, Overview, Easy Setup, customer portal fallbacks, core lifecycle endpoints and shared/developer integration paths. No separate Free-visible caller of an absent Pro endpoint was found beyond the Member Insight split. Existing links from notes, feature logs, credit pages and analytics now share the working core destination.

The audit is source review plus the specific browser cases above. It does not claim a new full checkout, gateway or subscription-lifecycle QA cycle.

## Restoration

[Post-test restoration check](after/restoration-check.json) confirms the exact active-plugin array and saved module settings were restored, with Pro active. No remote deployment, release version, package publication or payment-setting change was made. Unrelated workspace edits were preserved.
