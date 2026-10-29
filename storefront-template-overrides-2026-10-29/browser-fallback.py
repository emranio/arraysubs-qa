"""Real page checks through agent-browser; run after hiding disposable overrides."""
import json
import subprocess
from pathlib import Path

root = Path(__file__).resolve().parent
fixture = json.loads((root / 'evidence/fixtures.json').read_text())
routes = [
    ('list', 'http://localhost:10013/my-account/as-subscriptions/', 'Memberships'),
    ('detail', fixture['subscription_url'], 'Subscription #' + str(fixture['subscription_id'])),
    ('order', fixture['order_url'], 'Related Subscriptions'),
    ('credit', 'http://localhost:10013/my-account/as-store-credit/', 'Your Store Credit Balance'),
    ('features', 'http://localhost:10013/my-account/as-features/', 'My Features'),
    ('profile', 'http://localhost:10013/my-account/edit-account/', 'Profile Photo'),
    ('flexible', fixture['product_url'], 'Billing Period'),
    ('fixed', 'http://localhost:10013/product/test-3/', 'test'),
    ('variable', 'http://localhost:10013/product/demo-%c2%b7-membership-tier/', 'Tier'),
    ('box', 'http://localhost:10013/product/demo-%c2%b7-build-your-monthly-coffee-box/', 'Create Subscription Box'),
    ('comments', fixture['comments_url'], 'QA comments denied.'),
    ('denied', fixture['denied_url'], 'Access Restricted'),
]
results = []
for label, url, expected in routes:
    session = 'template-qa-guest' if label in ('comments', 'denied') else 'template-qa-customer'
    def browser(*args):
        return subprocess.run(['agent-browser', '--session', session, *args], check=True, text=True, capture_output=True).stdout
    browser('open', url)
    browser('wait', '--load', 'networkidle')
    (root / 'evidence' / ('fallback-' + label + '-snapshot.txt')).write_text(browser('snapshot', '-i'))
    result = json.loads(browser('eval', '({url:location.href,title:document.title,markers:Array.from(document.querySelectorAll("[data-template-qa]")).map(e=>e.textContent),body:document.body.innerText})'))
    assert not result['markers'], (label, result['markers'])
    assert expected in result['body'], (label, expected, result['body'])
    assert 'There has been a critical error' not in result['body'], label
    errors = browser('errors')
    (root / 'evidence' / ('fallback-' + label + '-errors.txt')).write_text(errors)
    assert not errors.strip(), (label, errors)
    if label in ('list', 'detail', 'flexible', 'denied', 'comments'):
        browser('screenshot', '--full', str(root / 'evidence' / ('fallback-' + label + '.png')))
    result['expected'] = expected
    result['passed'] = True
    results.append(result)
    print('PASS', label, url, flush=True)
(root / 'evidence/fallback-browser-results.json').write_text(json.dumps(results, indent=2))
print('All 12 real-browser plugin fallback routes passed.')
