---
id: 146
title: Subscription related-orders table pushes invoice action outside mobile viewport
status: closed
priority: medium
created: 2026-10-29T15:20:23.051423+06:00
updated: 2026-10-31T02:25:01.552315+06:00
started: 2026-10-29T15:47:26.571916+06:00
completed: 2026-10-29T15:47:26.571916+06:00
tags:
    - bug
    - customer-portal
    - mobile
    - pdf-invoices
class: standard
---

Active QA task2, scheduled2026-10-29 Asia/Dhaka, plan qa/pdf-invoice-visual-2026-10-29/plan.md. Actual local site http://localhost:10013; ArraySubs2.1.0/Pro1.3.0 active, Woo11.1.2, official WPO5.16.6. Customer479 qa_pdf_invoice_20261029 / qa.pdf.invoice.20261029@example.test customer role, subscription23967, order23966, product23964. Isolated agent-browser pdf-visual-pro using Edge156+CDPScreenshotNewSurface. Exact URL http://localhost:10013/my-account/as-view-subscription/23967/?qa_visual=pro, viewport390x844.

Repro: authenticate as fixture customer, open subscription, set390 viewport and inspect Related Orders. Expected: invoice control and order details fit usable mobile layout without expanding the page past the viewport. Actual: table extends x30..527px; document scrollWidth527 for390 viewport; Invoice spans x441..510, entirely outside viewport. Desktop1440x1000 renders Invoice legibly and native PDF opens correctly as a counterexample. Concrete proof qa/pdf-invoice-visual-2026-10-29/evidence/pro-mobile.png and pro-mobile-layout.json, original full screenshot inspected. Scope: core template renders the table for both plugins; inactive Pro visual repro pending. This is an actual product layout issue exposed after capture recovery, independent of the fixed invoice URL.

Fix plan: inspect both plugin styles and native WooCommerce responsive table implementations; reuse native responsive table classes/data-title labels so mobile related-order rows keep invoice action reachable while desktop stays unchanged. Implement in isolated task worktree, preserve native View/Pay/invoice actions, escape translated labels, update canonical core draft, inspect actual before/after screenshots with/without Pro at desktop and narrow widths, and click invoice to verify native PDF still opens.

[[2026-10-29]] Thu 15:21
Delegating the isolated core template fix to implementation agent while root captures core-only before state and owns all browser/config changes. Root releases claim explicitly for the implementation agent; no shared source or site mutation by that agent.

[[2026-10-29]] Thu 15:22
Implementation plan: add WooCommerce native shop_table_responsive to the related-orders table and translated attribute-escaped data-title labels for Order, Date, Status, Total and Actions, mirroring existing core subscriptions and Pro feature tables. Preserve invoice/View/Pay markup and desktop classes. No CSS/JS/build unless source inspection reveals a native layout conflict. Root owns actual core/Pro screenshots and PDF open validation; implementation remains in isolated worktree.

[[2026-10-29]] Thu 15:24
Isolated implementation ready for root deployment/visual retest: commit23c9df3b6d28d200a7715a51d43ca8744cc69a9d on codex/pdf-invoice-mobile in /tmp/arraysubs-invoice-mobile-fix. Only related-orders table gains native Woo responsive class plus five esc_attr_e data-title attributes. Existing View/Invoice/Pay logic, link labels and desktop classes unchanged; no CSS/JS/build. Core draft appended with existing bytes preserved as exact prefix; Pro draft/source unchanged. Translation proof: all five msgids already ship in arraysubs.pot and Spanish PO (Order=Pedido,Date=Fecha,Status=Estado,Total=Total,Actions=Acciones). Invoice remains provider-translated/custom action text. Self source review/whitespace check done, no lint or extra tests; root owns actual screenshot/PDF proof before merging and closing. Lines: template1243,draft153. Releasing claim for root takeover.

[[2026-10-29]] Thu 15:47
Root verification complete and merged to core main commit23c9df3. Reproduced original527px document overflow at390px with Pro inactive too. Native Woo responsive class plus five existing esc_attr_e labels correct both states:390px page remains390px; Invoice x187..256. Spanish320px labels Pedido/Fecha/Estado/Total/Acciones and actions Ver/Factura/Pagar fit, with visible unoccluded Invoice target. Desktop columns/focus remain correct. Actual pointer and keyboard actions open fully rendered native PDF; download files pass PDF signature/content and rendered visual inspection. Final screenshots/report: qa/pdf-invoice-visual-2026-10-29/report.md. Existing portal regression19 assertions and20 actual browser/layout/translation/PDF observations pass. Exact original four option groups and Pro activation restored; WPO remains active, Coming Soon=yes; official Spanish pack retained. Template1243 lines, draft153; prior draft prefix preserved, Pro unchanged. Owned browser sessions and worktree cleaned up.


ReleaseQA2026-10-31 occurrence (priorclosedstatuspreserved):
ACCESS-002 — Live subscription Related Orders table overflows390px viewport (matches existing closed#146)

- Severity:medium customer-portal responsive regression on live QA. Existing#146 remains closed; coordinator to append this run evidence without changing its status.
- Active QA task:#4 Customer access and premium workflows, scheduled2026-10-31 Asia/Dhaka; plan qa/release-2026-10-31-0208/plan.md.
- Subscription109453; orders109452(initial) and109485(early-renewal completed by commerce lane); product109433.
- CustomerA3252, login rqa0208.customera, testemail rqa0208-a@example.invalid, roleCustomer.
- URL:https://mirror-help.arrayhash.com/my-account/as-view-subscription/109453/.
- Browsercontext:rqa0208-owner, isolated A auth copied from commerce private state, viewport390x844. ProfileA current settings. No lifecycle mutation by access lane.
- Steps:1) Open own subscription detail.2) Set viewport390x844.3) Scroll Related Orders into view and inspect rightmostcolumns.
- Expected:order table fits usable responsive layout, with Total and View actions reachable within viewport or an explicit contained scrollsurface, without expanding wholepage.
- Actual:Related Orders table509.8125px wide atleft30; document.scrollWidth540 forwindow.innerWidth390. View actions and much of Total are outside initial viewport; screenshot shows clipped right edge.
- Proof:evidence/access-related-orders-mobile-viewport.png (390px viewport screenshot),evidence/access-subscription-mobile.png (fullpage width540); direct browser DOM layout corroboration viewport390,pageScrollWidth540,tablewidth509.8125,left30.
- Knownscope/counterexample:Accountdetails form at390px remains readable and fits. Desktop subscriptiondetail renders fullcolumns. No Invoice action present on this fixture, but same related-orders table issue is recorded in#146. Existing#146 documents fix23c9df3 and local verification2026-10-29; present livebehavior suggests deployment alignment gap, not proof the currentlocalsource fix is broken.
- Restoration:viewport/session only; no record/settings mutation.
