# Release coverage

Inventory and worker shards will be merged before the deadline. Rows distinguish render, interaction, save+reload and end-to-end.

| capability | current URL | role | profile/fixture | check depth | expected → observed | result | proof/issue |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Plugins / activation baseline | https://mirror-help.arrayhash.com/wp-admin/plugins.php | admin | A | render | both active → core2.1.0/Pro1.3.0 active plus Woo/Stripe | PASS | evidence/plugins.txt; evidence/plugins.png |
| Modules | https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/modules | admin | A | render | modules configurable → six always-on,11 optional all on; Save disabled until change | PASS | evidence/modules-snapshot.txt; evidence/modules.png |
| General settings | https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/settings/general | admin | A / logs30→31→30 | save+reload | value persists after reload, visible Saving… →31 verified, restored30 | PASS | evidence/settings-general-before.png; evidence/settings-general-saved.png; evidence/settings-general-restored.txt |
