# Menu injection investigation, 2026-09-19

User report: latest versions; Subscriptions absent from the My Account builder on thequillsacademy.com. Focus only on menu injection, later filters, saved/default merge and labels. Do not investigate missing files or mutate production.

Targeted continuation, active formal QA cycle N/A. Local browser target http://localhost:10003, admin via the user-provided local auto-login URL. Current core 2.0.5, Pro 1.2.4 active. Existing QA issues are preserved.

Sequence for this same-day investigation:
1. Read-only baseline of builder and its actual config/defaults REST responses.
2. Local request-scoped option fixture omits Subscriptions from saved config only: it should be appended from defaults.
3. Local request-scoped menu filter removes Subscriptions after injection, plus the same omitted saved item: the builder should omit it while Store Credit/My Features survive. This is a simulated conflict, not a claim about the reported site.
4. Keep the saved item while that filter removes it: the builder should retain the saved row.
5. Request-scoped label fixture renames Subscriptions to Support: distinguish absent label from absent endpoint.
6. Remove the fixture and confirm saved options, activation state and normal UI remain unchanged. No Save Configuration clicks or subscription actions.

Use the real admin browser and screenshots for each visible result. Record the PHP filter order and sanitized actual REST results. No network mocking or production access.

Completed: all four discovery fixtures produced the expected distinction between saved rows and filtered defaults. The runtime fixture was removed. The user subsequently authorized required-menu enforcement and as- endpoint prefixes; implementation and fresh regression results are recorded in ../../required-account-menus-2026-09-19/report.md. Final raw menu/settings/activation hashes match this investigation's baseline.
