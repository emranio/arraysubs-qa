const fs=require('fs');
const {JSDOM}=require(process.cwd()+'/arraysubs/node_modules/jsdom');
const map=JSON.parse(fs.readFileSync(__dirname+'/gutenberg-wp-script-packages.json','utf8'));
const root='/Users/emran/Local Sites/wp-test2/app/public/wp-includes/js/';
const dom=new JSDOM('<!doctype html><html><body></body></html>',{url:'http://localhost:10013',runScripts:'outside-only',pretendToBeVisual:true});
const w=dom.window;
w.matchMedia=()=>({matches:false,addListener(){},removeListener(){},addEventListener(){},removeEventListener(){}});
w.requestIdleCallback=(cb)=>setTimeout(cb,0);w.cancelIdleCallback=clearTimeout;
const loaded=new Set();
function load(handle){
 if(loaded.has(handle))return;
 if(['react-jsx-runtime','react-dom','react-dom-client','react-dom/client'].includes(handle))load('react');
 const name=handle.replace(/^wp-/,'');
 const entry=map[name+'.js'];
 if(entry){ for(const dep of entry.dependencies)load(dep); w.eval(fs.readFileSync(root+'dist/'+name+'.js','utf8')); }
 else {
  const vendors={react:'react.js','react-dom':'react-dom.js','react-jsx-runtime':'react-jsx-runtime.js','react-dom/client':'react-dom.js','react-dom-client':'react-dom.js','react-dom-server':'react-dom-server.js'};
  if(!vendors[handle])throw Error('Unknown script '+handle);
  w.eval(fs.readFileSync(root+'dist/vendor/'+vendors[handle],'utf8'));
 }
 loaded.add(handle);
}
load('wp-blocks');
console.log('Loaded actual WordPress blocks package and '+loaded.size+' dependencies.');
console.log(Object.keys(w.wp.blocks).filter(k=>/parse|serialize|registerBlockType/.test(k)).join(', '));
const assert=require('assert/strict'), vm=require('vm');
const babel=require(process.cwd()+'/arraysubs/node_modules/@babel/core');
const {execFileSync}=require('child_process');
const oldMeta=JSON.parse(execFileSync('git',['-C','arraysubs','show','HEAD:src/Features/GutenbergIntegration/block/block.json'],{encoding:'utf8'}));
const newMeta=JSON.parse(fs.readFileSync('arraysubs/src/Features/GutenbergIntegration/block/block.json','utf8'));
const b=w.wp.blocks, el=w.wp.element;
b.setCategories([{slug:'arraysubs',title:'ArraySubs'},{slug:'text',title:'Text'},{slug:'design',title:'Design'}]);
const Save=({innerBlocks=[]})=>el.createElement('div',{className:'wp-block-arraysubs-content-restricted'},el.createElement(el.RawHTML,{},b.serialize(innerBlocks)));
b.registerBlockType('core/paragraph',{apiVersion:3,title:'Paragraph',category:'text',attributes:{content:{type:'string',source:'html',selector:'p',default:''}},save:({attributes})=>el.createElement('p',{},el.createElement(el.RawHTML,{},attributes.content))});
b.registerBlockType(oldMeta,{save:Save});
const oldCases=[
 {name:'ordinary-any-native-minimal',attrs:{roles:['demo_premium'],products:[2147483000],condition:'or',showToAdmins:false,message:'<p>Old <strong>custom</strong></p>'}},
 {name:'ordinary-any-all-false',attrs:{roles:['qa_nonexistent_role'],products:[2147483000],condition:'or',loginRequired:false,showToAdmins:false}},
 {name:'ordinary-any-all-matching-fields',attrs:{roles:['demo_premium'],lifetimeSpent:'0',condition:'or',loginRequired:false,showToAdmins:false}},
 {name:'ordinary-all-default-logic',attrs:{roles:['demo_premium'],products:[2147483000],loginRequired:false}},
 {name:'ordinary-any-explicit-old-mode',attrs:{mode:'restrict',roles:['demo_premium'],products:[2147483000],condition:'or',loginRequired:false}},
 {name:'ordinary-any-explicit-uppercase-logic',attrs:{roles:['demo_premium'],products:[2147483000],condition:'OR'}},
 {name:'old-login-only',attrs:{roles:['qa_nonexistent_role'],products:[2147483000],condition:'or',loginRequired:true,showToAdmins:false}},
 {name:'old-guests-visibility',attrs:{mode:'visibility',visShow:'logged_out',visFallback:'<p>Fallback</p>',showToAdmins:false}},
 {name:'old-login-visibility-default-show',attrs:{mode:'visibility',visFallback:'<p>Fallback</p>'}},
 {name:'old-visibility-login-override',attrs:{mode:'visibility',visShow:'logged_out',visFallback:'[old_shortcode]',loginRequired:true}},
 {name:'all-default-saved-container',attrs:{}},
];
const fixtures=oldCases.map(item=>({...item,source:b.serialize([b.createBlock(oldMeta.name,item.attrs,[b.createBlock('core/paragraph',{content:'Original protected body'})])])}));
b.unregisterBlockType(oldMeta.name);
const moduleCache={};
function productModule(file){
 if(moduleCache[file])return moduleCache[file];
 const code=babel.transformSync(fs.readFileSync(file,'utf8'),{configFile:false,babelrc:false,plugins:[require.resolve(process.cwd()+'/arraysubs/node_modules/@babel/plugin-transform-modules-commonjs')]}).code;
 const out={};moduleCache[file]=out;
 vm.runInNewContext(code,{exports:out,window:{ArraySubsPostRestriction:{}},require:(id)=>{
  if(id==='./accessConditions')return productModule('arraysubs/src/resources/components/ContentGate/accessConditions.js');
  if(id==='@/components/RestrictionRuleBuilder/conditionLogic')return productModule('arraysubs/src/resources/components/RestrictionRuleBuilder/conditionLogic.js');
  throw Error(id);
 }});return out;
}
const compat=productModule('arraysubs/src/resources/components/ContentGate/restrictionCompatibility.js');
const indexCode=babel.transformSync(fs.readFileSync('arraysubs/src/Features/GutenbergIntegration/block/index.js','utf8'),{configFile:false,babelrc:false,plugins:[require.resolve(process.cwd()+'/arraysubs/node_modules/@babel/plugin-transform-modules-commonjs')]}).code;
vm.runInNewContext(indexCode,{exports:{},require:(id)=>{
 if(id==='@wordpress/blocks')return b;if(id==='@wordpress/hooks')return w.wp.hooks;
 if(id.includes('restrictionCompatibility'))return compat;
 if(id==='./block.json')return newMeta;if(id==='./save')return Save;if(id==='./edit')return function Edit(){};if(id.endsWith('.scss'))return {};
 throw Error(id);
}});
let checks=0;const results=[];
function check(value,label){assert.ok(value,label);checks++;}
for(const item of fixtures){
 const expectedLogin=!!item.attrs.loginRequired;
 check(!item.source.includes('accessVersion'),item.name+': old native source has no version');
 let source=item.source;
 for(let round=0;round<3;round++){
  const block=b.parse(source)[0];
  check(block.isValid,item.name+': actual WP parser accepts old wrapper round'+round);
  const attrs=block.attributes;
  check(attrs.accessVersion===1,item.name+': v1 stays explicit round'+round);
  check(attrs.loginRequired===expectedLogin,item.name+': no login prerequisite drift round'+round);
  check(attrs.legacy.loginOnly===expectedLogin,item.name+': override descriptor exact round'+round);
  check(attrs.condition===(item.attrs.condition||'and'),item.name+': match logic exact round'+round);
  check(JSON.stringify(attrs.roles)===JSON.stringify(item.attrs.roles||[]),item.name+': roles exact round'+round);
  check(JSON.stringify(attrs.products)===JSON.stringify(item.attrs.products||[]),item.name+': products exact round'+round);
  check(attrs.useDefaultMessage===false,item.name+': old custom/default inference preserved round'+round);
  if(round===1)block.innerBlocks[0].attributes.content+=' — unrelated body edit';
  source=b.serialize([block]);
  check(source.includes('"accessVersion":1'),item.name+': serializer retains nondefault version marker round'+round);
  check(!source.includes('"accessVersion":2'),item.name+': ordinary save never converts round'+round);
 }
 results.push({name:item.name,original:item.source,afterThreeRounds:source});
}
// An old block nested inside another persisted block is parsed with the same raw filter.
b.registerBlockType('core/group',{apiVersion:3,title:'Group',category:'design',attributes:{},save:({innerBlocks=[]})=>el.createElement('div',{className:'wp-block-group'},el.createElement(el.RawHTML,{},b.serialize(innerBlocks)))});
const nestedSource='<!-- wp:group --><div class="wp-block-group">'+fixtures[0].source+'</div><!-- /wp:group -->';
const nested=b.parse(nestedSource)[0];
check(nested.innerBlocks[0].attributes.accessVersion===1,'nested restricted block is v1');
check(nested.innerBlocks[0].attributes.loginRequired===false,'nested ordinary ANY keeps no login override');
const nestedAgain=b.parse(b.serialize([nested]))[0];
check(nestedAgain.innerBlocks[0].attributes.legacy.loginOnly===false,'nested roundtrip preserves false override');
// Execute actual Edit initialization with JSX/hook shims; no replacement parser or compatibility logic.
const Legacy=function LegacyAccessNotice(){};const Rules=function AccessRuleSections(){};
const jsx=(type,props)=>({type,props});let initPatches=[];
const editCode=babel.transformSync(fs.readFileSync('arraysubs/src/Features/GutenbergIntegration/block/edit.js','utf8'),{configFile:false,babelrc:false,plugins:[[require.resolve(process.cwd()+'/arraysubs/node_modules/@babel/plugin-transform-react-jsx'),{runtime:'automatic'}],require.resolve(process.cwd()+'/arraysubs/node_modules/@babel/plugin-transform-modules-commonjs')]}).code;
const editOut={};
vm.runInNewContext(editCode,{exports:editOut,window:{ArraySubsGutenberg:{}},require:(id)=>{
 if(id==='react/jsx-runtime')return {jsx,jsxs:jsx,Fragment:'Fragment'};
 if(id==='@wordpress/block-editor')return {InspectorControls:'InspectorControls',useBlockProps:()=>({}),useInnerBlocksProps:()=>({children:null})};
 if(id==='@wordpress/element')return {useEffect:fn=>fn()};
 if(id==='@wordpress/components')return {PanelBody:'PanelBody',Notice:'Notice',ToggleControl:'ToggleControl'};
 if(id==='@wordpress/i18n')return {__:s=>s};
 if(id.includes('restrictionCompatibility'))return compat;
 if(id.includes('LegacyAccessNotice'))return Legacy;
 if(id.includes('AccessRuleSections'))return {__esModule:true,default:Rules,AdminAccessSection:'AdminAccessSection'};
 if(id.includes('RestrictionMessageField'))return 'RestrictionMessageField';
 throw Error(id);
}});
function types(node){if(!node||typeof node!=='object')return [];return [node.type,...(Array.isArray(node.props?.children)?node.props.children:[node.props?.children]).flatMap(types)];}
for(const item of fixtures){
 initPatches=[];const attrs=b.parse(item.source)[0].attributes;
 const tree=editOut.default({attributes:attrs,setAttributes:patch=>initPatches.push(patch)});
 check(initPatches.length===0,item.name+': opening Edit writes no attributes');
 check(types(tree).includes(Legacy),item.name+': legacy surface rendered');
 check(!types(tree).includes(Rules),item.name+': force-login rule fields not mounted');
}
initPatches=[];
editOut.default({attributes:b.createBlock(newMeta.name).attributes,setAttributes:patch=>initPatches.push(patch)});
check(initPatches.length===1&&initPatches[0].accessVersion===2,'new insertion explicitly stamps v2');
check(initPatches[0].enabled===true&&initPatches[0].useDefaultMessage===true,'new insertion initializes current UI flags');
console.log(`${checks} actual WordPress parser/serializer and product Edit initialization assertions passed.`);
const outPath='qa/restriction-final-access-audit-2026-10-24/evidence/gutenberg-official-parser-roundtrips.json';
fs.mkdirSync(require('path').dirname(outPath),{recursive:true});
fs.writeFileSync(outPath,JSON.stringify({checks,actualWordPressDependencies:loaded.size,results},null,2));
dom.window.close();
