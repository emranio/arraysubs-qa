from browser_checks import *

def inspect(route):
 open_page(route);print(snap()['snapshot'],flush=True)
 screenshot(route.strip('/').replace('/','-')+'-initial')

def profile_field_save():
 route='/profile-builder/profile-form';open_page(route)
 click('Add Field');click('Save Configuration');ab('wait','--text','A field label is required')
 fill('Label','Footer QA field');fill('Key','footer_qa_field');save('Save Configuration')
 check('Footer QA field' in ab('get','text','#arraysubs-main-root'),'Profile Form custom field save succeeds')
 click('Remove field');print(snap()['snapshot'],flush=True)

def styling():
 route='/member-styling';open_page(route);initial=form_state();footer('Member Styling')
 click('','checkbox');click('Discard Changes');check(form_state()==initial,'Member Styling discard restores master enable switch')
 click('Add New Rule');fill('Rule name','Discard CSS rule');fill('gold-member premium-user','discard-class');fill('.site-header { background: #111; }','.discard-class { color: red; }');click('Add Condition')
 click('Discard Changes');check(form_state()==initial,'Member Styling discard removes added rule, classes, CSS and condition')
 click('Add New Rule');fill('Rule name','Footer QA Styling');fill('gold-member premium-user','footer-qa-style');fill('.site-header { background: #111; }','.footer-qa-style { color: red; }');ab('uncheck',ref('','checkbox',1));settle();save('Save Rules')
 saved=form_state();fill('gold-member premium-user','discard-class');fill('.site-header { background: #111; }','.discard-class { color: green; }');click('Duplicate');click('Discard Changes');check(form_state()==saved,'Member Styling discard restores saved CSS and removes duplicate')
 open_page(route);check(form_state()==saved,'Member Styling rule and CSS survive reload');screenshot('member-styling-saved');easy_link('Member Styling',route)

def reasons():
 return js('[...document.querySelectorAll("#arraysubs-main-root .arraysubs-fb-repeater-item-header")].map(e=>e.textContent)')

def retention():
 route='/retention-flow';open_page(route);initial=form_state();initial_reasons=reasons();footer('Retention Flow')
 click('+ Add Reason');fill('e.g., too_expensive','footer_qa_reason');fill('e.g., Too expensive','Footer QA reason');click('Enable Retention Offers','switch');click('Discard Changes')
 check(form_state()==initial and reasons()==initial_reasons,'Retention discard removes new reason and restores hidden offer state')
 click('+ Add Reason');fill('e.g., too_expensive','footer_qa_reason');fill('e.g., Too expensive','Footer QA reason');save();saved_reasons=reasons();check(len(saved_reasons)==len(initial_reasons)+1 and any('footer_qa_reason' in r for r in saved_reasons),'Retention saved list includes the new eighth reason')
 click('Remove item',index=len(initial_reasons));click('Discard Changes');check(reasons()==saved_reasons,'Retention discard restores a removed saved reason')
 open_page(route);check(reasons()==saved_reasons,'Retention reason save survives reload');screenshot('retention-saved')
 # Empty repeaters must persist and must be a valid discard baseline.
 for _ in range(len(saved_reasons)):click('Remove item')
 check(reasons()==[],'Retention all actual reason rows removed before save');save();open_page(route);check(reasons()==[],'Retention empty reason list survives save/reload')
 click('+ Add Reason');click('Discard Changes');check(reasons()==[],'Retention discard restores saved empty reason list')
 easy_link('Retention Flow',route)

if __name__=='__main__':
 if sys.argv[1]=='inspect':inspect(sys.argv[2])
 elif sys.argv[1]=='field':profile_field_save()
 elif sys.argv[1]=='styling':styling()
 elif sys.argv[1]=='retention':retention()
