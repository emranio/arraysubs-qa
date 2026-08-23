# SLT-REF-04 — email inventory procedure

Fresh-cycle guide updated 2026-08-22. Generate the effective inventory from the current code and
WooCommerce email settings; do not copy prior subjects, enablement or message IDs.

## Per-message proof

- Trigger and owning lifecycle card.
- Effective enablement and recipient.
- Exact subscription/order/user/provider IDs.
- Subject, rendered HTML/plain body, links and site-local dates.
- Immutable Mailpit baseline captured immediately before the trigger and the complete resulting delta.
- Negative rows for duplicate, wrong-recipient and forbidden-stage messages.

## Current sources

- `arraysubs/src/Features/Emails/Services/EmailManager.php`
- `arraysubs/src/Features/Emails/Emails/`
- `arraysubs/src/Features/Emails/templates/`
- `documentations/architecture/email-system.md`

Use `/usr/local/bin/mailpit-agent`; never infer delivery from a hook or DB row alone.
