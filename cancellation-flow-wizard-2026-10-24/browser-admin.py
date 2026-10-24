import json, re, subprocess
from pathlib import Path
ROOT=Path(__file__).resolve().parent
SESSION='cancellation-admin'
checks=[]
def run(*args):
 r=subprocess.run(['agent-browser','--session',SESSION,*args],capture_output=True,text=True,timeout=40)
 if r.returncode: raise RuntimeError(str(args)+r.stdout+r.stderr)
 return r.stdout
def snap():
 run('wait','--fn','Array.from(document.querySelectorAll(".arraysubs-modal-backdrop")).every(e=>getComputedStyle(e).opacity==="1")')
 return run('snapshot','-i')
def click(text,kind='button',partial=False):
 tree=snap(); candidates=re.findall(rf'{kind} "([^"\n]*)"[^\n]*\bref=(e\d+)\]',tree)
 found=[(name,ref) for name,ref in candidates if (text in name if partial else name==text)]
 if not found: raise AssertionError('Missing '+text+'\n'+tree[-4000:])
 run('click','@'+found[-1][1])
 if text.startswith('Edit'): run('wait','.arraysubs-flow-step-title')
def field(placeholder,value):
 tree=snap(); found=re.findall(rf'(?:textbox|spinbutton) "{re.escape(placeholder)}"[^\n]*\bref=(e\d+)\]',tree)
 if not found: raise AssertionError('Missing field '+placeholder)
 run('fill','@'+found[-1],str(value) if value != '' else 'x')
 if value == '': run('press','Backspace')
def step(title):
 click(title,partial=True)
 run('wait','--fn',f'document.querySelector(".arraysubs-flow-step-title")?.textContent==={json.dumps(title)}')
def check(ok,text):
 checks.append({'passed':bool(ok),'check':text}); (ROOT/'evidence/admin-checks.json').write_text(json.dumps(checks,indent=2)); assert ok,text
def shot(name): run('screenshot',str(ROOT/'evidence'/name))

run("reload"); run("wait", ".arraysubs-flow-hero"); click("Edit cancellation flow"); run("wait", ".arraysubs-flow-steps")
step('Cancellation Reasons')
check('Require Cancellation Reason' in snap() and '+ Add Reason' in snap(),'Step 1 contains both reason settings')
click('Reason 1 - too_expensive',kind='generic',partial=True)
field('e.g., Too expensive','Price does not fit my budget')
click('+ Add Reason')
field('e.g., too_expensive','qa_reason')
field('e.g., Too expensive','QA temporary reason')
step('Retention Offers')
check('Enable Retention Offers' in snap(),'Step 2 contains master switch')
click('Enable Retention Offers',kind='switch')
step('Discount Offer')
check('Enable Discount Offer' in snap(),'Offers remain configurable when master is off')
check('Retention offers are off.' in run('get','text','.arraysubs-flow-wizard'),'Disabled master has explanatory notice')
step('Retention Offers'); click('Enable Retention Offers',kind='switch'); step('Discount Offer')
# Dynamic choices include unsaved reasons.
tree=snap(); control=re.findall(r'generic "[^"\n]*Found a better alternative"[^\n]*\bref=(e\d+)\]',tree)[-1]; run('click','@'+control)
check('QA temporary reason' in snap(),'Offer reason choices reflect unsaved new reason')
run('click','.arraysubs-flow-step-title')
field('Enter discount percentage','23')
field('Enter max subscription value','1')
click('Continue')
check('Discount Offer' in run('get','text','.arraysubs-flow-step-title'),'Invalid min/max blocks continuing')
check('Max Subscription Value must be 0 or at least' in run('get','text','body'),'Validation error is visible')
field('Enter max subscription value','250')
field('Enter discount percentage','101'); click('Continue')
check('Discount Offer' in run('get','text','.arraysubs-flow-step-title'),'Discount over 100 percent blocks continuing')
field('Enter discount percentage','23')
click('Continue'); check('Pause Offer' in run('get','text','.arraysubs-flow-step-title'),'Step 4 follows Discount')
shot('03-pause-step.png')
click('Continue'); check('Downgrade Offer' in run('get','text','.arraysubs-flow-step-title'),'Step 5 follows Pause')
click('Continue'); check('Contact Support Offer' in run('get','text','.arraysubs-flow-step-title'),'Step 6 follows Downgrade')
field('e.g., https://yoursite.com/support',''); click('Continue')
check('Contact Support Offer' in run('get','text','.arraysubs-flow-step-title'),'Missing enabled support URL blocks review')
field('e.g., https://yoursite.com/support','http://localhost:10013/demo-member-support/')
click('Continue')
check('23% off' in run('get','text','.arraysubs-flow-overview.is-review'),'Review shows current unsaved discount')
check('Unsaved changes' in run('get','text','.arraysubs-setup-wizard__footer'),'Review does not save changes automatically')
check('QA temporary reason' in run('get','text','.arraysubs-flow-overview.is-review'),'Review includes unsaved reason')
shot('04-review.png')
click('Edit Cancellation Reasons')
# Remove last added row; save must not restore it from stale indexed fields.
tree=snap(); refs=re.findall(r'button "Remove item"[^\n]*\bref=(e\d+)\]',tree); run('click','@'+refs[-1])
step('Overview and Save')
check('QA temporary reason' not in run('get','text','.arraysubs-flow-overview.is-review'),'Removed reason stays removed on review')
run('press','Escape'); run('wait','--text','Discard your changes?'); check('Discard your changes?' in snap(),'Closing dirty wizard opens shared confirmation')
click('Keep editing'); run('wait','--fn','!document.querySelector(".arraysubs-confirm-dialog")'); check('Overview and Save' in run('get','text','.arraysubs-flow-step-title'),'Keep editing preserves review')
# Preserve data for blocked-save retry, using actual server response handling.
run('network','route','**/arraysubs/v1/settings/save','--body','{"code":500,"message":"Failed to save settings"}')
click('Save cancellation flow'); run('wait','--fn','!document.querySelector(".arraysubs-setup-wizard__footer .button-primary")?.disabled')
check('23% off' in run('get','text','.arraysubs-flow-overview.is-review'),'Failed save retains all current edits')
check('Failed to save settings' in run('get','text','body'),'Failed save produces visible error toast')
run('network','unroute','**/arraysubs/v1/settings/save')
click('Save cancellation flow')
run('wait','--fn','!document.querySelector(".arraysubs-cancellation-wizard-modal")')
run('reload'); run('wait','.arraysubs-flow-hero')
check('23% off' in run('get','text','.arraysubs-flow-overview'),'Saved discount survives reload')
check('Price does not fit my budget' in run('get','text','.arraysubs-flow-overview'),'Saved reason label survives reload')
check('QA temporary reason' not in run('get','text','.arraysubs-flow-overview'),'Deleted reason does not resurrect on reload')
# Confirm dirty discard restores saved state.
click('Edit Discount Offer'); field('Enter discount percentage','77'); run('press','Escape'); run('wait','--text','Discard your changes?'); click('Discard changes'); run('wait','--fn','!document.querySelector(".arraysubs-confirm-dialog")')
run('wait','--fn','!document.querySelector(".arraysubs-cancellation-wizard-modal")')
click('Edit Discount Offer')
check('23' in run('get','value','#arraysubs-field-cancellation-retention_offers-discount-discount_percent'),'Discard restores saved discount')
# Mobile and keyboard checks.
run('set','viewport','390','844')
shot('05-mobile-editor.png')
check(run('eval','document.querySelector(".arraysubs-cancellation-wizard-modal").scrollWidth <= innerWidth').strip()=='true','Mobile modal has no horizontal overflow')
run('set','viewport','1280','900')
run('press','Escape'); run('wait','--fn','!document.querySelector(".arraysubs-cancellation-wizard-modal")')
check(run('eval','document.activeElement?.getAttribute("aria-label")').strip('"\n')=='Edit Discount Offer','Closing editor restores trigger focus')
shot('06-saved-overview.png')
check(not run('errors').strip(),'Admin browser has no uncaught errors')
print(json.dumps(checks,indent=2))
