(()=>{"use strict";var e={20:(e,t,r)=>{var o=r(609),i=Symbol.for("react.element"),s=(Symbol.for("react.fragment"),Object.prototype.hasOwnProperty),n=o.__SECRET_INTERNALS_DO_NOT_USE_OR_YOU_WILL_BE_FIRED.ReactCurrentOwner,a={key:!0,ref:!0,__self:!0,__source:!0};function l(e,t,r){var o,l={},c=null,p=null;for(o in void 0!==r&&(c=""+r),void 0!==t.key&&(c=""+t.key),void 0!==t.ref&&(p=t.ref),t)s.call(t,o)&&!a.hasOwnProperty(o)&&(l[o]=t[o]);if(e&&e.defaultProps)for(o in t=e.defaultProps)void 0===l[o]&&(l[o]=t[o]);return{$$typeof:i,type:e,key:c,ref:p,props:l,_owner:n.current}}t.jsx=l,t.jsxs=l},848:(e,t,r)=>{e.exports=r(20)},609:e=>{e.exports=window.React}},t={},r=function r(o){var i=t[o];if(void 0!==i)return i.exports;var s=t[o]={exports:{}};return e[o](s,s.exports,r),s.exports}(848);const o=window.wp.hooks;(0,o.addFilter)("remote-data-blocks.list-header","remote-data-blocks/search-panel",(function(e,t){return"remote-data-blocks/shopify-product"!==t.blockName?e:function(){return(0,r.jsx)("img",{style:{height:"75px"},src:"".concat(SHOPIFY_LIST_PANEL.assetPath,"/shopify_logo_black.png"),alt:"Shopify Logo"})}})),(0,o.addFilter)("remote-data-blocks.list-item","remote-data-blocks/list-panel",(function(e,t){return"remote-data-blocks/shopify-product"!==t.blockName?e:function(e){return(0,r.jsx)("div",{children:(0,r.jsxs)("div",{style:{display:"flex",flexDirection:"row",gap:"8px"},children:[(0,r.jsx)("img",{style:{height:"75px",width:"75px",objectFit:"contain"},src:e.item.image_url,alt:"Shopify product"}),(0,r.jsxs)("div",{children:[(0,r.jsx)("h2",{children:e.item.title}),(0,r.jsx)("p",{children:e.item.price})]})]})})}}))})();