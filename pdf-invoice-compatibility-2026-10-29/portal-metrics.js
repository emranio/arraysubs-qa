({
  title: document.title,
  status: performance.getEntriesByType('navigation')[0]?.responseStatus,
  criticalError: document.body.innerText.includes('critical error'),
  rows: Array.from(document.querySelectorAll('.subscription-orders tbody tr, .woocommerce-orders-table tbody tr')).map(row => ({
    order: row.querySelector('a')?.textContent.trim(),
    text: row.textContent.trim().replace(/\s+/g, ' '),
    actions: Array.from(row.querySelectorAll('a.button')).map(a => ({
      name: a.textContent.trim(), classes: a.className, target: a.target, rel: a.rel,
      path: new URL(a.href).pathname,
      queryKeys: Array.from(new URL(a.href).searchParams.keys()),
    })),
  })),
})
