# Mutation ledger

| Owner | Record/setting | Original | Change | Restoration/result |
| --- | --- | --- | --- | --- |
| root | General / keep job logs for days |30|31 UI persistence check|restored30 via UI save; reload shows30 (evidence/settings-general-restored.txt)|
|root|Refund minimum amount (automatic refunds disabled)|0|0.01 UI persistence check|restored0 save+reload evidence/refunds-restored.txt|
|root|Plan Switching enabled|false|true save+reload; commerce acknowledged no switch actions|restoredfalse via save+reload (evidence/switching-restored.txt)|
|root|Content Gate default rich text|This content is restricted. Please subscribe to access.|append RQA0208 viaUI; access assertions paused/acknowledged|restored exact original text and <p> HTML, reloadverified evidence/content-gate-restored.json; Ctrl+A ineffective on macOS, Meta+A restoration succeeded|
|root|Profile/avatar Max File Size MB|2|3 save+reload|restored2 through save+reload evidence/profile-settings-restored.txt|
|root|Skip Renewal enable|false|true for allowed/disabled actioncontrast; commerce acknowledgedpause|restoredfalse save+reload evidence/skip-restored.txt|
|root|Allow Customers to Skip (stored under disabledSkip)|false|true for profileC; max3/cutoff2 unchanged|restoredfalse save+reload, hiddenstoredflag verified via unsavedEnable on and reloadwithoutSave; evidence/skip-hidden-values-restored.txt and actions-final-restored.txt|
|premium/root|Disabled Shop Access rule1name|Private member store|temporaryconcatenatedmarker due UItyping issue; eligibilityfalse unchanged|rootrestoredexactoriginal, save+reloadverified evidence/shop-rule-restored.txt|
|root|Pause feature/customer/resume enable flags|false,false,false|true,true,true; max30days,count2,cooldown0,reasonrequiredtrue,noaccessunchanged|restoredallfalse save+reload; hiddenallowcustomer/resumefalse verified evidence/pause-hidden-values-restored.txt|
|root|Require Pause Reason|true|false contrastbecauseUIcannotenterrequiredreason(issue153)|restoredtrue verifiedinhiddenPauseeditor aftersave+reload; featureoffpersisted|
|root|Cancel Immediately|true|false forscheduledcancel/undoontrial109475|restoredtrue save+reloadverified evidence/cancel-policy-restored.txt|
|root|RenewalInvoice emailSubject|empty(default)|RQA0208 Invoice {order_id} (localdraftbeforeSave)|saved+reloadverified; originalemptyrestored+reloadverified evidence/email-invoice-restored.txt|

|root|All live email Subject editors|original values in email-save-baseline.private.json|28 batch markers plus separate Invoice|29/29 live restored via Save+reload; email-save-results.json has no pending baseline states|
|root|Audits optional module|true|false Save+reload; direct Activity Audits gated cleanly; no worker writes while off|restored true Save+reload; historical RQA0208 note visible, evidence/modules-restored.txt and audits-module-restored.txt/png|
|root|Checkout Builder border radius|8|9 Save+reload check|restored8 Save+reload verified, evidence/checkout-radius8-restored.txt/png|
|root|Feature Manager Show Usage Count in Admin|true|false Save+reload|restoredtrue Save+reload; other3displayflags true,aggregationPerSubscription unchanged; feature-usage-restored.txt/png|
|root|CPT rule1 display name|Premium Library — members only|append RQA0208 harmless name only; enabledtrue and eligibility/outcomes unchanged|restored exact originalname Save+reload, enabledtrue unchanged; cpt-name-restored.txt|

Root final policy check: both Skip and Pause disabled on full reload; hidden Allow Customers to Skip=false, max3/cutoff2 preserved. Hidden inspection was an unsaved local enable, discarded by reload, no additional global setting write. All root mutations above are reconciled.

Final root housekeeping: CLI session list reports no active sessions. The four run-owned /tmp authentication-state files (admin, customerA, customerB, entitled) were deleted after session closure. No unrelated session or file was closed/deleted. All root settings and email journal entries are restored; commerce final disposition is in commerce-mutations.md.
Two run-owned temporary filled-card screenshots in /tmp were deleted. Final artifact privacy audit found no actual credentials, authentication tokens or payment secrets in the run artifacts; issue150–153 required fields and historical statuses were checked.
Run finalized 2026-10-31 03:01:16 +06; elapsed 53m 4s. Global settings restored, no sessions or pending financial requests; retained records and variation-child verification gap are explicit in the final report.
