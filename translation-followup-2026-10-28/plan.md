# Missing Spanish catalogs in Loco Translate

Final user-confirmed scope, 2026-10-28. Issue #135. Local site http://localhost:10013.
Leave the existing ArraySubs translation UI unchanged. Fix only missing Spanish
catalogs. Manual execution in this session; no recurring automation.

1. Confirm Loco reports no translations and both plugin languages directories
   contain templates only. Cached sample/integration output was not an installed
   complete translation.
2. Run the existing Luna workflow for complete Spanish catalogs in core and Pro.
   Preserve validated progress, placeholders, brands and plural forms.
3. Regenerate templates to match the unchanged source/current builds, reuse
   completed memory, and install complete PO, MO and script JSON.
4. Verify both domains in the actual Loco overview/editor, complete catalog counts
   and representative PHP/JavaScript translations. No user locale, source setting,
   storefront behavior, purchase, email or UI feature changes.
5. Record output paths and results. Do not run other target languages.
