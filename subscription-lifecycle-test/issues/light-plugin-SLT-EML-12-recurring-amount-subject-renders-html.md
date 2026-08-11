# Recurring amount merge tag renders raw HTML in the email subject

- Severity: medium
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
