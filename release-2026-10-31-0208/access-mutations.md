# Account and access mutation ledger

| Owner | Record/setting | Original | Change | Restoration/result |
| --- | --- | --- | --- | --- |
| access | Tagged QA user 2595 display name | Sqa sqa-fix81-c1 | Added RQA0208 suffix through Manage Members | Saved, reloaded and shown downstream in Account details; original restored and verified after reload |
| access | Tagged QA user 2595 custom Company Name | Empty | RQA0208 through customer Account details | Save/reload passed; empty original restored and verified |
| access | Tagged QA user 2595 billing address line 2 | Empty | RQA0208 through billing address editor | Reopened editor retained it; empty original restored and address overview verified |
| access | Tagged QA user 2595 shipping address line 2 | Empty | RQA0208 through shipping address editor | Reopened editor retained it; empty original restored and address overview verified |
| access | Run-owned Page 109473, RQA0208 Access | New auto-draft | Published tagged body with active-product-109433 restriction, ALL logic, admin bypass, default denial message | Save/reload and fresh A/B/guest assertions passed; canonical repeated issue #150. Trashed with native confirmation; Pages UI confirmed one page moved to Trash, ID 109473 |
| access | Customer B browser identity | Admin | Login as Customer 2595 | Session only; closed successfully at coordinator request |
| access | Entitled browser identity | Admin | Login as Customer 595 | Session only; closed successfully at coordinator request |

No global settings, modules, lifecycle states, financial records or existing admin cart contents were changed by this lane. The admin-owned frontend account already had one cart item; it was left untouched. No mutation or financial request remains pending.

All five access-owned sessions closed successfully at coordinator request after handoff; no pending browser operation or mutation.
