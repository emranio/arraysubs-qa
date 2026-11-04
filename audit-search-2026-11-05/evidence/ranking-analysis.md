# Audit-log search ranking analysis

Date: 2026-11-05 (Asia/Dhaka). Issue: QA #158.

Read-only investigation used the existing built search records and quantized semantic vectors from `user-guide/dist/search/`, with the production search functions in `src/lib/search-core.mjs` and `src/lib/search-ranking.mjs`. The baseline source was `cf03efd`. No index files or source files were changed during these trials.

## Reproduction

Query: **How do I choose which activity events appear in the audit log?**

| Mode | Settings guide | Review guide | First result |
| --- | ---: | ---: | --- |
| Keyword | 54.8940508741 | 48.6045605850 | `audit-logs/choose-what-activity-is-logged` |
| Ask (hybrid) | 0.7318989064 | 0.7339651076 | `audit-logs/review-the-activity-audit-trail` |

The review guide's **Search the activity text** section has semantic similarity 0.6847597727. The settings guide's strongest semantic section has similarity 0.5844037547. This narrowly outweighs the settings guide's lexical advantage. Its current title and keywords emphasize individual product, coupon, email, and settings switches but omit a focused phrase for the broader task of choosing recorded activity events.

## Candidate trial

Appending the single keyword phrase **choose which activity events are recorded** to the settings guide's records, in memory, produced:

| Guide | Ask score |
| --- | ---: |
| `audit-logs/choose-what-activity-is-logged` | 0.7291998797 |
| `audit-logs/review-the-activity-audit-trail` | 0.6790771149 |

The trial retained the existing vectors, corpus, and ranking algorithm. It did not alter the failing expected result or add an exact-query rule. The recommendation is a targeted metadata clarification followed by the full guide check and browser verification.

## Related Ask queries tested

All of the following ranked their expected guide first with the candidate phrase:

| Query | Expected first guide |
| --- | --- |
| Which audit logs can I use to investigate subscription problems? | `audit-logs/audit-logs-explained` |
| How do I check whether a scheduled subscription job ran successfully? | `audit-logs/check-scheduled-job-runs` |
| How do I choose which activity events appear in the audit log? | `audit-logs/choose-what-activity-is-logged` |
| How do I find who changed a subscription in the activity audit trail? | `audit-logs/review-the-activity-audit-trail` |
| Where can I investigate a failed action in the customer portal? | `audit-logs/troubleshoot-customer-portal-errors` |
| Which activity events can I turn off in the audit log? | `audit-logs/choose-what-activity-is-logged` |
| How do I change which events the activity log records? | `audit-logs/choose-what-activity-is-logged` |
| How do I find who changed a subscription? | `audit-logs/review-the-activity-audit-trail` |
| Where can I filter the activity log by author? | `audit-logs/review-the-activity-audit-trail` |

All queries except the original failing question already ranked correctly in the baseline. These focused trials do not replace the complete search-quality suite or real browser tests.
