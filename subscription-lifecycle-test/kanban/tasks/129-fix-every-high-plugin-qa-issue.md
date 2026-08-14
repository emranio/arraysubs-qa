---
id: 129
title: Fix every high plugin QA issue
status: done
priority: high
created: 2026-08-14T19:09:40.084766561+02:00
updated: 2026-08-14T20:21:53.031588832+02:00
started: 2026-08-14T19:09:40.271291148+02:00
completed: 2026-08-14T20:21:53.03158779+02:00
tags:
    - plugin
    - bugfix
    - maintenance
claimed_by: hive-lodge
claimed_at: 2026-08-14T20:21:53.031588732+02:00
class: standard
---

Resolve each high-prefixed plugin report in issues/ sequentially with source-task and live-data validation, dependency-safe implementation planning, security review, browser/data regression proof, and a done- filename only after the exact issue passes.

[[2026-08-14]] Fri 19:15
SLT-ADM-08 resolved and renamed on 2026-08-14. Confirmed Pro Store Credit label restoration overwrote WooCommerce native amount markup. Fixed exact HTML preservation and standard-mode ownership; live order 20230 passed $4/$5 native labels, Store Credit round-trip, dismissed native confirmation, zero browser errors, and exact zero-refund data invariants.

[[2026-08-14]] Fri 19:35
SLT-CPN-02 resolved and renamed on 2026-08-14. The note formatter treated every fixed discount as recurring although renewal application already gates on duration=recurring. Core now labels fixed one-time discounts as initial-order-only; live classic checkout order 26561/subscription 26562, admin UI, one-time renewal gate, recurring control, browser errors, and exact teardown all passed.

[[2026-08-14]] Fri 19:35
SLT-PROD-09 pre-fix plan: preserve the documented subscription_items one-click replacement contract. Reconcile only multi-product add-to-cart notices when WooCommerce reports successful grouped children that the current one-click cart no longer contains; keep ordinary single adds and mixed grouped adds unchanged. Test the two-subscription stale aggregate notice, retained cart line, one-subscription control, mixed subscription+plain control, cart data, and browser errors.

[[2026-08-14]] Fri 19:42
SLT-PROD-09 resolved and renamed on 2026-08-14. Confirmed the cart replacement is the configured subscription_items contract, while Woo grouped feedback was stale. Core now reconciles multi-product notices against post-hook cart contents with escaped titles and sanitized action HTML. Live two-subscription, single-subscription, and mixed subscription+plain controls passed; settings and persistent cart remained/restored exact.

[[2026-08-14]] Fri 19:46
SLT-SETUP-02 revalidated and renamed on 2026-08-14. The product fix was already committed; the high filename was stale. A live one-field General save sent only customer_actions.allow_early_renew, left all six reported runtime-default keys absent, and restored the raw option to exact SHA-256 identity. Mail stayed unchanged; exact test audit notes 26598/26599 and the temporary backup option were removed after identity checks.

[[2026-08-14]] Fri 19:53
SLT-SW-10 pre-fix plan: remove CancellationFlow terminal analytics writes from arraysubs_data_waiting_cancellation and capture terminal age/payment analytics only on arraysubs_data_cancelled. Canonical schedule meta, pending mail/notes, RetentionAnalytics scheduled_cancel rows, renewal unscheduling, and Pro remote gateway handling remain unchanged. Test live customer pending state with absent terminal meta, undo cleanup/action removal, and immediate terminal cancellation with timestamp plus analytics present.

[[2026-08-14]] Fri 20:03
SLT-SW-10 resolved and renamed on 2026-08-14. Confirmed CancellationFlow wrote terminal metadata from the waiting event. Core now captures age/payment snapshots only after arraysubs_data_cancelled while canonical lifecycle helpers own date/type. Live pending, independent scheduled analytics, dual mail, undo, immediate-terminal control, browser diagnostics, and exact disposable teardown all passed.

[[2026-08-14]] Fri 20:10
SLT-SYN-09 pre-fix plan: treat this as a real WooCommerce HPOS integration defect, not an ArraySubs renewal failure. Woo 10.9.4 intentionally loads core post.js for order-editor compatibility and autosave.js for lost-connection UI; the HPOS form exposes post_ID but no post title, slug box, or sample-permalink nonce. The first heartbeat therefore submits an invalid blank wp_autosave, and post.js reacts with a nonce-less sample-permalink request (403). Add a core HPOS-order-editor guard after autosave.js that suspends only unsupported local/server post autosaving and detaches only the permalink refresh listener, while preserving both script handles, heartbeat locking, lost-connection handling, and all Woo order controls. Test both reported renewals, an unrelated HPOS order, a normal product editor counterexample, browser diagnostics, request bodies/statuses, and exact order data invariants.

[[2026-08-14]] Fri 20:16
SLT-SYN-09 resolved and renamed on 2026-08-14. Confirmed Woo HPOS post.js plus autosave.js submit an invalid blank post autosave and nonce-less permalink refresh on every order editor. Shared core now suspends only unsupported post autosaving and the slug-refresh listener on validated HPOS order edit screens, preserving script handles, lost-connection UI, lock heartbeat, capabilities, and order controls. Both reported renewals, an unrelated HPOS order, a classic product counterexample, browser diagnostics, exact request bodies/statuses, and stable order hashes passed.

[[2026-08-14]] Fri 20:21
Final audit: zero high-prefixed reports remain; all six done-high reports contain resolution evidence. Core and Pro diffs pass whitespace checks; no forbidden native dialogs were introduced. Live hook inventory retains pending mail/notes/renewal suppression/Pro gateway/scheduled analytics, with terminal CancellationFlow only on arraysubs_data_cancelled. Disposable order/subscription/action/retention/backup-option records are absent, order 20230 still has zero refunds, and the pre-existing unrelated QA deletion plus issue-fix-prompt.txt were preserved.
