(()=>{
'use strict';
const $=s=>document.querySelector(s), $$=s=>[...document.querySelectorAll(s)];
const SPICE=['','Mild','Medium','Spicy'], DIET={veg:'Vegetarian',nonveg:'Non-Vegetarian',vegan:'Vegan'};
const S={category:'',q:'',diet:'',spice:'',maxPrice:600,restaurant:'',exclude:[],sort:'popularity'};
let meta, cart=0, timer, lastFocus, reqId=0;
const inr=n=>'₹'+Number(n).toLocaleString('en-IN');
function el(tag,attrs={},...kids){const e=document.createElement(tag);for(const[k,v]of Object.entries(attrs)){if(k==='class')e.className=v;else if(k.startsWith('on'))e.addEventListener(k.slice(2),v);else if(v!==false&&v!=null)e.setAttribute(k,v)}
 kids.flat().forEach(c=>e.append(c instanceof Node?c:document.createTextNode(c)));return e}
async function api(p){const r=await fetch('api.php?'+new URLSearchParams(p));const d=await r.json();if(!r.ok)throw new Error(d.error||'Request failed');return d}
function img(src,cls,w,h){const i=el('img',{class:'ph '+cls,src:src||'',alt:'',width:w,height:h,loading:'lazy'});if(!src){i.removeAttribute('src');return el('span',{class:'ph '+cls,'aria-hidden':'true'})}
 i.addEventListener('error',()=>i.replaceWith(el('span',{class:'ph '+cls,'aria-hidden':'true'})));return i}
function toast(m){const t=$('#toast');t.textContent=m;t.classList.add('on');clearTimeout(t._t);t._t=setTimeout(()=>t.classList.remove('on'),2200)}
function params(){const p={sort:S.sort,maxPrice:S.maxPrice};['q','category','diet','spice','restaurant'].forEach(k=>{if(S[k])p[k]=S[k]});if(S.exclude.length)p.exclude=S.exclude.join(',');return p}
function err(box,e){box.replaceChildren(el('p',{class:'empty',role:'alert'},e.message))}
async function loadDishes(){
 const id=++reqId;
 try{const d=await api({action:'dishes',...params()});if(id!==reqId)return;
  $('#count').textContent=`Showing ${d.length} ${d.length===1?'dish':'dishes'}`;
  $('#grid').replaceChildren(...(d.length?d.map(dishCard):[el('p',{class:'empty'},'No dishes match these filters. Try clearing a filter or searching for something else.')]));
  const sp=d.filter(x=>x.is_special).slice(0,3);$('#heroSec').hidden=!sp.length;
  $('#hero').replaceChildren(...sp.map(x=>el('button',{class:'hbtn',type:'button',onclick:()=>openDish(x.id)},img(x.image,'',84,84),el('div',{},el('strong',{},x.name),el('span',{},x.restaurant),el('br'),el('span',{},`${inr(x.price)}  ★ ${x.rating.toFixed(1)}`)))));
 }catch(e){err($('#grid'),e)}}
function dishCard(x){return el('button',{class:'card',type:'button',onclick:()=>openDish(x.id),'aria-label':`${x.name}, ${x.restaurant}, ${inr(x.price)}. View details`},
 img(x.image,'',400,190),el('span',{class:'dot'+(x.diet==='nonveg'?' nv':''),title:DIET[x.diet]},el('i')),x.is_special?el('span',{class:'badge'},"Chef's Special"):'',
 el('span',{class:'body'},el('h3',{},x.name),el('p',{class:'sub'},`${x.restaurant} · ${x.category}`),el('span',{class:'meta'},el('span',{class:'price'},inr(x.price)),el('span',{},`${SPICE[x.spice]} · ★ ${x.rating.toFixed(1)}`))))}
async function loadRests(){
 try{const d=await api({action:'restaurants',q:S.q,sort:$('#sort').value==='time'?'time':$('#sort').value==='name'?'name':'rating'});
  $('#rcount').textContent=`Showing ${d.length} restaurants`;
  $('#rgrid').replaceChildren(...(d.length?d.map(r=>el('button',{class:'card rcard',type:'button',onclick:()=>openRest(r.id)},img(r.image,'',400,170),
   el('span',{class:'body'},el('h3',{},r.name),el('p',{class:'sub'},r.cuisine),el('span',{class:'meta'},el('span',{},`★ ${r.rating.toFixed(1)}`),el('span',{},`${r.delivery_min}–${r.delivery_max} min`)),el('p',{class:'sub'},`${r.featured} featured menu ${r.featured==1?'item':'items'}`)))):[el('p',{class:'empty'},'No restaurants found.')]));
 }catch(e){err($('#rgrid'),e)}}
function show(d){const dlg=$('#dlg');lastFocus=document.activeElement;$('#dBody').replaceChildren(el('button',{class:'dclose',type:'button','aria-label':'Close',onclick:()=>dlg.close()},'✕'),d);if(!dlg.open)dlg.showModal()}
async function openDish(id){
 try{const x=await api({action:'dish',id});let extra=0;
  const add=el('button',{class:'btn add',type:'button',onclick:()=>{cart++;$('#cartCount').textContent=cart;$('#dlg').close();toast(`${x.name} added to your order`)}},`Add to Order · ${inr(x.price)}`);
  const ex=(n,p)=>{const c=el('input',{type:'checkbox',onchange:e=>{extra+=e.target.checked?p:-p;add.textContent=`Add to Order · ${inr(x.price+extra)}`}});return el('label',{class:'extra'},el('span',{},c,n),el('b',{},'+'+inr(p)))};
  const al=[x.contains_gluten&&'Contains Gluten',x.contains_dairy&&'Contains Dairy',x.contains_nuts&&'Contains Nuts',DIET[x.diet]].filter(Boolean);
  if(!(x.contains_gluten||x.contains_dairy||x.contains_nuts))al.unshift('No common allergens');
  show(el('div',{},img(x.image,'dimg',620,260),el('div',{class:'dbody'},
   el('div',{class:'dhead'},el('h2',{id:'dTitle'},x.name),el('span',{class:'price'},inr(x.price))),
   el('button',{class:'rl',type:'button',onclick:()=>openRest(x.restaurant_id)},x.restaurant),
   el('p',{class:'muted'},x.description),el('p',{class:'muted'},'Category: '+x.category),
   el('div',{class:'stats'},el('div',{},el('small',{},'Spice level'),el('b',{},SPICE[x.spice])),el('div',{},el('small',{},'Calories'),el('b',{},x.calories+' kcal')),el('div',{},el('small',{},'Rating'),el('b',{},'★ '+x.rating.toFixed(1)))),
   el('h3',{},'Allergen & dietary info'),el('div',{class:'pills'},al.map(a=>el('span',{class:'pill'},a))),
   el('h3',{},'Add extra portions'),ex('Extra Sauce / Dip',10),ex("Chef's Special Spice Blend",20),add)));
 }catch(e){toast(e.message)}}
async function openRest(id){
 try{const r=await api({action:'restaurant',id});
  show(el('div',{},el('div',{class:'dbody'},el('h2',{id:'dTitle'},r.name),el('p',{class:'sub'},r.cuisine),
   el('dl',{class:'info'},el('dt',{},'Rating'),el('dd',{},'★ '+r.rating.toFixed(1)),el('dt',{},'Delivery'),el('dd',{},`${r.delivery_min}–${r.delivery_max} min`),el('dt',{},'Address'),el('dd',{},r.address),el('dt',{},'Contact'),el('dd',{},r.phone),el('dt',{},'Hours'),el('dd',{},r.opening_hours)),
   el('a',{class:'map',href:'https://www.google.com/maps/search/?api=1&query='+encodeURIComponent(r.name+' '+r.address),target:'_blank',rel:'noopener'},'View location (opens in new tab)'),
   el('button',{class:'btn add',type:'button',onclick:()=>{$('#dlg').close();S.restaurant=String(r.id);$('#rest').value=S.restaurant;tab('dishes');loadDishes()}},'See this restaurant’s menu'))));
 }catch(e){toast(e.message)}}
function chips(){$('#chips').replaceChildren(...[['','All Dishes',meta.total],...meta.categories.map(c=>[String(c.id),c.name,c.n]),['special',"Chef's Specials",meta.specials]].map(([v,l,n])=>
 el('button',{class:'chip',type:'button','aria-pressed':String(S.category===v),onclick:()=>{S.category=v;chips();loadDishes()}},l,el('small',{},n))))}
function tab(w){const d=w==='dishes';$('#view-dishes').hidden=!d;$('#view-rest').hidden=d;
 $('#tab-dishes').setAttribute('aria-selected',d);$('#tab-rest').setAttribute('aria-selected',!d);$('#tab-dishes').tabIndex=d?0:-1;$('#tab-rest').tabIndex=d?-1:0;
 const o=d?[['popularity','Popularity'],['price_asc','Price: Low to High'],['price_desc','Price: High to Low'],['rating','Rating']]:[['rating','Rating'],['time','Delivery time'],['name','Name']];
 $('#sort').replaceChildren(...o.map(([v,t])=>el('option',{value:v},t)));$('#sort').value=d?S.sort:'rating';d?loadDishes():loadRests()}
function reset(){Object.assign(S,{category:'',q:'',diet:'',spice:'',maxPrice:meta.maxPrice,restaurant:'',exclude:[],sort:'popularity'});
 $('#q').value='';$('#spice').value='';$('#rest').value='';$('#price').value=meta.maxPrice;$('#priceOut').textContent=inr(meta.maxPrice);
 $$('[name=diet]')[0].checked=true;$$('[name=ex]').forEach(c=>c.checked=false);chips();tab('dishes')}
async function init(){
 try{meta=await api({action:'meta'})}catch(e){err($('#grid'),e);return}
 S.maxPrice=meta.maxPrice;const p=$('#price');p.min=meta.minPrice;p.max=meta.maxPrice;p.value=meta.maxPrice;$('#pmin').textContent=inr(meta.minPrice);$('#pmax').textContent=inr(meta.maxPrice);$('#priceOut').textContent=inr(meta.maxPrice);
 $('#rest').append(...meta.restaurants.map(r=>el('option',{value:r.id},r.name)));chips();tab('dishes');
 const re=()=>$('#view-dishes').hidden?loadRests():loadDishes();
 $('#searchForm').addEventListener('submit',e=>e.preventDefault());
 $('#q').addEventListener('input',e=>{S.q=e.target.value.trim();clearTimeout(timer);timer=setTimeout(re,250)});
 $$('[name=diet]').forEach(r=>r.addEventListener('change',()=>{S.diet=r.value;loadDishes()}));
 $('#spice').addEventListener('change',e=>{S.spice=e.target.value;loadDishes()});
 $('#rest').addEventListener('change',e=>{S.restaurant=e.target.value;loadDishes()});
 p.addEventListener('input',e=>{S.maxPrice=+e.target.value;$('#priceOut').textContent=inr(S.maxPrice);clearTimeout(timer);timer=setTimeout(loadDishes,150)});
 $$('[name=ex]').forEach(c=>c.addEventListener('change',()=>{S.exclude=$$('[name=ex]:checked').map(x=>x.value);loadDishes()}));
 $('#sort').addEventListener('change',e=>{if(!$('#view-dishes').hidden){S.sort=e.target.value;loadDishes()}else loadRests()});
 $('#reset').addEventListener('click',reset);$('#clear').addEventListener('click',reset);
 $('#tab-dishes').addEventListener('click',()=>tab('dishes'));$('#tab-rest').addEventListener('click',()=>tab('rest'));
 $('.tabs').addEventListener('keydown',e=>{if(e.key==='ArrowRight'||e.key==='ArrowLeft'){const d=$('#view-dishes').hidden;tab(d?'dishes':'rest');$(d?'#tab-dishes':'#tab-rest').focus()}});
 $('#dlg').addEventListener('close',()=>lastFocus&&lastFocus.isConnected&&lastFocus.focus());
 $('#dlg').addEventListener('click',e=>{if(e.target.id==='dlg')e.target.close()});
 $('#myFood').addEventListener('click',()=>toast(cart?`${cart} item(s) in your order. Checkout is part of Module 3.`:'Your order is empty.'));
}
init();
})();
