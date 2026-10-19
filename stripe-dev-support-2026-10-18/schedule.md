# Execution schedule (restart r2)

Phases run sequentially, one agent each, because all cases share one site and global settings. A phase starts only after the previous phase's `handoff-phaseN.md` exists. Times are UTC.

| Order | Phase | Board tasks | Planned start | Gate to next phase |
|---|---|---|---|---|
| 1 | Phase 1: preflight r2, settings snapshot r2, plan/board, capture plugin, shared fixtures, S01, S02 (core + Pro) | P1-* | 2026-10-19 07:00 | `handoff-phase1.md` written; board updated |
| 2 | Phase 2: S03, S04 (core + Pro) | P2-* | after phase 1 | retry/grace settings restored to snapshot (diff clean) |
| 3 | Phase 3: S05, S06 (core + Pro) | P3-* | after phase 2 | settings diff clean |
| 4 | Phase 4: S07, S08 (core + Pro) | P4-* | after phase 3 | settings diff clean |
| 5 | Phase 5: S09, S10 (core + Pro, S10 variants) | P5-* | after phase 4 | plan switching / store credit settings restored |
| 6 | Phase 6: core-alone S01, S02, S03/S05 ordinary failure-recovery, S06 replay | P6-* | after phase 5 | Pro reactivated; active plugin set equals original |
| 7 | Phase 7: cleanup, restoration proof, mu-plugin removal, final report | P7-* | after phase 6 | final report |

Daily-plan timing: invoice generation runs 6 hours before due (settings `invoice_before_due`), so bringing `next_payment_date` to `time() + 120` makes both the invoice and the due-payment actions eligible immediately. Each phase runs only fixture actions explicitly (`wp action-scheduler action run <id> --allow-root`) or waits for the per-minute queue runner.
