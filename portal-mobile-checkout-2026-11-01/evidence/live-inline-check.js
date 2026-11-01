(() => {
  const frame = document.querySelector('iframe[title*="secure checkout"]');
  const source = frame ? new URL(frame.src) : null;
  return {
    url: location.origin + location.pathname,
    heading: document.querySelector('h1')?.textContent,
    bodyOverflow: getComputedStyle(document.body).overflow,
    insidePaymentSection: Boolean(frame?.closest('section[aria-label="Payment details"]')),
    modalAncestor: Boolean(frame?.closest('[role="dialog"], [aria-modal="true"]')),
    framePosition: frame ? getComputedStyle(frame).position : null,
    checkoutOrigin: source?.origin,
    mode: source?.searchParams.get('mode'),
    coupon: source?.searchParams.get('coupon'),
    trial: source?.searchParams.get('trial'),
    viewport: { width: innerWidth, height: innerHeight, scrollWidth: document.documentElement.scrollWidth },
    busy: document.querySelector('section[aria-label="Payment details"]')?.getAttribute('aria-busy'),
  };
})()
