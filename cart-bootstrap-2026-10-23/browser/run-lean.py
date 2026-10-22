"""Real keyboard UI checks for owned mixed carts or a single monthly item."""
from pathlib import Path
import json
import re
import subprocess
import sys

session, mode, role, case = sys.argv[1:5]
out = Path(__file__).parent / 'after' / 'matrix' / f'{mode}-{role}' / case
out.mkdir(parents=True, exist_ok=True)
tag = f'{mode}-{role}-{case}'


def run(*args, js=None):
    r = subprocess.run(['agent-browser', '--session', session, *args], input=js, capture_output=True, text=True)
    if r.returncode:
        raise RuntimeError(r.stderr or r.stdout)
    return r.stdout


def evaluate(js):
    return json.loads(run('eval', '--stdin', js=js))


def click(label):
    snap = run('snapshot', '-i')
    ref = re.search(r'- (?:button|link) "' + re.escape(label) + r'"[^\n]*?ref=(e\d+)', snap)
    if not ref:
        raise RuntimeError('Missing control: ' + label)
    run('focus', '@' + ref.group(1))
    run('press', 'Enter')


def total(amount):
    run('wait', '--fn', '(()=>{const s=wp.data.select("wc/store/cart");return s.getCartTotals().total_price===' + json.dumps(str(amount * 100)) + '&&!s.hasPendingItemsOperations();})()')


def capture(stage):
    text = re.sub(r'Go back as admin \([^)]*\)', 'Go back as admin ([email redacted])', run('snapshot', '-i'))
    (out / f'{stage}-snapshot.txt').write_text(text)
    d = evaluate('''(()=>{const scripts=Array.from(document.scripts);const s=wp.data.select('wc/store/cart');const d=s.getCartData();let c;try{c=wp.data.select('wc/store/checkout')}catch{}return {url:location.href,currentUserId:Number(window.wcSettings?.currentUserId||0),reducedMotion:matchMedia('(prefers-reduced-motion: reduce)').matches,mainCartText:document.querySelector('.wp-block-woocommerce-cart')?.innerText||null,items:d.items.map(i=>({id:i.id,name:i.name,quantity:i.quantity,lineTotal:i.totals.line_total})),total:d.totals.total_price,errors:s.getCartErrors(),miniCartModule:scripts.some(s=>s.src.includes('/woocommerce/mini-cart.js')),productButtonModule:scripts.some(s=>s.src.includes('/woocommerce/product-button.js')),malformedRequests:performance.getEntriesByType('resource').filter(r=>r.name.includes('undefinedwc/')).map(r=>r.name),checkoutDraftOrderId:c?.getOrderId?.()||0}})()''')
    (out / f'{stage}.json').write_text(json.dumps(d, indent=2) + '\n')
    print(tag, stage, 'total=' + d['total'], flush=True)
    assert d['currentUserId'] == (438 if role == 'customer' else 0)
    assert not d['malformedRequests'] and not d['errors']
    return d


run('network', 'requests', '--clear')
run('set', 'media', 'reduced-motion')
run('open', f'http://localhost:10013/cart-2/?qa-cart90={tag}-empty')
run('wait', '--text', 'Your cart is currently empty!')
capture('empty')
for product in (['ordinary', 'subscription'] if case == 'mixed' else ['subscription']):
    run('open', f'http://localhost:10013/product/qa-cart-90-{product}/?qa-cart90={tag}-add')
    identity = evaluate('Number(window.wcSettings?.currentUserId||0)')
    assert identity == (438 if role == 'customer' else 0)
    snap = run('snapshot', '-i')
    button = re.search(r'- button "(Purchase now|Subscribe Now|Add to cart)"[^\n]*?ref=(e\d+)', snap)
    assert button, 'Purchase control missing'
    run('focus', '@' + button.group(2))
    run('press', 'Enter')
    run('wait', '--text', 'Billing address')
    capture(f'add-{product}-automatic-checkout')
run('open', f'http://localhost:10013/cart-2/?qa-cart90={tag}-items')
run('wait', '--text', 'QA Cart 90 Subscription')
total(37 if case == 'mixed' else 12)
d = capture('mixed-37' if case == 'mixed' else 'monthly-12')
assert 'every 1 month' in d['mainCartText'] and 'Continues until cancelled' in d['mainCartText']
if case == 'mixed':
    click('Increase quantity of QA Cart 90 Ordinary')
    total(62)
    capture('ordinary-quantity-two-total-62')
    click('Proceed to Checkout')
    run('wait', '--text', 'Billing address')
    capture('checkout-navigation-only')
    run('open', f'http://localhost:10013/cart-2/?qa-cart90={tag}-remove')
    run('wait', '--text', 'QA Cart 90 Ordinary')
    total(62)
    click('Remove QA Cart 90 Ordinary from cart')
    total(12)
    capture('ordinary-removed-monthly-12')
click('Remove QA Cart 90 Subscription from cart')
run('wait', '--text', 'Your cart is currently empty!')
total(0)
capture('removed-empty')
for filename, args in [('requests.txt', ['network', 'requests']), ('errors.json', ['errors', '--json'])]:
    text = re.sub(r'([?&](?:_wpnonce|security|nonce|key|cart_token)=)[^&\s]+', r'\1REDACTED', run(*args))
    (out / filename).write_text(text)
(out / 'result.json').write_text(json.dumps({'pass': True, 'role': role, 'mode': mode, 'case': case, 'customer_id': 438 if role == 'customer' else 0, 'totals': [37, 62, 12, 0] if case == 'mixed' else [12, 0], 'no_order_submission': True}, indent=2) + '\n')
print(tag, 'PASS', flush=True)
