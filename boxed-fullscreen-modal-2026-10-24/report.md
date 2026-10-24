# Boxed fullscreen modals and progress cards

Verified locally on 2026-10-24 against http://localhost:10013 as an administrator.

Easy Setup and Cancellation Flow use 1200px maximum inner widths; product creation uses 800px. All three use the reusable `boxed-fullscreen-modal` variant. The actual scroll element spans the viewport, while only the inner header, body, progress and footer content is constrained. Header and footer dividers end at the padded content edges. Fixed regions compensate for conventional scrollbar width.

The titled step progress in Easy Setup and Cancellation Flow now matches the product progress card: background rgb(246,247,247), 1px rgb(226,229,233) border, 8px radius, and 12px by 16px padding. All three retain the WordPress admin accent color.

## Browser verification

86 recorded checks passed across the final layouts in checks.json:

- Cancellation Flow: 1920x720 and 390x844.
- Easy Setup: 1920x720 and 390x844.
- Product creation: 1920x600 and 390x600, using Product details so content overflows.
- Conventional scrollbars: 390x844 with a real 16px visible scrollbar; measured compensation was 16px and all inner regions remained 374px wide.
- Native wheel input over both side margins changed scrollTop, while headers and footers stayed fixed. Product progress stayed above the scrolling area.
- No horizontal overflow; modal panels and scroll hit areas spanned the viewport.
- Screenshots were visually inspected. Final screenshots correspond to the labels in checks.json.

Cancellation Continue reached Retention Offers and reset scroll/focus to the step heading. Review navigation worked and saving remained disabled with no changes. Product type advanced to Product details, reset scroll/focus, and empty required fields still produced visible validation from the relocated footer.

The nested Easy Setup discard dialog remained a normal 400px dialog above the fullscreen editor. Dismissing it with Escape left the parent open and the page locked. Repeated Escape dismissal/reopening intermittently stalled headless Chrome/CDP; those repeated cycles are not counted as passed. Browser startup also failed once before a successful isolated retry. Layout and scroll checks were completed in isolated sessions. Native wheel input used agent-browser's documented stream protocol because the CLI wheel command emitted coordinates (0,0) even after moving the pointer.

These checks did not save settings or create products. Customer cancellation behavior had already been covered in ../cancellation-flow-wizard-2026-10-24/report.md; this pass targeted admin modal layout.

Production build and source whitespace checks passed. Modified source files are below 3000 lines. Pro uses its separate modal implementation and has no dependency on the removed setup-specific layout classes. The temporary authentication token and browser sessions were cleaned up.
