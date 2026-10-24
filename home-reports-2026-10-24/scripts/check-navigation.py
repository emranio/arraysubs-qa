import json, re, subprocess
from pathlib import Path

base = 'http://localhost:10013/wp-admin/admin.php?page=arraysubs-mainadmin#/overview'
out = Path('qa/home-reports-2026-10-24/evidence')

def browser(*args):
    return subprocess.run(['agent-browser', '--session', 'report-home-links', *args], check=True, capture_output=True, text=True).stdout

browser('open', 'http://localhost:10013/wp-admin/?localwp_auto_login=1')
results = []
for title in ['Member insights', 'Gateway health & webhooks', 'Orders Analytics', 'Revenue Analytics', 'Variations Analytics', 'Customers Analytics', 'Orders', 'Store Credit Balances', 'Credit Transaction History', 'Activity Audit Trail', 'Scheduled Job Logs']:
    browser('open', base)
    browser('wait', '--fn', 'document.querySelectorAll("[data-report-card]").length === 11 && !document.querySelector(".arraysubs-route-overlay.is-active")')
    browser('eval', 'window.scrollTo(0, document.querySelector(".arraysubs-overview__reports").getBoundingClientRect().top+scrollY-150)')
    snap = browser('snapshot', '-i', '-s', '.arraysubs-overview__reports')
    ref = re.search(r'link "Open '+re.escape(title)+r'" \[ref=(e\d+)\]', snap).group(1)
    try:
        browser('click', '@'+ref)
    except subprocess.CalledProcessError as e:
        print('Retry after new snapshot:', title, e.stderr.strip(), flush=True)
        snap=browser('snapshot', '-i', '-s', '.arraysubs-overview__reports')
        ref=re.search(r'link "Open '+re.escape(title)+r'" \[ref=(e\d+)\]', snap).group(1)
        browser('click', '@'+ref)
    browser('wait', '--load', 'networkidle')
    destination = browser('snapshot', '-i', '-s', '#wpbody-content')
    slug = re.sub(r'[^a-z0-9]+', '-', title.lower()).strip('-')
    (out / (slug+'-snapshot.txt')).write_text(destination)
    browser('screenshot', str(out / (slug+'-destination.png')))
    page = json.loads(browser('eval', 'JSON.stringify({url:location.href,title:document.title,headings:[...document.querySelectorAll("#wpbody-content h1,#wpbody-content h2,#wpbody-content h3")].map(e=>e.innerText).slice(0,12),text:document.querySelector("#wpbody-content")?.innerText.slice(0,1800)})'))
    if isinstance(page, str): page=json.loads(page)
    results.append({'card':title, **page})
    (out / 'navigation-results.json').write_text(json.dumps(results, indent=2))
    print(title, page['headings'][:3], flush=True)
(out / 'navigation-results.json').write_text(json.dumps(results, indent=2))
browser('open', base)
browser('wait', '--fn', 'document.querySelectorAll("[data-report-card]").length === 11 && !document.querySelector(".arraysubs-route-overlay.is-active")')
