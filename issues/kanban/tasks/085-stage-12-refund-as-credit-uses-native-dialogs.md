---
id: 85
title: 'stage-12: Refund-as-credit uses native dialogs'
status: closed
priority: critical
created: 2026-05-23T10:21:12.105561379+02:00
updated: 2026-05-24T11:32:44.632301669+02:00
started: 2026-05-24T10:47:38.446339006+02:00
completed: 2026-05-24T11:12:39.682595142+02:00
tags:
    - qa
    - stage-12
    - store-credit
    - refund
    - ui-standard
class: standard
---

Original task: stages/12-store-credit/07-refund-to-credit.md\n\nObserved in browser on WooCommerce order #1110 refund UI:\n- Selecting Refund as Store Credit shows preview correctly.\n- Entering 15.00 + reason QA refund-to-credit test updates preview to 5.00.\n- Clicking the refund action opens a native browser confirm alert: Are you sure you want to refund $ 15.00 as store credit?\n- This blocked agent-browser automation with UnexpectedAlertOpenError.\n- Source file arraysubspro/src/Features/StoreCredit/assets/refund-integration.js uses confirm() plus alert() for validation/success/error and only disables the button without shared modal/toast/loading helpers.\n\nExpected: no native alert/confirm/prompt anywhere. Store credit refund should use the project's shared confirmation/toast/loading pattern or WooCommerce-compatible non-native UI, with visible loading state.\n\nImpact: QA cannot complete the browser refund flow through agent-browser, and the UI violates the workspace critical UI confirmation/loading standard.\n\nAdditional UI note: with Store Credit selected, the primary button label remains Refund 5.00 manually rather than indicating store credit.

[[2026-05-23]] Sat 19:56
Stage 19 Task 05 reproduced on Woo order #1750. Store-credit refund UI displayed correctly and accepted amount/reason, but processing path uses native confirm()/alert() in arraysubspro/src/Features/StoreCredit/assets/refund-integration.js. agent-browser cannot complete the modal alert path, and this violates shared UI standard. Continued QA through equivalent server-side AJAX logic.

[[2026-05-24]] Sun 10:48
Plan: replace refund-integration.js native alert()/confirm() with a non-native WP-admin modal, admin notice/toast area, and button loading state. Also update the Woo refund button label while Store Credit is selected. Shared React/portal helpers are not loaded on the Woo order editor, so this standalone admin asset needs a scoped equivalent instead of browser dialogs.

[[2026-05-24]] Sun 11:07
User instruction update: AGENTS.md now says to start with agent-browser but fall back to agent-browser with screenshots when agent-browser times out, mis-clicks, or cannot expose needed UI state. Continuing #85 verification with agent-browser screenshots because agent-browser became unreliable around the refund confirmation flow.

[[2026-05-24]] Sun 11:12
Fixed/verified. Replaced native alert()/confirm() in Store Credit refund UI with scoped WP-admin notice, custom confirmation modal, button loading state, and Store Credit-specific button labels; added capture-phase click interception so WooCommerce's native refund confirm cannot fire for Store Credit refunds. Source scan shows no alert/confirm/prompt in refund integration. agent-browser fallback used after agent-browser failed around modal state; screenshots saved: qa/artifacts/issue-85/01-order-before-refund.png, 02-store-credit-selected.png, 03-confirm-modal.png, 04-after-confirm-click.png, 05-after-refund-refresh.png. Browser proof on order #2605: Store Credit selected, buttons labeled 'Refund $ 1.00 as Store Credit', custom modal 'Refund as Store Credit' appeared, no native dialog fired, refund succeeded and page refreshed with order note. Backend proof: _refunded_as_credit=1, customer #308 balance=1, credit log #2756 source order #2605.

[[2026-05-24]] Sun 11:31
Follow-up regression fixed: Store Credit refunds now create a real WooCommerce refund record with refund_payment=false before tracking store-credit meta. Browser verified order #2774 full Store Credit refund: order status refunded, WC total_refunded 8.50, refund #2780, subscription #2776 status arraysubs-cancelled, customer #313 credit balance 8.50. agent-browser screenshots saved in qa/artifacts/refund-status-regression/.
