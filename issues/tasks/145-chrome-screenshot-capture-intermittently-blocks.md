---
id: 145
title: Chrome screenshot capture intermittently blocks invoice visual QA
status: closed
priority: medium
created: 2026-10-29T15:16:09.05025+06:00
updated: 2026-10-29T15:20:23.038081+06:00
started: 2026-10-29T15:20:23.03808+06:00
completed: 2026-10-29T15:20:23.03808+06:00
tags:
    - qa
    - browser
    - tooling
    - pdf-invoices
class: standard
---

Active QA task1, scheduled2026-10-29 Asia/Dhaka; plan qa/pdf-invoice-visual-2026-10-29/plan.md. Tooling issue, not a product regression. Subscription/order/customer/user IDs N/A for blank-page screenshot failure; related visual verification uses disposable customer479 qa_pdf_invoice_20261029 rolecustomer, subscription23967/order23966 and adminID1. Browser context isolated agent-browser CLI0.27.3 with cached Chrome for Testing146.0.7680.153 on macOS arm64; exact diagnostic URL about:blank and scoped data-page cases, product route http://localhost:10013/my-account/as-view-subscription/23967/.

Repro during prior QA: open a page, snapshot works, run screenshot absolute-path or --full; repeated Page.captureScreenshot timeout/EAGAIN even on blank pages with headed/headless/GPU-disabled variants. Expected actual screenshot PNG; actual capture hangs while DOM/navigation/download operations remain usable. Original limitation documented in qa/pdf-invoice-compatibility-2026-10-29/report.md. Fresh diagnostics now show intermittent recovery on current Chrome and reliable captures with existing Microsoft Edge156 via supported --executable-path; do not claim a permanent upstream code fix from transient success. Plan: verify a supported per-session modern Chromium executable or compositor flag repeatedly, then actual customer/PDF screenshots with Pro and without Pro, retain evidence and restore site configuration. Do not alter shared managed profile or user browser preferences. Counterexample: fresh default blank/full-page captures currently succeed; existing Edge156 diagnostic captures also succeed. Scope is capture tooling, not a PHP fatal or invoice availability bug.

[[2026-10-29]] Thu 15:20
Capture path recovered and actual product/native PDF screenshots verified using agent-browser with existing Edge156 executable plus CDPScreenshotNewSurface flag. Reusable per-session launch-browser helper saved in qa/pdf-invoice-visual-2026-10-29. Actual pro-desktop.png, pro-invoice-hover.png, pro-invoice-focus.png and pro-pdf-open.png captured and visually inspected; native viewer visibly renders invoice23966/product/quantity/25.00 total, not a blank MIME-only page. Diagnostic Chrome surface flag passed12 captures across6 navigations, Edge sample also succeeds; source/cause is documented as inference from upstream1437/1189, not a claimed upstream patch. No global browser/profile/default preference changes. Diagnosis notes and original diagnosticPNG files retained. Actual remaining QA now finds separate mobile-table product issue.
