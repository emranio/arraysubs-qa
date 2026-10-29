# PDF invoice customer portal reproduction

Confirmed scope: reproduce the customer report about absent PDF invoice actions and the undefined Invoice::get_endpoint_url() fatal in ArraySubs related orders. Investigation only; no product fix or release work requested.

Scheduled day: 2026-10-29, Asia/Dhaka. Immediate manual execution, sequential tasks on this directory's fresh board. Preserve all existing qa/issues records.

Environment: start with https://mirror-help.arrayhash.com, admin credentials from the workspace AGENTS.md Live QA access section. Isolated agent-browser pdf-repro-admin and pdf-repro-customer sessions. Inspect installed plugin versions and invoice availability first. If the PDF plugin is absent, use an isolated disposable fixture and the official released plugin to reproduce, recording any environment differences. Do not send email, run payments or alter existing customers' orders.

User explicitly authorized installing WooCommerce PDF Invoices & Packing Slips during this run. Reproduction will use the real local WordPress site http://localhost:10013, with the current workspace sources, ArraySubs 2.1.0, Pro 1.3.0 and WooCommerce 11.1.2. Local admin entry and credentials come from AGENTS.md Local Testing Context. WP-CLI runs with --allow-root and the existing NPLEnP-EU MySQL socket, passed as a PHP ini argument. Browser sessions pdf-repro-local and pdf-repro-customer are isolated. Keep the requested PDF plugin installed after reproduction. Remote admin authentication worked; remote shell access was unavailable, so no remote fixture or deployment is performed.

1. Record the test environment and inspect the current ArraySubs integration plus WP Overnight's current public API.
2. In the real browser compare an order with no invoice against one with an existing invoice, including WooCommerce's ordinary Orders page as a control. Capture screenshots and the exact error. Use only disposable data for any setup.
3. Record findings, conditions, IDs, exact routes, evidence and limitations in the report and qa/issues. Restore temporary configuration and close the isolated browser sessions.

No prior QA schedule is reused and no recurring automation is requested. Each task is scheduled for the immediate manual run above. A production-code change is outside this reproduction request.
