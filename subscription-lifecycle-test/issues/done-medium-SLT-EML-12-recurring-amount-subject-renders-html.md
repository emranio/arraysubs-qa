# Recurring amount merge tag renders raw HTML in the email subject

- Severity: medium
- Status: fixed
- Date found: 2026-08-05
- Watch day: D03
- Originating task: `SLT-EML-12`
- Plan file: `kanban/tasks/056-override-subject-heading-and-content-on-new.md`

## Task / stage / plan

- QA progress task: `#56` / `SLT-EML-12`
- Stage: `D03`
- Plan path: `qa/subscription-lifecycle-test/kanban/tasks/056-override-subject-heading-and-content-on-new.md`

## Affected records

- Subscription: `12786`
- Parent order: `12776`
- Product: `11938` (`SLT Lifetime One Time`)
- WP user: `366`, login `slt-email`, email `slt-email@example.test`, role `customer`
- Gateway: Stripe test
- Checkout type: block checkout (the pre-existing H1 fixture)
- Temporary setting: WooCommerce email section `arraysubs_new_subscription` used the authored subject, heading, and additional-content overrides from `SLT-EML-12`; the exact pre-test absent option state was restored after the send.

## Affected IDs

- Subscription ID(s): `12786`
- Order ID(s): `12776`
- Product ID(s): `11938` (`SLT Lifetime One Time`)

## Affected user / customer context

- WordPress user ID(s): `366`
- Login / email: `slt-email` / `slt-email@example.test`
- Role(s): `customer`

## Exact routes / browser context

- Settings URL: `https://mirror-help.arrayhash.com/wp-admin/admin.php?page=wc-settings&tab=email&section=arraysubs_new_subscription`
- Subscription route: `https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions`
- Browser contexts: authenticated admin session `admin-SLT-EML-12` and read-only Mailpit session `mail-SLT-EML-12`

## Reproduction

1. Open the ArraySubs New Subscription WooCommerce email settings as an administrator.
2. Set Subject to `SLT-EML-12 {customer_first_name} :: sub {subscription_id} :: {product_name} :: {recurring_amount}`.
3. Set Email heading to `Hello {customer_first_name}, subscription {subscription_id} is {subscription_status}`.
4. Set Additional content to `PROBE start={start_date} next={next_payment_date} period={billing_period} pay={payment_method}` and save with the email enabled.
5. Change active lifetime subscription `12786` to Pending, then back to Active through the ArraySubs admin UI.
6. Inspect the customer New Subscription message in Mailpit.

## Expected result

The RFC email subject is plain text:

`SLT-EML-12 SLT :: sub 12786 :: SLT Lifetime One Time :: $49.00`

All placeholders should be formatted for a mail header rather than returning HTML markup.

## Actual result

Mailpit message `5DWxnovrH9I1024JTuTxUj` has this literal subject:

`SLT-EML-12 SLT :: sub 12786 :: SLT Lifetime One Time :: <span class="woocommerce-Price-amount amount"><bdi><span class="woocommerce-Price-currencySymbol" translate="no">&#36;</span>49.00</bdi></span>`

The `{recurring_amount}` tag resolves to WooCommerce price HTML inside the subject header. Other live values render correctly: the heading is `Hello SLT, subscription 12786 is Active`; the PROBE shows start `5 August, 2026 7:26 PM (UTC+6)`, empty lifetime next payment, `Lifetime Deal`, and `Stripe`.

## Concrete proof

- Customer message: `5DWxnovrH9I1024JTuTxUj`, sent `2026-08-05T15:06:30Z` to `slt-email@example.test`.
- Matching unchanged admin message: `0zWQB9v5YIdXqjYpmEHm9v`.
- Mailpit UI: `/home/server-manager/slt-evidence/SLT-EML-12-03-overridden.png`.
- Saved override UI: `/home/server-manager/slt-evidence/SLT-EML-12-02-saved.png`.
- Bracket/restoration proof: `/home/server-manager/slt-evidence/SLT-EML-12-bracket.txt` (`15:02:04Z` to `15:10:37Z`, prior/final option state absent, final subscription status active).
- Subscription meta at review: `_recurring_amount=49`, `_billing_period=lifetime`, `_start_date=2026-08-05 13:26:24`, `_next_payment_date` empty, `_payment_method_title=Stripe`.
- No constructor samples (`John`, `Sample Subscription Product`, `$29.99`, `every month`, `12345`) appear in the live overridden message.

## Known scope / counterexamples

- The body price row renders `$49.00 / Lifetime Deal` correctly; the defect is specific to using `{recurring_amount}` in a subject/header context.
- The heading and all non-price PROBE tags resolve correctly, including an empty lifetime next-payment value.
- After clearing the overrides, customer message `1PVeoMecZqOQqAxlLtNshg` restored the plain default subject `[mirror-help.arrayhash.com] Your subscription #12786 is active`; matching admin message `6XstfWCpbfAFtSNvYCbd8t` was unchanged. Screenshot: `/home/server-manager/slt-evidence/SLT-EML-12-05-restored.png`.
- Non-SLT subscription `7809` renewed during the broader interval and was excluded; it did not use this overridden New Subscription template.

## Resolution — 2026-08-14

### Investigation and root cause

The report's saved Mailpit message and current source establish a real formatting-boundary defect.
`BaseSubscriptionEmail::populate_placeholders()` intentionally used `wc_price()` for
`{recurring_amount}` so HTML email bodies receive WooCommerce's accessible price markup. The same
placeholder map was also fed directly into `WC_Email::get_subject()`, leaving literal tags and the
encoded currency entity in the RFC header. The body-rendering counterexample was therefore valid
and should not be "fixed" by removing price markup globally.

### Fix and safety review

`BaseSubscriptionEmail::get_subject()` now normalizes the fully formatted subject at the mail-header
boundary: HTML entities are decoded, all tags and line breaks are removed, and the result is
trimmed. This applies after WooCommerce settings, placeholders, and subject filters resolve, so all
ArraySubs email classes receive the protection while HTML body prices remain unchanged.

This also prevents CR/LF or markup supplied through a product/customer-derived placeholder from
surviving into a mail header. It changes no recipients, triggers, template data, settings,
permissions, or Pro email implementation.

### Regression verification

- A direct render against original subscription `12786` and the exact authored subject produced
  `SLT-EML-12 SLT :: sub 12786 :: SLT Lifetime One Time :: $49.00`, with no `<` character, while
  the HTML body retained `woocommerce-Price-amount` markup and `$49.00`.
- A dedicated CR/LF/HTML probe normalized `Probe\r\n<b>{recurring_amount}</b>` to plain
  `Probe $49.00`; both newline and tag assertions were false.
- Live E2E: disposable pending SLT subscription `26663` was activated through the admin React
  confirmation flow under the exact temporary subject override. Mailpit message
  `4Oqu8vRlr4orfQId9DnH4y` arrived for `slt-email@example.test` with exact subject
  `FIX-SLT-EML-12 SLT :: sub 26663 :: SLT Lifetime One Time :: $49.00`; its HTML body still used
  WooCommerce price markup.
- Browser evidence:
  `/home/server-manager/slt-evidence/FIX-MEDIUM-SLT-EML-12-edit-before.png` and
  `/home/server-manager/slt-evidence/FIX-MEDIUM-SLT-EML-12-active-after.png`.
- The previously absent Woo email option was deleted after the send. Exact cleanup removed fixture
  `26663`, notes `26664`–`26667`, and actions `23417`–`23419`; post, note relationship, action, and
  option checks all returned zero/absent. Mailpit retains the message as evidence.
