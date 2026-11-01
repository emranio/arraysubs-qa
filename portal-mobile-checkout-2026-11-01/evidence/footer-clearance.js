(() => {
  const link = [...document.querySelectorAll('footer a')].find((node) => node.textContent.trim() === 'Refund Policy');
  const launcher = document.querySelector('[aria-label="Open Accessibility Tools menu"]');
  const policy = link.getBoundingClientRect();
  const control = launcher.getBoundingClientRect();
  return {
    viewport: { width: innerWidth, height: innerHeight, scrollWidth: document.documentElement.scrollWidth },
    refund: policy.toJSON(),
    launcher: control.toJSON(),
    overlap: policy.left < control.right && policy.right > control.left && policy.top < control.bottom && policy.bottom > control.top,
    rightEdgeHitsPolicy: document.elementFromPoint(policy.right - 5, policy.top + policy.height / 2)?.closest('a') === link,
    footerPaddingBottom: getComputedStyle(document.querySelector('footer nav')).paddingBottom,
  };
})()
