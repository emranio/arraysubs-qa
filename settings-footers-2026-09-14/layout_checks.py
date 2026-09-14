from browser_checks import *

ROUTES=[('/settings/general','settings'),('/profile-builder/profile-form','profile-fields'),('/profile-builder/my-account','myaccount'),('/cart-info-editor','cart'),('/member-styling','styling'),('/retention-flow','retention'),('/members-access/url-rules','member-access')]
def layout(width):
 ab('set','viewport',str(width),'900')
 results=[]
 for route,name in ROUTES:
  open_page(route)
  geom=js('(() => {const bar=document.querySelector(".arraysubs-settings-actions > div"); const items=[...bar.children];return {width:innerWidth,bar:bar.getBoundingClientRect().toJSON(),items:items.map(e=>({text:e.innerText,rect:e.getBoundingClientRect().toJSON(),color:getComputedStyle(e).color})),flex:getComputedStyle(bar).display};})()')
  check(geom['bar']['left']>=-1 and geom['bar']['right']<=width+1,name+' footer stays inside '+str(width)+'px viewport')
  check(all(x['rect']['left']>=geom['bar']['left'] and x['rect']['right']<=geom['bar']['right']+1 for x in geom['items']),name+' footer controls fit at '+str(width)+'px')
  ab('screenshot',str(OUT/(name+'-'+str(width)+'.png')));results.append({'page':name,**geom})
 (OUT/('layout-'+str(width)+'.json')).write_text(json.dumps(results,indent=2))

def failure():
 route='/members-access/url-rules';open_page(route);saved_names=rule_names();stamp=ab('get','text','.arraysubs-settings-last-saved');fill('Rule name','Draft after failed save')
 ab('set','offline','on')
 try:
  save('Save Rules');ab('wait','--text','Failed to save rules')
  check('Draft after failed save' in rule_names(),'failed save preserves unsaved rule')
  check(ab('get','text','.arraysubs-settings-last-saved')==stamp,'failed save leaves timestamp unchanged')
  screenshot('failed-save-preserves-draft')
 finally:ab('set','offline','off')
 click('Discard Changes');check(rule_names()==saved_names,'discard after failed save restores server snapshot');open_page(route);check(rule_names()==saved_names,'failed save did not persist the draft')

if __name__=='__main__':
 if sys.argv[1]=='failure':failure()
 else:layout(int(sys.argv[1]))
