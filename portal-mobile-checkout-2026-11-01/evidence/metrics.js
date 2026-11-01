(() => {
  const frame = document.querySelector('iframe[title*="secure checkout"]');
  const rect = frame?.getBoundingClientRect();
  const source = frame ? new URL(frame.src) : null;
  return {
    url: location.origin + location.pathname,
    viewport: { width: innerWidth, height: innerHeight, scrollWidth: document.documentElement.scrollWidth, scrollHeight: document.documentElement.scrollHeight },
    userAgent: navigator.userAgent,
    maxTouchPoints: navigator.maxTouchPoints,
    coarsePointer: matchMedia('(pointer: coarse)').matches,
    bodyOverflow: getComputedStyle(document.body).overflow,
    visibleDialogs: [...document.querySelectorAll('[role="dialog"], [aria-modal="true"]')].filter((node) => node.getClientRects().length && getComputedStyle(node).visibility !== 'hidden' && node.getAttribute('aria-hidden') !== 'true').map((node) => node.getAttribute('aria-label')),
    checkout: rect ? { width: rect.width, height: rect.height, left: rect.left, right: rect.right, plan: source.searchParams.get('plan_id'), coupon: source.searchParams.get('coupon'), trial: source.searchParams.get('trial'), mode: source.searchParams.get('mode') } : null,
    busy: document.querySelector('section[aria-label="Payment details"]')?.getAttribute('aria-busy'),
  };
})()
