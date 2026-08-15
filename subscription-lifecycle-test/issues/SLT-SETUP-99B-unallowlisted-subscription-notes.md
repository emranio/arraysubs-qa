# SLT-SETUP-99B ownership closure fails on unallowlisted subscription notes

## Scope

- Task: 119, SLT-SETUP-99B
- Stage: step 4 ownership-closure preflight, evaluated read-only before M0, tail cancellation, action cancellation, or deletion
- Plan path: kanban/tasks/119-slt-setup-99b-post-watch-teardown-on-2026-08-15.md
- Site: https://mirror-help.arrayhash.com
- Observation window: 2026-08-15 06:13-06:30 site time (UTC+6)
- Browser session: admin-SLT-SETUP-99B
- Browser routes used for prerequisite evidence:
  - /wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions/detail/12039
  - /wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions/detail/12172
  - /wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions/detail/12749
  - /wp-admin/admin.php?page=wc-orders&action=edit&id=26323
  - /wp-admin/admin.php?page=wc-orders&action=edit&id=26536
  - /wp-admin/admin.php?page=wc-orders&action=edit&id=26557
- Runtime ownership checks: read-only WP-CLI from the WordPress root with --allow-root

## Verdict

BLOCKED. Task 119 explicitly permits deletion of exact SLT products, coupons, users, orders, subscriptions, the classic cart and checkout pages, and the registry page. It does not enumerate arraysubs_sub_note posts. Live ownership closure finds exactly 343 such posts authored by the 22 allowlisted teardown users. Those note IDs are not in an authorized deletion allowlist.

Task 119 step 4 says every owned object must be allowlisted or absent, and that any unallowlisted object requires a STOP before deletion with all live artifacts preserved. Expanding the allowlist here would exceed the user's instruction to remove only artifact classes explicitly enumerated by task 119. No cancellation, scheduled-action mutation, deletion, reassignment, or Mailpit mutation was performed.

## Expected

Every object owned by users 347-369 in the exact teardown-user set is either absent or explicitly covered by task 119's deletion allowlist.

## Actual

- 343 existing arraysubs_sub_note posts are authored by allowlisted users but unallowlisted.
- 143 point to existing subscriptions in the known 49-subscription teardown registry.
- 188 have _subscription_id=0 and therefore cannot be treated as subscription-cascade children.
- 12 point to already-absent subscriptions 18932 or 26058.
- No user-owned note lacks _subscription_id metadata.
- No user-owned note points to an existing subscription outside the known 49-subscription registry.
- An all-author orphan read finds 35 notes linked to absent subscriptions 18932 and 26058. The 12 above overlap this set; the other 23 were authored by 0 or administrator user 1.
- Comments owned by the 22 users: zero.
- The only live post types authored by those users are arraysubs_data, arraysubs_sub_note, and shop_order_placehold. The placeholder IDs mirror the separately enumerated HPOS orders.

## Exact affected users and unallowlisted note IDs

Every listed user has WordPress role customer.

### User 347 - slt-core - slt-core@example.test - 71 notes

- Existing allowlisted-subscription links, 36: 11960, 11961, 11967, 11968, 11969, 12004, 12005, 12012, 12013, 12014, 12018, 12019, 12025, 12026, 12027, 12235, 12236, 12241, 12242, 12243, 12656, 12657, 12662, 12663, 12664, 13123, 13124, 13159, 13160, 13161, 13166, 13167, 13448, 13449, 13473, 13474
- Existing target subscription IDs: 11959, 12003, 12017, 12234, 12655
- Absent target 18932, 5: 18933, 18934, 18939, 18940, 18941
- _subscription_id=0, 30: 11950, 11951, 11952, 11953, 11954, 11955, 11956, 11957, 11958, 11962, 11966, 12006, 12011, 12020, 12024, 12237, 12240, 12658, 12661, 13122, 13447, 13472, 15637, 15641, 18935, 18938, 18954, 18957, 18965, 18968

### User 348 - slt-trial - slt-trial@example.test - 0 notes

### User 349 - slt-switch - slt-switch@example.test - 0 notes

### User 350 - slt-flex - slt-flex@example.test - 22 notes

- Existing allowlisted-subscription links, 10: 12040, 12041, 12047, 12048, 12049, 12565, 12566, 12571, 12572, 12573
- Existing target subscription IDs: 12039, 12564
- _subscription_id=0, 12: 12030, 12031, 12032, 12033, 12034, 12035, 12036, 12037, 12038, 12042, 12046, 12570

### User 351 - slt-fail - slt-fail@example.test - 0 notes

### User 352 - slt-paddle - slt-paddle@example.test - 31 notes

- Existing allowlisted-subscription links, 11: 12640, 12641, 13345, 13346, 20115, 20116, 20141, 20142, 20143, 20144, 20145
- Existing target subscription IDs: 12639, 13344, 20114
- Absent target 26058, 7: 26059, 26060, 26079, 26080, 26081, 26082, 26083
- _subscription_id=0, 13: 12630, 12631, 12632, 12633, 12634, 12635, 12636, 12637, 12638, 12642, 13347, 20117, 26061

### User 353 - slt-admincreated - slt-admincreated@example.test - 0 notes

### User 354 - slt-flex2 - slt-flex2@example.test - 16 notes

- Existing allowlisted-subscription links, 5: 12173, 12174, 12180, 12181, 12182
- Existing target subscription ID: 12172
- _subscription_id=0, 11: 12163, 12164, 12165, 12166, 12167, 12168, 12169, 12170, 12171, 12175, 12179

### User 355 - slt-flex3 - slt-flex3@example.test - 22 notes

- Existing allowlisted-subscription links, 10: 12194, 12195, 12201, 12202, 12203, 13278, 13279, 13284, 13285, 13286
- Existing target subscription IDs: 12193, 13277
- _subscription_id=0, 12: 12184, 12185, 12186, 12187, 12188, 12189, 12190, 12191, 12192, 12196, 12200, 13283

### User 357 - slt-core2 - slt-core2@example.test - 11 notes

- Existing allowlisted-subscription links, 9: 11992, 11993, 11998, 11999, 12000, 13258, 13259, 13263, 13264
- Existing target subscription ID: 11991
- _subscription_id=0, 2: 11997, 13260

### User 358 - slt.invoice - slt-invoice@example.test - 25 notes

- Existing allowlisted-subscription links, 7: 12148, 12149, 12152, 12153, 12158, 12159, 12160
- Existing target subscription ID: 12147
- _subscription_id=0, 18: 12132, 12133, 12134, 12135, 12136, 12137, 12138, 12139, 12140, 12141, 12142, 12143, 12144, 12145, 12146, 12150, 12151, 12157

### User 359 - slt.guest - slt-guest-d0@example.test - 22 notes

- Existing allowlisted-subscription links, 5: 12222, 12223, 12229, 12230, 12231
- Existing target subscription ID: 12221
- _subscription_id=0, 17: 12206, 12207, 12208, 12209, 12210, 12211, 12212, 12213, 12214, 12215, 12216, 12217, 12218, 12219, 12220, 12224, 12228

### User 360 - slt-eml - slt-eml@example.test - 18 notes

- Existing allowlisted-subscription links, 7: 12264, 12265, 12271, 12272, 12273, 12820, 12821
- Existing target subscription ID: 12263
- _subscription_id=0, 11: 12254, 12255, 12256, 12257, 12258, 12259, 12260, 12261, 12262, 12266, 12270

### User 361 - slt-cpnrec - slt-cpnrec@example.test - 19 notes

- Existing allowlisted-subscription links, 6: 12319, 12320, 12321, 12327, 12328, 12329
- Existing target subscription ID: 12318
- _subscription_id=0, 13: 12296, 12297, 12298, 12299, 12300, 12301, 12302, 12303, 12304, 12309, 12313, 12322, 12326

### User 362 - slt-cpnfirst - slt-cpnfirst@example.test - 9 notes

- Existing allowlisted-subscription links, 5: 12333, 12334, 12339, 12340, 12341
- Existing target subscription ID: 12332
- _subscription_id=0, 4: 12330, 12338, 15584, 15596

### User 363 - slt-chk-qty - slt-chk-qty@example.test - 7 notes

- Existing allowlisted-subscription links, 5: 12685, 12686, 12692, 12693, 12694
- Existing target subscription ID: 12684
- _subscription_id=0, 2: 12687, 12691

### User 364 - slt-cpnrej - slt-cpnrej@example.test - 7 notes

- Existing allowlisted-subscription links, 5: 12720, 12721, 12726, 12727, 12728
- Existing target subscription ID: 12719
- _subscription_id=0, 2: 12717, 12725

### User 365 - slt-qty - slt-qty@example.test - 7 notes

- Existing allowlisted-subscription links, 5: 12750, 12751, 12756, 12757, 12758
- Existing target subscription ID: 12749
- _subscription_id=0, 2: 12747, 12755

### User 366 - slt-email - slt-email@example.test - 16 notes

- Existing allowlisted-subscription links, 5: 12787, 12788, 12794, 12795, 12796
- Existing target subscription ID: 12786
- _subscription_id=0, 11: 12777, 12778, 12779, 12780, 12781, 12782, 12783, 12784, 12785, 12789, 12793

### User 367 - slt-grouped - slt-grouped@example.test - 0 notes

### User 368 - slt-chk-mixed - slt-chk-mixed@example.test - 16 notes

- Existing allowlisted-subscription links, 5: 13332, 13333, 13339, 13340, 13341
- Existing target subscription ID: 13331
- _subscription_id=0, 11: 13322, 13323, 13324, 13325, 13326, 13327, 13328, 13329, 13330, 13334, 13338

### User 369 - slt-cancel - slt-cancel@example.test - 24 notes

- Existing allowlisted-subscription links, 7: 13403, 13404, 13410, 13411, 13412, 13413, 13414
- Existing target subscription ID: 13402
- _subscription_id=0, 17: 13387, 13388, 13389, 13390, 13391, 13392, 13393, 13394, 13395, 13396, 13397, 13398, 13399, 13400, 13401, 13405, 13409

## Exact notes linked to already-absent subscriptions, all authors

These are an additional orphan/isolation concern. The 12 customer-authored rows above are included here, so this section is not additive to the 343 count.

- Absent subscription 18932, 13 notes: 18933:347, 18934:347, 18936:0, 18937:0, 18939:347, 18940:347, 18941:347, 18943:0, 18945:1, 18946:1, 18947:1, 18948:1, 18949:1
- Absent subscription 26058, 22 notes: 26059:352, 26060:352, 26062:0, 26064:0, 26065:0, 26066:0, 26067:0, 26073:0, 26074:0, 26075:0, 26076:0, 26078:0, 26079:352, 26080:352, 26081:352, 26082:352, 26083:352, 26084:0, 26085:0, 26086:0, 26087:0, 26088:0

## Additional preserved unallowlisted residuals

These do not alter the exact 343-note ownership count, but they are relevant to the same safe-retry boundary.

### Two unallowlisted SLT-prefixed users

- 395 - sltobs01x_3325661d400143_customer_a - sltobs01x_3325661d400143_customer_a@example.invalid - subscriber.
- 396 - sltobs01x_3325661d400143_customer_b - sltobs01x_3325661d400143_customer_b@example.invalid - subscriber.

Both own zero posts, subscriptions, HPOS orders, and comments. They are preserved prefix matches and are not part of the 22-user deletion allowlist.

### Eighty-nine unallowlisted revision children of the three SLT pages

- Parent 11843: 11844.
- Parent 11845: 11846.
- Parent 11847: 11848, 11852, 11926, 11932, 11937, 11942, 11947, 11948, 11972, 12001, 12015, 12028, 12050, 12086, 12092, 12098, 12107, 12117, 12124, 12129, 12130, 12161, 12204, 12232, 12244, 12274, 12342, 12346, 12356, 12364, 12365, 12374, 12379, 12384, 12403, 12544, 12548, 12551, 12552, 12553, 12554, 12562, 12575, 12576, 12581, 12590, 12607, 12628, 12652, 12653, 12665, 12695, 12696, 12705, 12713, 12729, 12759, 12806, 12822, 12829, 13040, 13043, 13173, 13247, 13265, 13293, 13342, 13371, 13415, 13439, 13441, 13475, 13566, 13575, 13609, 13668, 13714, 13741, 13819, 13835, 13866, 14356, 14404, 14445, 14446, 14447, 14918.

They are not authored by the teardown users, so they are not part of the 343-note ownership failure. They remain unallowlisted objects, however, and deletion of parent pages 11843, 11845, or 11847 may cascade-delete them. Safe retry authorization must explicitly account for that consequence.

## Reproduction

Run from the WordPress root with --allow-root. These are read-only queries.

1. Resolve every teardown user by exact numeric ID and inspect wp_capabilities for IDs 347, 348, 349, 350, 351, 352, 353, 354, 355, 357, 358, 359, 360, 361, 362, 363, 364, 365, 366, 367, 368, 369.
2. Query wp_posts where post_type='arraysubs_sub_note' and post_author is in that exact ID set.
3. Join wp_postmeta on meta_key='_subscription_id'. Left join wp_posts as the target where post_type='arraysubs_data'.
4. Classify each row as subscription_id_zero, subscription_absent, subscription_existing_allowlisted, or subscription_existing_unallowlisted.
5. Group by author and class, count rows, and group-concatenate exact note IDs and target subscription IDs.
6. Separately query all authors for _subscription_id in (18932,26058) with the target subscription absent.
7. Query wp_comments by the same exact user IDs; result is zero.

Core classification expression:

    CASE
      WHEN CAST(sm.meta_value AS UNSIGNED)=0 THEN 'subscription_id_zero'
      WHEN s.ID IS NULL THEN 'subscription_absent'
      WHEN s.ID IN (<the exact 49-subscription registry>) THEN 'subscription_existing_allowlisted'
      ELSE 'subscription_existing_unallowlisted'
    END

## Safety outcome and unblock requirement

- IDs cancelled: none.
- Scheduled actions cancelled/run: none.
- IDs deleted or reassigned: none.
- Mailpit messages generated or removed: none.
- Shop Access or other settings changed: none.
- Task 119 must not enter review or done on this evidence.
- Safe retry requires an explicit plan/authority decision covering the note artifact class and exact note IDs, plus a reviewed treatment for the 188 zero-linked rows and 35 rows linked to already-absent subscriptions. It must also account for the 89 revision children that parent-page deletion can cascade-delete. A prefix-derived or inferred expansion is not sufficient.
