from browser_checks import *

ab('set','viewport','1440','1000')
routes=[('/profile-builder/profile-form','Profile Form'),('/profile-builder/my-account','My Account'),('/cart-info-editor','Cart Info'),('/member-styling','Member Styling'),('/retention-flow','Retention Flow'),*RULE_ROUTES]
for route,name in routes:
 open_page(route)
 state=js('({text:document.querySelector("#arraysubs-main-root").innerText,values:[...document.querySelectorAll("#arraysubs-main-root input,#arraysubs-main-root textarea")].map(e=>e.value)})')
 check('Footer QA' not in state['text'] and all('Footer QA' not in v and 'footer_qa' not in v for v in state['values']),name+' temporary test data absent after restoration and reload')
 check(ab('get','text','.arraysubs-settings-last-saved')=='Settings last saved: September 10, 2026 12:59 pm (UTC)',name+' original saved timestamp restored')
 screenshot('restored-'+name.lower().replace(' ','-'))
