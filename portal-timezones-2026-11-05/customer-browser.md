# Customer timezone browser verification

Date: November 5, 2026, Asia/Dhaka. Plan: [plan.md](plan.md), task 2 on the progress board. Issue: 160. Subscription/order IDs: N/A (disposable provider responses only). Customer `timezone-customer@example.test`, ID `b9d14b0f-9f86-4c79-80ce-980efe9eb688`, customer role. Actual app: `http://127.0.0.1:3017`, isolated local PostgreSQL schema and app clone. Provider responses and outgoing email are intercepted in this local process. No production customer or billing state was changed.

Started with the required agent-browser CLI, following its core and dogfood guides. Its screenshot command hung in both independent sessions. After that tool limitation, used the connected Edge browser for actual UI interaction and visual inspection. API checks supplement these browser flows; they do not replace them.

| Flow | Actual browser result | Evidence |
| --- | --- | --- |
| Default UTC | New customer profile selected UTC. Products displayed annual purchase Nov 4, 2026 and expiry Nov 4, 2027. | [UTC product screenshot](evidence/customer-products-utc.jpg), [text](evidence/customer-products-utc.txt) |
| Save Asia/Dhaka | Settings → Account details → Timezone → Asia/Dhaka → Save changes showed success; footer updated immediately. | [Settings screenshot](evidence/customer-settings-dhaka.jpg) |
| Product dates | Same annual purchase became Nov 5, 2026, expiry Nov 5, 2027; trial ended Nov 20 instead of Nov 19. | [Dhaka screenshot](evidence/customer-products-dhaka.jpg), [text](evidence/customer-products-dhaka.txt) |
| License/domain dates | Purchase, expiry, domain activation and last seen used the selected calendar day, Nov 5. | [License screenshot](evidence/customer-license-dhaka.jpg), [text](evidence/customer-license-dhaka.txt) |
| Manage overview | Annual purchase/expiry and next payment displayed Nov 5. No cancellation or payment action was submitted. | Browser observation; the same formatter is covered by date tests. |
| Invoice date filter | Selected From and To Nov 5. Exactly two of four UTC boundary invoices were included; both displayed Nov 5. Native date fields required keyboard commit after fill in this automation tool. | [Filtered screenshot](evidence/customer-invoices-dhaka.jpg), [text](evidence/customer-invoices-dhaka.txt) |
| Change to New York | Saved America/New_York. All four invoice dates became Nov 5, Nov 5, Nov 4, Nov 4, with matching timezone caption/footer. | [New York screenshot](evidence/customer-invoices-new-york.jpg), [text](evidence/customer-invoices-new-york.txt) |
| Fresh login | Signed out and signed back in through the browser. Dashboard still showed America/New_York. | [Fresh-session evidence](evidence/customer-fresh-session.txt) |
| Customer email | Signed out, opened Forgot password and submitted the fixture email. UI confirmed reset instructions. No reset link was activated and no password changed. Captured HTML and plain text both showed `2026-11-04 15:53:19 America/New_York (UTC-05:00)`, matching DB expiry `2026-11-04T20:53:19.085Z`. | [Sanitized request confirmation](evidence/customer-reset-request.txt), [email/DB comparison](evidence/local-customer-reset-email.json) |

The visual invoice check exposed clipped year text in the original narrow filter row. The shared date controls were widened and moved below the product/type/billing controls. The final New York screenshot shows both complete date inputs and the correct invoice rows. The earlier Dhaka screenshot records the successful filter test before that visual correction.

The customer setting never changes admin display: a fresh admin session still showed UTC+6 after New York was saved. Database creation/activity instants remained identical to the initial fixtures. Authenticated product, license, manage, and invoice API timestamps preserved their original UTC values; see [API/database evidence](evidence/local-api-db-utc-after-new-york.json) and [admin browser report](admin-browser.md).
