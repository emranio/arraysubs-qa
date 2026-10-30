# Email lane findings

No confirmed product defect.

An initial Store Credit Used preview showed an empty subject while the body was already visible. A single revisit with an explicit rendered-subject wait resolved `[mirror-help.arrayhash.com] Store credit used for Order #12345`, with the expected 20.00 applied, 30.00 remaining and order #12345 body. Other Pro credit previews also resolved their subjects. This was asynchronous initialization, so no QA issue should be created.

Proof: evidence/email-credit-used-repeat.txt and email-credit-used-repeat.png. All 29 editors rendered; save persistence, actual delivery and attachments are separate coverage gaps in emails-coverage.md. No email configuration was changed and no test message sent.
