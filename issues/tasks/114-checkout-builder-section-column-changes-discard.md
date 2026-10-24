---
id: 114
title: Checkout Builder section column changes discard existing elements
status: closed
priority: high
created: 2026-10-25T02:24:30.72905+06:00
updated: 2026-10-25T02:31:02.309501+06:00
started: 2026-10-25T02:24:51.236558+06:00
completed: 2026-10-25T02:31:02.297722+06:00
tags:
    - bug
    - checkout-builder
    - admin-ui
class: standard
---

Active QA task ID and scheduled day: N/A; no active QA plan. Relevant plan markdown path: N/A.
Affected subscription and order IDs: N/A.
Affected WordPress user: ID 1, login admin, role administrator. Checkout customers: N/A.
URL: http://localhost:10013/wp-admin/admin.php?page=arraysubs-mainadmin#/checkout-builder
Browser context: isolated authenticated admin session arraysubs-section-bug; saved builder configuration was not changed.

Reproduction:
1. Drag a Section from the palette into the current step and leave its settings open.
2. Drag the existing Order Notes element into its first column.
3. Change Columns from 1 to 2.
Expected: Order Notes remains visible, filling the first column; extra elements after resizing are placed below the section in existing reading order.
Actual: Order Notes vanishes and both columns become empty. The header counts top-level items only, so its count does not expose the lost nested element.
Proof: evidence/114-section-columns/before-resize.png and evidence/114-section-columns/vanished.png. Browser snapshot changes Section1 colOrder Notes to Section2 colsColumn 1Drop element hereColumn 2Drop element here.

Known scope: Open section settings retain a stale editingField.children after DnD. A subsequent settings save replaces the latest section with that stale snapshot. The old resize helper also reverses removed-column ordering, and Pro sanitization slices off excess columns from mismatched payloads. Other field data and global builder settings remain unchanged.

Fix plan: Derive section structural changes from the latest step tree at commit time; preserve children on ordinary settings edits; on column changes fill columns from left to right and insert overflow immediately below the section. Add defensive Pro sanitization that preserves overflow rather than dropping it, focused regression checks, and repeat the real browser scenario before closing.

[[2026-10-25]] Sun 02:25
Confirmed current-tree overwrite in the real browser. Implementation now applies resize to the latest matched section and ignores panel child snapshots, fills columns left-to-right, and hoists overflow directly after the section. Label-only saves retain current column membership. Pro sanitizer recovery passed 27 isolated assertions; core helper passed 15 Jest cases. Rebuilding before end-to-end verification.

[[2026-10-25]] Sun 02:30
Final real-browser verification passed on rebuilt assets: open section retained Order Notes after dragging it in then changing 1→3; moving the currently edited Text field into a section kept later label edits working; 3→2 filled Order Notes and Contact preference left-to-right and moved Email below the section; selecting Email before the pending resize committed then renaming it to Reply email succeeded at its new top-level location; 2→1 moved Contact preference below the section before Reply email, preserving each exactly once; a subsequent section rename retained its latest children. Proof saved in evidence/114-section-columns/. No global builder configuration was saved during tests. Core regression helper/resolver: 24 Jest checks passed. Pro defensive sanitizer: 27 isolated WordPress assertions passed. Production build succeeded without warnings.

[[2026-10-25]] Sun 02:31
Cleanup complete: discarded the temporary draft in the shared confirmation dialog; Save and Discard returned to disabled, restoring the original saved checkout layout. All touched source files remain below 3000 lines. No lint/PHPCS run. Issue fixed and verified.
