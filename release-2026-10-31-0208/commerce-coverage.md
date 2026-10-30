# Commerce coverage

| capability | current URL | role | profile/fixture | check depth | expected → observed | result | proof/issue |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Plans list | https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/plans | admin | A | interaction |77 plans render; RQA0208 search gives empty before creation | PASS | evidence/commerce-plans.png; commerce-plans.txt |
| Simple plan wizard | https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/plans | admin | A/product109433 | save+reload pending | empty details rejected; Back preserves title/price;8 steps; cancel confirm returns review; Create visibly disabled/loading and completes | PASS creation, reload pending | evidence/commerce-wizard-invalid.png,commerce-wizard-review.png,commerce-create-loading.png |
