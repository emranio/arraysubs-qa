# Fixed step progress cards

Verified on http://localhost:10013 on 2026-10-24 as an administrator.

Easy Setup and Cancellation Flow now render ModalProgress in the shared boxed fullscreen modal's beforeContent region, above the scrolling form, matching product creation. Card styling and 1200px alignment are preserved. Cancellation progress no longer tries to scroll the form to its active step marker.

50 browser checks passed across Cancellation Flow and Easy Setup at 1920x900 and 390x844, including the Easy Setup completion view and the actual question form. In every case, the body scrolled while the progress card remained at y=56px; the header and footer also stayed fixed. Scroll areas still span the viewport, cards remain visible, and there is no horizontal overflow. See checks.json and paired top/scrolled screenshots, which were visually reviewed.

While Cancellation Flow was scrolled, clicking the fixed Retention Offers step opened that step, reset body scroll to zero, and focused the step heading. Easy Setup's Next button similarly advanced from Your Business to Billing & Renewal Rules with scroll zero and heading focus. No settings were saved or products created.

Production build and source whitespace checks passed; all three modified source files are below 3000 lines. Product creation's existing beforeContent usage and Pro's separate modal implementation require no changes. The temporary authentication session and browser were cleaned up.
