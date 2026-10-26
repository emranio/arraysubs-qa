# Site header menu

Updated the actual local site at http://localhost:10013 through native WordPress navigation and Site Editor records.

The shared header menu contains, in order: Home, Shop, Checkout, Checkout Classic, Cart Page, and My Account. Links use the configured WooCommerce pages, with existing classic checkout page 9 kept separate from block checkout page 5756. Home links to the site root.

Saved navigation 23464, shared header 23465, and checkout template 23466. The checkout template now references the shared theme header so its menu matches the rest of the site. Its checkout content is preserved. Existing navigation 23461 and all plugin/theme source files were preserved.

Browser verification passed on all six destinations; both checkout forms rendered with the six-item menu and no page overflow. The mobile menu opened correctly at 390px and showed all six links. A temporary item was added only to the isolated guest cart to view checkout, then removed; the final cart count was zero. No order was placed.

Evidence: [verification results](verification.json), [desktop header](evidence/shop-desktop.png), [block checkout](evidence/page-2.png), and [mobile navigation](evidence/mobile-menu-open.png). Original template and navigation records are saved in [before.json](before.json), with the saved record IDs and destinations in [changes.json](changes.json).
