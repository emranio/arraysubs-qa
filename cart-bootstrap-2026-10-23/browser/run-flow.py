"""Real agent-browser UI flow for owned Cart 90 fixtures; no order submission."""
from pathlib import Path
import json
import re
import subprocess
import sys

session, mode, role, product = sys.argv[1:5]
is_subscription = product == 'subscription'
name = 'QA Cart 90 Subscription' if is_subscription else 'QA Cart 90 Ordinary'
price = 12 if is_subscription else 25
customer_id = 438 if role == 'customer' else 0
tag = f'{mode}-{role}-{product}'
out = Path(__file__).parent / 'after' / 'matrix' / f'{mode}-{role}' / product
out.mkdir(parents=True, exist_ok=True)


def run(*args, js=None):
    result = subprocess.run(['agent-browser', '--session', session, *args], input=js, capture_output=True, text=True)
    if result.returncode:
        raise RuntimeError(result.stderr or result.stdout)
    return result.stdout


def snapshot():
    return run('snapshot', '-i')


def click(kind, label):
    state = snapshot()
    match = re.search(r'- ' + re.escape(kind) + r' "' + re.escape(label) + r'"[^\n]*?ref=(e\d+)', state)
    if not match:
        raise RuntimeError(f'Missing {kind}: {label}')
    run('focus', '@' + match.group(1))
    run('press', 'Enter')


def evaluate(js):
    return json.loads(run('eval', '--stdin', js=js))


def wait_total(amount):
    condition = '(()=>{const s=window.wp?.data?.select?.("wc/store/cart");return s?.getCartTotals?.()?.total_price===' + json.dumps(str(amount * 100)) + '&&!s.hasPendingItemsOperations();})()'
    run('wait', '--fn', condition)


def capture(stage):
    visible = re.sub(r'Go back as admin \([^)]*\)', 'Go back as admin ([email redacted])', snapshot())
    (out / f'{stage}-snapshot.txt').write_text(visible)
    # Settled DOM/runtime evidence follows already-inspected visual controls.
    # Screenshot capture stalled in the original sessions; do not block UI QA.
    data = evaluate('''(()=>{const scripts=Array.from(document.scripts);const j=scripts.find(s=>s.id==='wp-script-module-data-@wordpress/interactivity');const state=j?JSON.parse(j.textContent).state:{};const c=window.wp?.data?.select?.('wc/store/cart');const d=c?.getCartData?.();let x;try{x=window.wp?.data?.select?.('wc/store/checkout')}catch{}return {url:location.href,currentUserId:window.wcSettings?.currentUserId,miniCartModule:scripts.some(s=>s.src.includes('/woocommerce/mini-cart.js')),productButtonModule:scripts.some(s=>s.src.includes('/woocommerce/product-button.js')),interactivityNamespaces:Object.keys(state||{}),sharedCartRestUrl:state?.woocommerce?.restUrl||null,malformedRequests:performance.getEntriesByType('resource').filter(r=>r.name.includes('undefinedwc/')).map(r=>r.name),miniCartVisibility:Array.from(document.querySelectorAll('.wp-block-woocommerce-mini-cart')).map(e=>getComputedStyle(e).visibility),mainCartText:document.querySelector('.wp-block-woocommerce-cart')?.innerText||null,drawerText:document.querySelector('.wc-block-mini-cart__drawer')?.innerText||null,items:d?.items?.map(i=>({id:i.id,name:i.name,quantity:i.quantity,lineTotal:i.totals?.line_total})),total:d?.totals?.total_price,cartErrors:c?.getCartErrors?.(),checkoutDraftOrderId:x?.getOrderId?.()||0}})()''')
    (out / f'{stage}.json').write_text(json.dumps(data, indent=2) + '\n')
    print(tag, stage, 'total=' + str(data.get('total')), 'draft=' + str(data.get('checkoutDraftOrderId')), flush=True)
    return data


if '--resume-drawer' not in sys.argv:
    run('network', 'requests', '--clear')
    run('set', 'viewport', '1440', '1200')
    run('set', 'media', 'reduced-motion')
    run('open', f'http://localhost:10013/cart-2/?qa-cart90={tag}-empty')
    run('wait', '--text', 'Your cart is currently empty!')
    capture('empty-cart')
    run('open', f'http://localhost:10013/product/qa-cart-90-{product}/?qa-cart90={tag}-add')
    identity = evaluate('Number(window.wcSettings?.currentUserId||0)')
    if identity != customer_id:
        raise RuntimeError(f'Wrong user context: expected {customer_id}, got {identity}')
    state = snapshot()
    purchase = re.search(r'- button "(Purchase now|Subscribe Now|Add to cart)"[^\n]*?ref=(e\d+)', state)
    if not purchase:
        raise RuntimeError('Missing purchase control')
    run('focus', '@' + purchase.group(2))
    run('press', 'Enter')
    run('wait', '--text', 'Billing address')
    capture('automatic-checkout-navigation')
    run('open', f'http://localhost:10013/product/qa-cart-90-{product}/?qa-cart90={tag}-drawer')
    click('button', 'Number of items in the cart: 1')
    run('wait', '--fn', 'document.querySelector(".wc-block-mini-cart__drawer")?.innerText.includes(' + json.dumps(name) + ')')
wait_total(price)
capture('product-drawer')
click('button', f'Increase quantity of {name}')
wait_total(price * 2)
capture('drawer-quantity-two')
click('button', f'Reduce quantity of {name}')
wait_total(price)
click('link', 'View my cart')
run('wait', '--text', name)
wait_total(price)
cart = capture('cart-quantity-one')
if cart['miniCartModule'] or cart['malformedRequests']:
    raise RuntimeError('Configured Cart still loads unused MiniCart or malformed request')
click('button', f'Increase quantity of {name}')
wait_total(price * 2)
capture('cart-quantity-two')
click('link', 'Proceed to Checkout')
run('wait', '--text', 'Billing address')
capture('checkout-navigation-only')
run('open', f'http://localhost:10013/cart-2/?qa-cart90={tag}-remove')
run('wait', '--text', name)
wait_total(price * 2)
click('button', f'Remove {name} from cart')
run('wait', '--text', 'Your cart is currently empty!')
wait_total(0)
capture('removed-empty-cart')
for filename, command in [('requests.txt', ['network', 'requests']), ('errors.json', ['errors', '--json'])]:
    text = run(*command)
    text = re.sub(r'([?&](?:_wpnonce|security|nonce|key|cart_token)=)[^&\s]+', r'\1REDACTED', text)
    (out / filename).write_text(text)
(out / 'result.json').write_text(json.dumps({'pass': True, 'session': session, 'mode': mode, 'role': role, 'customer_id': customer_id, 'product_id': 19881 if is_subscription else 19879, 'quantity_totals': [price, price * 2, 0], 'no_order_submission': True}, indent=2) + '\n')
print(tag, 'PASS', flush=True)
