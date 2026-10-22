# Fresh task board — 2026-10-23

| Task | Owner | State |
| --- | --- | --- |
| Customer REST contracts, permissions and Pro dependencies | endpoint_audit + lead | complete; issue87 closed;22 cancellation checks passed |
| Account/storefront navigation, saved settings and feature privacy | integration_audit | complete; issues88/89 closed after browser acceptance |
| Real customer browser pages with Pro active and Free-only | qa_access | complete within documented scope; separate Woo Cart limitation90 |
| Supplemental real REST ownership checks | lead | complete;37Pro /22Free checks passed |
| Unavailable premium purchase / order-payment guards | lead + endpoint_audit | complete;47Free /49Pro checks and browser product/pay-page acceptance passed |
| Variation features / credit shortcode / avatar | integration_audit + lead | complete; rebuilt Pro assets and browser acceptance passed |
| Restore environment, remove fixtures and finalize evidence | lead + qa_access | complete; cleanup.json and state/final-comparison.json all pass |

Separate WooCommerce cart-interactivity request issue90 remains open. No WooCommerce/theme source changes are included. Initial variation screenshots used malformed label-only QA features; they are not valid proof for canonical feature rows. Final checks use corrected name-keyed fixture rows and passed all3selections/reset/privacy. Original configuration and customer224 state were restored; own test fixtures and sessions removed.
