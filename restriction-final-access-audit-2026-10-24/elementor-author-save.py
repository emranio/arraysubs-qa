"""Real native authoring through agent-browser refs; no restriction panel opened."""
import json
import pathlib
import re
import subprocess
import sys
import time

ROOT = pathlib.Path(__file__).resolve().parent
EVIDENCE = ROOT / 'evidence'
EDITOR = 'final-elementor-overflow'
WP = ['php', '-d', 'mysqli.default_socket=/Users/emran/Library/Application Support/Local/run/NPLEnP-EU/mysql/mysqld.sock', '/opt/homebrew/bin/wp', '--allow-root', '--path=/Users/emran/Local Sites/wp-test2/app/public', 'eval-file', str(ROOT / 'elementor-audit.php')]
URL = 'http://localhost:10013/qa-final-access-author_elementor-20261024/'
STATES = sys.argv[1:] or ['transitional_off', 'transitional_inverse_off', 'legacy_any_fail_absent', 'legacy_any_pass_absent', 'legacy_any_fail_explicit_off', 'legacy_any_pass_explicit_off', 'visibility_empty', 'visibility_custom', 'legacy_login_only', 'imported_negative', 'imported_prefix']

def call(args):
    p = subprocess.run(args, text=True, capture_output=True, timeout=65)
    if p.returncode:
        raise RuntimeError(p.stderr + p.stdout)
    return p.stdout

def browser(session, *args):
    return call(['agent-browser', '--session', session, *args])

def snap(session):
    return browser(session, 'snapshot', '-i')

def wait_ref(pattern):
    for _ in range(30):
        s = snap(EDITOR)
        m = re.search(pattern, s)
        if m:
            return m.group(1), s
        time.sleep(.3)
    raise RuntimeError('Missing ref: ' + pattern + '\n' + s)

def matrix(state, stage):
    results = {}
    for actor in ['guest', 'member', 'customer', 'admin']:
        session = 'final-elementor-' + actor
        browser(session, 'open', URL + '?elementor-audit=' + state + '-' + stage)
        body = json.loads(browser(session, 'eval', 'document.body.innerText'))
        results[actor] = {
            'private': 'QA FINAL ELEMENTOR UNOPENED PRIVATE' in body,
            'default': 'QA FINAL DEFAULT' in body,
            'custom': 'QA FINAL ELEMENTOR AUDIT CUSTOM' in body,
            'fallback': 'QA FINAL ELEMENTOR AUDIT FALLBACK' in body,
        }
        (EVIDENCE / ('elementor-' + state + '-' + stage + '-' + actor + '.txt')).write_text(snap(session))
    return results

prior = EVIDENCE / 'elementor-unopened-author-results.json'
report = json.loads(prior.read_text()) if sys.argv[1:] and prior.exists() else []
for state in STATES:
    seed = call(WP + ['seed', state])
    (EVIDENCE / ('elementor-' + state + '-raw-before.txt')).write_text(seed)
    before = matrix(state, 'before')
    browser(EDITOR, 'open', 'http://localhost:10013/wp-admin/post.php?post=20192&action=elementor&qa=' + state)
    browser(EDITOR, 'wait', '--fn', "document.querySelector('#elementor-loading') && getComputedStyle(document.querySelector('#elementor-loading')).display === 'none'")
    ref, _ = wait_ref(r'heading "QA FINAL ELEMENTOR UNOPENED PRIVATE"[^\n]*ref=(e\d+)')
    model = browser(EDITOR, 'eval', "JSON.stringify({version:elementor.elements.at(0).get('settings').get('arraysubs_access_version'),initialized:!!elementor.elements.at(0).get('settings').arraySubsOriginalSettings,serialized:elementor.elements.at(0).get('settings').toJSON({remove:['default']}),dirty:elementor.saver.isEditorChanged()})")
    (EVIDENCE / ('elementor-' + state + '-model.txt')).write_text(model)
    browser(EDITOR, 'click', '@' + ref)
    ref, _ = wait_ref(r'textbox "Title" \[ref=(e\d+)\]')
    browser(EDITOR, 'fill', '@' + ref, 'QA FINAL ELEMENTOR UNOPENED PRIVATE edited ' + state)
    ref, _ = wait_ref(r'button "Publish" \[ref=(e\d+)\]')
    browser(EDITOR, 'click', '@' + ref)
    wait_ref(r'button "Publish" \[disabled, ref=(e\d+)\]')
    (EVIDENCE / ('elementor-' + state + '-raw-after.txt')).write_text(call(WP + ['inspect']))
    after = matrix(state, 'after')
    expected = {a: a == 'member' if '_pass_' in state else (a == 'guest' if state.startswith('visibility') else (a != 'guest' if state == 'legacy_login_only' else False)) for a in before}
    assert before == after, (state, 'audience/output changed', before, after)
    assert {a: v['private'] for a,v in after.items()} == expected, (state, 'wrong audience', after, expected)
    if state == 'transitional_off':
        browser(EDITOR, 'screenshot', '--full', str(EVIDENCE / 'elementor-unopened-fixed-editor.png'))
        browser('final-elementor-member', 'screenshot', '--full', str(EVIDENCE / 'elementor-unopened-fixed-member.png'))
    report.append({'state': state, 'before': before, 'after': after, 'pass': True})
    (EVIDENCE / 'elementor-unopened-author-results.json').write_text(json.dumps(report, indent=2))
    print('PASS ' + state, flush=True)
print('PASS: ' + str(len(STATES)) + ' real unopened-panel heading saves; ' + str(len(STATES) * 8) + ' actor page views retain exact audience and messages.', flush=True)
