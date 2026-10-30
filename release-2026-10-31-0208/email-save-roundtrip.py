"""Timed UI-only email subject round trips; no site API or DOM writes."""
import json
import re
import subprocess
from pathlib import Path

RUN = Path(__file__).resolve().parent
ROOT = RUN.parent.parent
CLOCK = ROOT / '.agents/skills/arraysubs-release-qa/scripts/qa_clock.py'
SESSION = 'rqa0208-read'
JOURNAL = RUN / 'email-save-baseline.private.json'
RESULTS = RUN / 'email-save-results.json'
baseline = json.loads(JOURNAL.read_text()) if JOURNAL.exists() else {}
results = json.loads(RESULTS.read_text()) if RESULTS.exists() else []


def ab(*args):
    completed = subprocess.run(
        ['agent-browser', '--session', SESSION, *args],
        capture_output=True, text=True, timeout=29,
    )
    if completed.returncode:
        raise RuntimeError(completed.stdout + completed.stderr)
    return completed.stdout


def persist():
    JOURNAL.write_text(json.dumps(baseline, indent=2))
    JOURNAL.chmod(0o600)
    RESULTS.write_text(json.dumps(results, indent=2))


def subject():
    snap = ab('snapshot', '-i')
    match = re.search(r'- textbox "Subject[^\n]*\[ref=(e\d+)\]', snap)
    if not match:
        raise RuntimeError('Subject textbox not present in current snapshot')
    ref = '@' + match.group(1)
    ident = ab('get', 'attr', ref, 'id').strip()
    value = ab('get', 'value', ref).rstrip('\r\n')
    return ref, ident, value


def replace_subject(value):
    ref, ident, current = subject()
    ab('click', ref)
    ab('press', 'End')
    for _ in current:
        ab('press', 'Backspace')
    if ab('get', 'value', ref).rstrip('\r\n'):
        raise RuntimeError('Textbox did not clear; refusing to append or save')
    if value:
        ab('keyboard', 'inserttext', value)
    if ab('get', 'value', ref).rstrip('\r\n') != value:
        raise RuntimeError('Textbox value differs before save')
    return ident


def save_and_reload(ident, expected):
    snap = ab('snapshot', '-i')
    match = re.search(r'- button "Save changes"[^\n]*\[ref=(e\d+)\]', snap)
    if not match or '[disabled,' in match.group(0):
        raise RuntimeError('Save changes unavailable after real edit')
    ab('click', '@' + match.group(1))
    ab('reload')
    ab('wait', '--fn',
       f'document.getElementById({json.dumps(ident)})?.value === {json.dumps(expected)}')
    return ab('snapshot', '-i')


priority = ['arraysubs_trial_started', 'arraysubs_trial_converted', 'arraysubspro_credit_used_email']
queue = json.loads((RUN / 'emails-inventory.json').read_text())
queue.sort(key=lambda item: priority.index(item['section']) if item['section'] in priority else len(priority))
for item in queue:
    section = item['section']
    if section == 'arraysubs_renewal_invoice':
        continue  # Coordinator already performed and restored this section.
    if any(r['section'] == section and r.get('restored') for r in results):
        continue
    state = subprocess.run(
        ['python3', str(CLOCK), 'status', str(RUN / 'clock.json')],
        capture_output=True, text=True, check=False,
    )
    timing = json.loads(state.stdout)
    if timing['elapsed_seconds'] >= 2640 or timing['testing_remaining_seconds'] < 180:
        print('Stopping new email mutations; restoration reserve reached.', flush=True)
        break
    result = {'section': section, 'title': item['title'], 'saved': False, 'restored': False}
    results.append(result)
    original = None
    try:
        ab('open', item['url'])
        ab('wait', '--fn',
           "Array.from(document.querySelectorAll('#wpbody-content h2'))"
           f".some(e=>e.textContent.includes({json.dumps(item['title'])})) && "
           "!!document.querySelector('#wpbody-content input[id$=\"_subject\"]')")
        _, ident, original = subject()
        baseline[section] = {'original': original, 'state': 'original-recorded'}
        persist()
        marker = 'RQA0208 QA ' + section
        ident = replace_subject(marker)
        baseline[section]['state'] = 'changed-pending-verification'
        persist()
        snap = save_and_reload(ident, marker)
        (RUN / 'evidence' / ('email-save-' + section + '.txt')).write_text(snap)
        ab('screenshot', '--full', str(RUN / 'evidence' / ('email-save-' + section + '.png')))
        result['saved'] = True
        baseline[section]['state'] = 'changed-verified'
        persist()
    except Exception as error:
        result['error'] = str(error)[:500]
    finally:
        if original is not None:
            try:
                _, ident, actual = subject()
                if actual != original:
                    ident = replace_subject(original)
                    restored = save_and_reload(ident, original)
                else:
                    ab('reload')
                    ab('wait', '--fn',
                       f'document.getElementById({json.dumps(ident)})?.value === {json.dumps(original)}')
                    restored = ab('snapshot', '-i')
                (RUN / 'evidence' / ('email-restored-' + section + '.txt')).write_text(restored)
                result['restored'] = True
                baseline[section]['state'] = 'original-restored-verified'
            except Exception as error:
                result['restoration_error'] = str(error)[:500]
        persist()
    print(section, 'PASS' if result['saved'] and result['restored'] else 'STOP: review journal', flush=True)
    if not result['saved'] or not result['restored']:
        break
