---
id: 153
title: Customer Pause cannot submit when a reason is required because the flow has no reason field
status: open
priority: critical
created: 2026-10-31T02:29:50.385594+06:00
updated: 2026-10-31T02:44:16.636891+06:00
tags:
    - release-qa
    - pause
    - customer-portal
    - validation
    - release-blocker
class: standard
---

C4 — Customer Pause has no required-reason control and backend rejects the action

- Severity high functional defect; customer Pause workflow blocked when RequirePauseReason=true. ActiveQAprogress#3/#5, scheduled2026-10-31, planqa/release-2026-10-31-0208/plan.md.
- Affectedsubscription109453; initialorder109452,earlyrenewal109485 (nofinancialeffectsfromPause). Customer3252rqa0208-a@example.invalid,rolecustomer; coordinatoradmin1configuredprofile.
- ExactURL https://mirror-help.arrayhash.com/my-account/as-view-subscription/109453/ . Browserrqa0208-commerce-customer-a,ProfileC. Bothpluginsactive. CoordinatorconfirmedPauseenabled,customerpause/resumeallowed,max30days,max2,cooldown0,RequireReason=true,fullyrestrictedaccess;Skipfalse.
- Steps: openownedactivepaidsubscription;PauseSubscription;cancelinitialdayspromptverifiesActive/Dec31unchanged;reopen;enter1day;Continue. ConfirmationhasnoReasoninput. AcceptPauseonce.
- Expected: allowenterrequiredreason,validateemptyinputlocally,andcompletePausewhenvalid.
- Actual: dayspromptgoesdirectlytoPauseconfirmationwithoutreasoncontrol;acceptedrequestshowsProcessingdisabledthenin-modalerror "A pause reason is required." Customerhasnoinputtoprovidereason,soactioncannotcomplete. CancelreturnsunchangedActive,nextDec31,existingtwoorders.
- Proof: evidence/commerce-pause-confirm-no-reason.png and commerce-pause-error.png visuallyinspected; commerce-pause-error.txt; coordinator evidence/pause-enabled.txt confirmsrequiredpolicy. Twoacceptedattemptsconfirmedwithin90sec;reprovideoevidence/commerce-pause-repro.webm,secondresultcommerce-pause-repro-error.png.
- Knownscope: onlyRequireReason=trueprofiletestedsofar;baselinePauseoffproperlyhidbutton. Backendcorrectlyenforcesrequiredreason;frontendcannotfulfillit. Nochangecommittedtosubscriptionandnofinancialoperationpending. Productcodeuntouched.


Coordinatortriage: CRITICAL release-blocking required-reasoncustomerworkflowfailure on deployedtarget. CounterexampleRequirePauseReason=false:Pause1daycommittedPaused,nextDec31→Jan1,adminscheduledresumeNov1;ResumeNowProcessingdisabled→Active,nextDec31plusactualelapsedpause(~1minute). Sameexistingtwoorders,nocharge. OriginalPause/customer/resumeflagsfalse andRequireReasontrue subsequentlyrestored+reload/hiddenUIverified (evidence/actions-restored.txt,pause-hidden-values-restored.txt). ThisdoesnotclaimallPausemodesfailorcurrentlocalsourcewasdeployed.
