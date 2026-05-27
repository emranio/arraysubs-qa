---
id: 88
title: 'stage-13: Store credit money placeholders render HTML in subject'
status: closed
priority: medium
created: 2026-05-23T10:48:29.362046663+02:00
updated: 2026-05-24T11:44:23.887206843+02:00
started: 2026-05-24T11:41:51.101636348+02:00
completed: 2026-05-24T11:44:23.887205671+02:00
tags:
    - qa
    - stage-13
    - email
    - store-credit
    - placeholders
claimed_by: shell-quartz
claimed_at: 2026-05-24T11:44:23.887206723+02:00
class: standard
---

Original task: stages/13-emails/03-store-credit-emails.md subtask 3.3.\n\nExpected customized Credit Added subject:\n[QA] cust3 just got 0.00 in credit (balance now 5.00)\n\nObserved via wp_mail capture after setting subject to [QA] {customer_name} just got {credit_amount} in credit (balance now {new_balance}):\n[QA] cust3 just got <span class="woocommerce-Price-amount amount"><bdi><span class="woocommerce-Price-currencySymbol">&#36;</span>10.00</bdi></span> in credit (balance now <span class="woocommerce-Price-amount amount"><bdi><span class="woocommerce-Price-currencySymbol">&#36;</span>35.00</bdi></span>)\n\nBody placeholders rendered and custom additional content appeared. Raw {...} tokens were gone. Problem is subject-safe formatting: money placeholders use wc_price() HTML markup inside email subject.\n\nDelivery/inbox proof remains blocked by issue #40; this was captured before actual send with wp_mail interception.

[[2026-05-24]] Sun 11:42
Plan: change Store Credit email money placeholder values to plain decoded currency strings for subject/heading/additional-content formatting while preserving numeric template args for HTML/plain body rendering; verify customized Credit Added subject through wp_mail interception.

[[2026-05-24]] Sun 11:44
Fixed/verified. Store Credit email money placeholders now use plain decoded currency text for subject/heading/additional-content formatting while templates still receive numeric amounts for wc_price() body rendering. Verification with Credit Added custom subject exactly from the QA issue produced subject '[QA] cust3 just got 0.00 in credit (balance now 5.00)' and heading/body also used plain 0.00/5.00 with custom additional content present; no HTML price markup in subject. Settings restored. php -l passed for all four Store Credit email classes.
