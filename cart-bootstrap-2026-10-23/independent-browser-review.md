# Independent browser-evidence review — task 90

Reviewed the completed Pro and Free artifacts saved by `qa_access`; did not drive a browser or mutate site state. Source review is in `source-review.md`. The reviewed source fix is merged as `8a03618`.

| Mode / case | Concrete evidence reviewed | Result |
| --- | --- | --- |
| Pro / guest ordinary product | `browser/after/matrix/pro-guest/ordinary/`: result, stage JSON, native snapshots, requests, errors | Quantity 1→2 and totals 25→50; final items empty / total 0. Product-page Mini-Cart opens and updates 25→50. |
| Pro / guest monthly subscription | `browser/after/matrix/pro-guest/subscription/`: same artifacts | Totals 12→24→0, quantity 1→2, monthly renewal terms update to 24. Product-page drawer remains active. |
| Pro / owned customer ordinary | `browser/after/matrix/pro-customer/ordinary/`: same artifacts | Totals 25→50→0; drawer works; `currentUserId` is consistently 438. |
| Pro / owned customer mixed cart | `browser/after/matrix/pro-customer/mixed/`: same artifacts | Ordinary + monthly total 37; ordinary quantity 2 gives 62; removing ordinary leaves monthly 12; final removal gives empty items / total 0. |
| Free / guest ordinary product | `browser/after/matrix/free-guest/ordinary/`: same artifacts | Totals 25→50→0; quantity and drawer updates work; Checkout navigation keeps total 50. |
| Free / guest monthly subscription | `browser/after/matrix/free-guest/monthly/`: same artifacts | Settled monthly terms and total 12; removal returns empty / 0. Quantity-two was not repeated in this Free monthly case. |
| Free / owned customer mixed cart | `browser/after/matrix/free-customer/mixed/`: same artifacts | Totals 37→62→12→0 with user 438; ordinary quantity and both removals are reflected in UI and state. |

`browser/after/matrix/customer-identity.json` independently identifies the owned customer as user 438 with the expected display name/email match. Guest stages identify user 0. The native snapshots include actual quantity spinbuttons and settled Cart/Checkout text, rather than relying solely on summary `pass` flags.

All seven request inventories were searched directly: no `undefinedwc`/malformed Cart URL occurs, Cart reads return 200, quantity updates return 200, and batched Cart operations return 207. There is no direct Store API Checkout POST in these inventories. Per-stage malformed-request arrays and captured page-error arrays are empty; recorded checkout draft IDs are zero. Checkout evidence proves navigation/rendering and matching cart totals; it does not claim a placed order or payment.

On configured Cart and Checkout stages, the Mini-Cart module is absent and the fallback is hidden or not present. On normal product pages, the Mini-Cart module remains present, the header is visible, the drawer contains the expected product, and the shared Cart REST URL is valid. Thus the patch has not globally suppressed the Mini-Cart.

The ordinary Cart stages preserve legitimate cross-sell interactivity: `productButtonModule` is true, the `woocommerce`, product-button/products/store-notices/router namespaces remain, and `sharedCartRestUrl` is `http://localhost:10013/wp-json/`, while only the unnecessary Mini-Cart module is absent. Earlier pointer-driven screenshots under `browser/after/pro-guest/` additionally show the cross-sell offered at 25, the added cross-sell yielding Cart total 32, and the same 32 on Checkout. Those screenshots were visually inspected. They support successful cross-sell addition; an independent per-stage request/state file for that exact earlier click was not saved alongside those PNGs, so this review does not separately claim a complete network trace for that individual click.

The browser worker documents a capture/rendering-tool freeze during later repeated interactions and uses reduced-motion emulation plus native focus/Enter for the matrix. No injected page or transport patch is reported. The matrix has native snapshots, runtime state and request logs; the earlier pointer-driven drawer/cross-sell screenshots provide visual context. This limitation does not invalidate the recorded native Cart actions, but the matrix should not be described as a full pointer/motion visual regression.

No blocking evidence gap was found for the completed representative Pro/Free scope above. The initial main Cart skeleton was transitional, and the post-fix artifacts demonstrate settled UI and removal. This review does not claim the patch fixed a separate main Cart hydration bug or that every product/gateway/theme/control permutation was tested.

`browser/state-free.json` records seven active plugins with ArraySubs Pro absent. Supplemental `render-contract-pro.json` and `render-contract-free.json` both pass seven native-render/public-conditional checks, with the latter explicitly reporting `pro_active: false`; these supplement the actual browser matrix. Final fixture cleanup and exact activation/Coming Soon restoration are owned by the lead and were still underway when this review was written; they are not asserted complete here.
