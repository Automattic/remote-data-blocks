import*as e from"@wordpress/interactivity";var t={d:(e,n)=>{for(var r in n)t.o(n,r)&&!t.o(e,r)&&Object.defineProperty(e,r,{enumerable:!0,get:n[r]})},o:(e,t)=>Object.prototype.hasOwnProperty.call(e,t)};const n=(r={getContext:()=>e.getContext,getElement:()=>e.getElement,store:()=>e.store,useEffect:()=>e.useEffect,useInit:()=>e.useInit,useState:()=>e.useState},o={},t.d(o,r),o);var r,o;(0,n.store)("remote-data-blocks/airtable-elden-ring-map",{callbacks:{runMap:()=>{const[e,t]=(0,n.useState)(null);(0,n.useInit)((()=>{const{ref:e}=(0,n.getElement)();t(e)}));const r=(0,n.getContext)();(0,n.useEffect)((()=>{if(!e)return;const t=r?.coordinates?[...r.coordinates].map((e=>({...e}))):[];e.innerText=JSON.stringify(t,null,2)}),[r,e])}}});