(()=>{"use strict";var e={};e.g=function(){if("object"==typeof globalThis)return globalThis;try{return this||new Function("return this")()}catch(e){if("object"==typeof window)return window}}();const t=window.wp.blocks,r=JSON.parse('{"$schema":"https://schemas.wp.org/trunk/block.json","apiVersion":3,"name":"remote-data-blocks/elden-ring-map","usesContext":["remote-data-blocks/remoteData"],"version":"0.1.0","title":"Elden Ring Map","category":"widgets","icon":"location-alt","description":"Elden Ring Map","example":{},"supports":{"html":false,"inserter":false},"textdomain":"remote-data-blocks","editorScript":["file:./index.js","leaflet-script"],"editorStyle":["leaflet-style"],"viewScript":["file:./view.js"]}'),n=window.wp.blockEditor,o=window.wp.element;(0,t.registerBlockType)(r.name,{...r,edit:function({context:t}){const r=(0,n.useBlockProps)(),s=(0,n.useInnerBlocksProps)(r),a=(0,o.useRef)(),l=(0,o.useRef)(),c=(0,o.useRef)(),i=t["remote-data-blocks/remoteData"];(0,o.useEffect)((()=>{if(a.current){if(!l.current){const t=e.g.L.map(a.current).setView([i?.results[0].x,i?.results[0].y],25);e.g.L.tileLayer("https://tile.openstreetmap.org/{z}/{x}/{y}.png",{maxZoom:4}).addTo(t),t._handlers.forEach((e=>e.disable())),l.current=t,c.current=e.g.L.layerGroup().addTo(l.current)}c.current.clearLayers(),i?.results.forEach((t=>{e.g.L.marker([t.x,t.y]).addTo(c.current)})),l.current.flyTo([i?.results[0].x,i?.results[0].y])}}),[a.current,t]);const u=Boolean(i?.results);return React.createElement(React.Fragment,null,React.createElement(n.BlockControls,null),React.createElement("div",s,u?React.createElement("div",{ref:a,id:"map",style:{height:400}}):React.createElement("p",{style:{color:"red",padding:"20px"}},"This block only supports being rendered inside of an Elden Ring Map Query block.")))},save:function(e,t,r){return console.log({savedContext:1,a:e,b:t,c:r}),React.createElement("p",n.useBlockProps.save(),React.createElement(n.InnerBlocks.Content,null))}})})();