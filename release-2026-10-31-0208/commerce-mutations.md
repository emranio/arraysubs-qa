# Commerce mutation ledger

Commerce owns only the records listed below. The coordinator owns/restores all global settings. Stripe TEST and the verified Mailpit sink were used throughout. No real payment, real email recipient, refund, product code, or database command was used.

| Record | Original | Test change / verified result | Current state and cleanup |
| --- | --- | --- | --- |
| Product 109433, RQA0208 Paid Monthly | Absent | Wizard published virtual $7.31/month; billing length 0→12 persisted | DRAFT saved/reloaded after cancellations; retained as QA evidence |
| Product 109466, RQA0208 Trial Shipping | Absent | Copied 109433; seven-day trial, $2 signup, nonvirtual, shipping $3 initial/$1 renewal; saved/reloaded | DRAFT saved/reloaded after cancellation; retained as QA evidence |
| Product 109500, Bundle Draft | Absent | Copy 33290; fixed discount 5→6 persisted; total 24 | TRASHED via scoped three-item bulk action |
| Product 109529, Box Draft | Absent | Copy 33324; gift placeholder persisted | TRASHED via same scoped bulk action |
| Product 109542, Variable Draft; children 109544/109546 | Absent | Copy 33313; Silver 15→15.01 persisted after reload | Parent TRASHED via same scoped bulk action; child disposition not independently queried |
| Product 109521, Grouped Meta Draft | Absent | Copy 109433; Grouped save retains hidden subscription flag; unsaved Simple switch discarded | Draft Grouped; retain as existing #8 proof unless coordinator prefers trash |
| Product 109573, Redirect Draft | Absent | Conditional-control ref shift opened Copy to Draft; caught before any financial action. Renamed; enabled Show404, Save Draft/reload proved persistence | TRASHED; original product redirect remained disabled, verified after reload |
| Product 109433 Feature Manager | Empty | Added enabled RQA0208 Perk / RQA0208 benefit; save/reload and customer positive proof | RESTORED empty via Delete confirmation, modal Save, Woo Update, full reload; customer My Features empty again |
| Customer 3252, rqa0208-a@example.invalid | Absent | Auto-created by first checkout; role customer | Retain with sandbox orders. Private browser state only under /tmp |
| Order 109452 / subscription 109453 | Absent | One $7.31 Stripe TEST purchase | Order Completed; subscription CANCELLED immediately by customer, admin list agrees; retained |
| Order 109474 / subscription 109475 | Absent | One $5.00 Stripe TEST trial purchase (fee2 + shipping3) | Order Processing (physical); subscription CANCELLED immediately by customer, admin list agrees; retained |
| Renewal order 109485 / subscription 109453 | Absent; next Dec1 | One $7.31 Early Renew after preview/dismissal test | Completed once; next Dec31; retain order, no refund |
| Order 109510 / subscription 109511 | Absent; credit1 | One $6.31 Stripe TEST purchase after automatic $1 credit application | Order Completed; subscription CANCELLED immediately by customer, admin list agrees; retained |
| Admin custom subscription 109525 | Absent | Created Pending: initial10, different renewal12 after1, length2; persisted | DELETED permanently via scoped Delete confirmation, disabled Deleting state, then no match for109525; no orders/gateway charge |
| Subscription 109453 billing company | Empty | RQA0208 save/reload; first empty-fill attempt was a CLI no-op | RESTORED empty using verified End/Backspace clear, Save, reopened form. Historical order billing snapshot retained |
| Subscription 109453 Skip | Next Dec31, no skip | Skip1→Jan31; UndoSkip→Dec31; admin agrees | Restored, no extra order; coordinator restored Skip policy |
| Subscription 109453 Pause required reason | Active, next Dec31 | Two required-reason attempts failed because UI has no reason field | No state mutation from failed attempts; issue C4/#153 |
| Subscription 109453 optional Pause | Active, next Dec31 | Pause one day→Paused/Jan1; Resume→Active/Dec31 02:15 | Resumed; actual elapsed pause added about one minute; coordinator restored policy |
| Subscription 109475 scheduled cancellation | Trial, due Nov7 | Dismissal/required selection; schedule→Trial Pending Cancellation; Undo→Trial | Restored Trial/Nov7; coordinator restored immediate-cancel policy |
| Subscription 109453 private note | Absent | Added tagged RQA0208 release QA private note; full reload retained | Retain with QA subscription evidence |
| Customer 3252 cart | Empty | Paid/trial purchases; blocked repeat trial; box builder/cart; narrow checkout sample | Empty verified after removing final line; viewport restored 1280×577 |
| Customer 3252 store credit | 0.00 | Inventory worker granted1; checkout applied−1 on109510 | Balance0.00; exactly one ADMIN+1 and one ORDER_APPLIED−1, independently reconciled by inventory worker |

No financial request is pending. Four intended sandbox charges total $25.93, across three Completed orders and one physical trial order Processing. No refunds were issued. All paid orders and the customer will be retained. Coordinator released all holds after email subjects and Audits restoration. Three immediate customer cancellations settled, all three admin rows show Cancelled with no next payment; two published products are now drafts; Pending109525 was deleted. Post-cancel Pending Scheduled Actions argument searches109453/109475/109511/109525 all returned No items found. No direct queue Run/Cancel/Delete action was taken. Cleanup complete; no outstanding live mutation remains.


## Final handoff

All commerce test mutations are reconciled. Retained evidence records: customer3252; four sandbox orders109452/109474/109485/109510; three Cancelled subscriptions109453/109475/109511; drafts109433/109466 and known-issue proof109521; tagged private subscription note and zero-balance credit history. Extra draft copies109500/109529/109542/109573 remain Trash. Variable child109544/109546 disposition was not independently queried. No financial request, future owned billing job, refund, or cleanup action is pending. Commerce made no global settings/module changes.

Commerce admin and customer-A browser sessions were closed after verification. Task3 was marked done and released; unresolved bugs and gaps remain documented.
