(()=>{var e,t={567:(e,t,n)=>{"use strict";var r={};n.r(r),n.d(r,{AddNewButton:()=>N,AddNewTable:()=>R,EmailStatus:()=>Mt,Image:()=>E,Section:()=>z,SelectableCard:()=>S,SelectableCards:()=>B,useAddNewModal:()=>j});const a=window.React,o=window.wp.domReady;var i=n.n(o);const s=window.wp.element,l=window.wp.components,c=window.noptinEmailSettingsMisc||{},u=(c.license,window.wp.i18n),d=window.wp.url,p=window.noptinEmailEditorSettings||{},m=window.noptinEmailSettingsMisc||{},f=(m.license,p.types||{}),h=(Object.keys(f).map((e=>({value:e,label:f[e].label}))),Object.keys(p.templates||{}).map((e=>({value:e,label:p.templates[e]}))),m.license||{}),g=(h.is_usable&&h.key,/_published|_unpublished|_deleted$/),y=["automation_rule_new_subscriber"],b=["WordPress Users"],v=(e,t=void 0)=>{if(!e)return!1;if(t&&b.includes(t))return!0;const n=e.replace(/^automation_rule_/,"");return g.test(n)?"post_published"!==n:y.includes(n)};var _=n(184),k=n.n(_);const x=function({categories:e,selectedCategory:t,onClickCategory:n}){const r="noptin-campaign-explorer__sidebar";return(0,a.createElement)("div",{className:r},(0,a.createElement)("div",{className:`${r}__categories-list`},e.map((e=>(0,a.createElement)(l.Button,{key:e,label:e,className:`${r}__categories-list__item`,isPressed:t===e,onClick:()=>{n(e)}},e)))))},w=c.license||{},E=({image:e,title:t})=>{if("string"==typeof e&&e.startsWith("http"))return(0,a.createElement)("img",{src:e,width:24,alt:t,style:{maxWidth:24}});if("string"==typeof e)return(0,a.createElement)(l.Icon,{size:24,icon:e,style:{color:"#424242"}});if("object"==typeof e){const t=e.fill||"#008000",n=e.path||"",r=e.viewBox||"0 0 24 24";return e.path?(0,a.createElement)(l.SVG,{viewBox:r,xmlns:"http://www.w3.org/2000/svg",style:{maxWidth:24}},(0,a.createElement)(l.Path,{fill:t,d:n})):(0,a.createElement)(l.Icon,{size:24,style:{color:t},icon:e.icon})}return(0,a.createElement)(l.Icon,{size:24,icon:"email",style:{color:"#424242"}})},S=({name:e,label:t,description:n,image:r,is_installed:o,forcePremium:i,onSelect:s})=>{const p=(0,a.useCallback)((()=>s(e)),[e,s]),[m,f]=((e,t,n,r)=>{if(t?e&&w.key:e)return[null,(0,a.createElement)(l.Button,{variant:"primary",onClick:r},(0,a.createElement)("span",{className:"noptin-selectable-card__label"},(0,u.__)("Select"))," ",(0,a.createElement)(l.Icon,{size:16,icon:"arrow-right-alt"}))];const o=!e&&w.install_desc||(0,u.__)("Activate your license key to unlock","newsletter-optin-box"),i=!e&&w.install_text||(0,u.__)("View Pricing","newsletter-optin-box");let s=!e&&w.install_url||w.upgrade_url||"https://noptin.com/pricing/";w.key||(s=(0,d.addQueryArgs)(s,{utm_source:n,utm_campaign:(c.data?.type||"noptin")+"-emails"}));const p=w.key?"primary":"secondary";return[o,(0,a.createElement)(l.Button,{variant:p,href:s},(0,a.createElement)("span",{className:"noptin-selectable-card-action__label"},i)," ",(0,a.createElement)(l.Icon,{size:16,icon:"lock"}))]})(!1!==o,i,e,p);return(0,a.createElement)(l.Card,{className:`noptin-selectable-card noptin-selectable-card__${e}`,onClick:p,size:"small"},(0,a.createElement)(l.CardHeader,null,(0,a.createElement)(l.__experimentalHeading,{level:4,numberOfLines:1},t),(0,a.createElement)(E,{image:r,title:t})),(0,a.createElement)(l.CardBody,null,(0,a.createElement)(l.__experimentalVStack,{spacing:4},n&&(0,a.createElement)(l.__experimentalText,{as:"p",variant:"muted"},n),m&&(0,a.createElement)(l.__experimentalText,{as:"em",isDestructive:!0},m))),(0,a.createElement)(l.CardFooter,{isBorderless:!0,justify:"flex-end"},f),(0,a.createElement)(l.__experimentalElevation,{value:1,hover:3,isInteractive:!0}))},C=function({types:e,selectedCategory:t,showTitle:n,onSelect:r}){return(0,a.createElement)("div",{className:"noptin-campaign-explorer__list"},n&&(0,a.createElement)(l.__experimentalHeading,{level:2,lineHeight:"48px",className:"noptin-campaign-explorer__category-name"},t),(0,a.createElement)("div",{role:"listbox",className:"noptin-campaign-explorer-list"},Object.keys(e).map((t=>(0,a.createElement)(S,{key:t,name:t,onSelect:r,...e[t]})))))};function A({cardGroups:e,onSelect:t}){const n=Object.keys(e).length,[r,o]=(0,s.useState)(Object.keys(e)[0]);(0,s.useEffect)((()=>{n>0&&!e[r]&&o(Object.keys(e)[0])}),[e,r]);const i=n>1,l=(0,s.useMemo)((()=>Object.keys(e)),[e]),c=(0,s.useMemo)((()=>e[r]||{}),[e,r]),u=k()("noptin-campaign-explorer",{"noptin-campaign-explorer--show-sidebar":i});return(0,a.createElement)("div",{className:u},i&&(0,a.createElement)(x,{selectedCategory:r,categories:l,onClickCategory:o}),(0,a.createElement)(C,{showTitle:i,selectedCategory:r,types:c,onSelect:t}))}const O=function({title:e,isOpen:t,closeModal:n,back:r,...o}){const i=(0,a.createElement)(a.Fragment,null,r&&(0,a.createElement)(l.Button,{icon:"arrow-left-alt",onClick:r,label:(0,u.__)("Back","newsletter-optin-box"),showTooltip:!0}));return(0,a.createElement)(a.Fragment,null,t&&(0,a.createElement)(l.Modal,{title:e,onRequestClose:n,headerActions:i,isFullScreen:!0},(0,a.createElement)(A,{...o})))},M=c.data?.add_new||(0,d.addQueryArgs)(window.location.href,{noptin_campaign:0}),$=c.senders||{},P=function(e){const t={};return Array.isArray(e)?{}:(Object.entries(e).forEach((([e,n])=>{if(!n.category){if(!c.isTest)return;n.category="Deprecated"}t[n.category]||(t[n.category]={}),t[n.category][e]={...n,forcePremium:v(e,n.category)}})),t)}(c.data?.sub_types||{}),T=Object.keys(P).length>0,j=()=>{const[e,t]=(0,a.useState)(!1),[n,r]=(0,a.useState)(""),[o,i]=(0,a.useState)(""),[p,m]=(0,a.useState)(!1),f=(0,a.useCallback)((()=>{t(!0)}),[t]),h=(0,a.useCallback)((()=>{t(!1)}),[t]);(0,s.useEffect)((()=>{if(!n&&!o)return;if(T&&!n)return;if(c.data?.supports_recipients&&!o)return;const e={};n&&(e.noptin_email_sub_type=n),o&&(e.noptin_email_sender=o),window.location.href=(0,d.addQueryArgs)(M,e),m(!0)}),[n,o]);const g=(0,a.useCallback)((()=>{m(!1),r("")}),[m,r]),y=(0,a.useCallback)((()=>{m(!1),h(),i(""),r("")}),[m,h,i,r]);return p?{hasModal:!0,openModal:f,url:M,modal:(0,a.createElement)(a.Fragment,null,e&&(0,a.createElement)(l.Modal,{onRequestClose:y,__experimentalHideHeader:!0},(0,a.createElement)(l.Flex,null,(0,a.createElement)(l.FlexItem,null,(0,a.createElement)(l.Spinner,null)),(0,a.createElement)(l.FlexItem,null,(0,u.__)("Redirecting...","newsletter-optin-box")))))}:T&&!n?{hasModal:!0,url:M,openModal:f,modal:(0,a.createElement)(O,{isOpen:e,title:(0,u.__)("Select Campaign Type","newsletter-optin-box"),cardGroups:P,onSelect:r,closeModal:h})}:c.data?.supports_recipients?{hasModal:!0,url:M,openModal:f,modal:(0,a.createElement)(O,{isOpen:e,title:(0,u.__)("Send to","newsletter-optin-box"),cardGroups:{[(0,u.__)("Send to","newsletter-optin-box")]:$},onSelect:i,closeModal:h,back:T?g:void 0})}:{hasModal:!1,url:M,openModal:f,modal:null}},N=()=>{const e=j();return(0,a.createElement)(a.Fragment,null,(0,a.createElement)(l.Button,{variant:"primary",href:e.hasModal?void 0:e.url,text:c.data?.new_campaign_label,type:"button",onClick:e.hasModal?e.openModal:void 0}),e.hasModal&&e.modal)},R=()=>(0,a.createElement)(l.__experimentalVStack,{alignment:"center",justify:"center",spacing:6,style:{minHeight:320}},(0,a.createElement)(l.Icon,{icon:c.data?.icon,size:100,style:{color:"#646970"}}),(0,a.createElement)(l.__experimentalText,{align:"center",color:"#646970",size:16,isBlock:!0},c.data?.click_to_add_first),(0,a.createElement)(N,null));function z({title:e,isSecodary:t,className:n,children:r}){const[o,i]=(0,a.useState)(!0),s=k()(n,"noptin-component__section");return(0,a.createElement)(l.Card,{variant:t?"secondary":"primary",className:s},(0,a.createElement)(l.CardHeader,null,(0,a.createElement)(l.Flex,null,(0,a.createElement)(l.FlexBlock,null,(0,a.createElement)("h3",null,e)),(0,a.createElement)(l.FlexItem,null,(0,a.createElement)(l.Button,{variant:"tertiary",onClick:()=>i(!o),icon:o?"arrow-up-alt2":"arrow-down-alt2"})))),o&&r)}const I=({showingAll:e=!0,cards:t,onSelect:n})=>{const r=Object.entries(t),o=e?r:r.slice(0,3);return(0,a.createElement)(l.Flex,{className:"noptin-selectable-card",justify:"left",align:"stretch",wrap:!0},o.map((([e,t],r)=>(0,a.createElement)(S,{key:`${e}__${r}`,onSelect:n,name:e,...t}))))},B=({cards:e,title:t,onSelect:n,onGroupSelect:r,unwrap:o=!1})=>{const i=Object.keys(e).length,s=r&&i>3;if(console.log(i),0===i)return null;const c=(0,a.createElement)(I,{cards:e,onSelect:n,showingAll:o||!s});if(o)return c;const d={};return s&&(d["aria-expanded"]="false",d.onClick=()=>r&&r(t),d.label=(0,u.__)("Show all","newsletter-optin-box"),d.showTooltip=!0),(0,a.createElement)(l.__experimentalVStack,{spacing:2},(0,a.createElement)(l.__experimentalHStack,{as:s?l.Button:"h2",...d},t),c)};function F(){return F=Object.assign?Object.assign.bind():function(e){for(var t=1;t<arguments.length;t++){var n=arguments[t];for(var r in n)Object.prototype.hasOwnProperty.call(n,r)&&(e[r]=n[r])}return e},F.apply(this,arguments)}function H(e){var t=Object.create(null);return function(n){return void 0===t[n]&&(t[n]=e(n)),t[n]}}var L=/^((children|dangerouslySetInnerHTML|key|ref|autoFocus|defaultValue|defaultChecked|innerHTML|suppressContentEditableWarning|suppressHydrationWarning|valueLink|abbr|accept|acceptCharset|accessKey|action|allow|allowUserMedia|allowPaymentRequest|allowFullScreen|allowTransparency|alt|async|autoComplete|autoPlay|capture|cellPadding|cellSpacing|challenge|charSet|checked|cite|classID|className|cols|colSpan|content|contentEditable|contextMenu|controls|controlsList|coords|crossOrigin|data|dateTime|decoding|default|defer|dir|disabled|disablePictureInPicture|download|draggable|encType|enterKeyHint|form|formAction|formEncType|formMethod|formNoValidate|formTarget|frameBorder|headers|height|hidden|high|href|hrefLang|htmlFor|httpEquiv|id|inputMode|integrity|is|keyParams|keyType|kind|label|lang|list|loading|loop|low|marginHeight|marginWidth|max|maxLength|media|mediaGroup|method|min|minLength|multiple|muted|name|nonce|noValidate|open|optimum|pattern|placeholder|playsInline|poster|preload|profile|radioGroup|readOnly|referrerPolicy|rel|required|reversed|role|rows|rowSpan|sandbox|scope|scoped|scrolling|seamless|selected|shape|size|sizes|slot|span|spellCheck|src|srcDoc|srcLang|srcSet|start|step|style|summary|tabIndex|target|title|translate|type|useMap|value|width|wmode|wrap|about|datatype|inlist|prefix|property|resource|typeof|vocab|autoCapitalize|autoCorrect|autoSave|color|incremental|fallback|inert|itemProp|itemScope|itemType|itemID|itemRef|on|option|results|security|unselectable|accentHeight|accumulate|additive|alignmentBaseline|allowReorder|alphabetic|amplitude|arabicForm|ascent|attributeName|attributeType|autoReverse|azimuth|baseFrequency|baselineShift|baseProfile|bbox|begin|bias|by|calcMode|capHeight|clip|clipPathUnits|clipPath|clipRule|colorInterpolation|colorInterpolationFilters|colorProfile|colorRendering|contentScriptType|contentStyleType|cursor|cx|cy|d|decelerate|descent|diffuseConstant|direction|display|divisor|dominantBaseline|dur|dx|dy|edgeMode|elevation|enableBackground|end|exponent|externalResourcesRequired|fill|fillOpacity|fillRule|filter|filterRes|filterUnits|floodColor|floodOpacity|focusable|fontFamily|fontSize|fontSizeAdjust|fontStretch|fontStyle|fontVariant|fontWeight|format|from|fr|fx|fy|g1|g2|glyphName|glyphOrientationHorizontal|glyphOrientationVertical|glyphRef|gradientTransform|gradientUnits|hanging|horizAdvX|horizOriginX|ideographic|imageRendering|in|in2|intercept|k|k1|k2|k3|k4|kernelMatrix|kernelUnitLength|kerning|keyPoints|keySplines|keyTimes|lengthAdjust|letterSpacing|lightingColor|limitingConeAngle|local|markerEnd|markerMid|markerStart|markerHeight|markerUnits|markerWidth|mask|maskContentUnits|maskUnits|mathematical|mode|numOctaves|offset|opacity|operator|order|orient|orientation|origin|overflow|overlinePosition|overlineThickness|panose1|paintOrder|pathLength|patternContentUnits|patternTransform|patternUnits|pointerEvents|points|pointsAtX|pointsAtY|pointsAtZ|preserveAlpha|preserveAspectRatio|primitiveUnits|r|radius|refX|refY|renderingIntent|repeatCount|repeatDur|requiredExtensions|requiredFeatures|restart|result|rotate|rx|ry|scale|seed|shapeRendering|slope|spacing|specularConstant|specularExponent|speed|spreadMethod|startOffset|stdDeviation|stemh|stemv|stitchTiles|stopColor|stopOpacity|strikethroughPosition|strikethroughThickness|string|stroke|strokeDasharray|strokeDashoffset|strokeLinecap|strokeLinejoin|strokeMiterlimit|strokeOpacity|strokeWidth|surfaceScale|systemLanguage|tableValues|targetX|targetY|textAnchor|textDecoration|textRendering|textLength|to|transform|u1|u2|underlinePosition|underlineThickness|unicode|unicodeBidi|unicodeRange|unitsPerEm|vAlphabetic|vHanging|vIdeographic|vMathematical|values|vectorEffect|version|vertAdvY|vertOriginX|vertOriginY|viewBox|viewTarget|visibility|widths|wordSpacing|writingMode|x|xHeight|x1|x2|xChannelSelector|xlinkActuate|xlinkArcrole|xlinkHref|xlinkRole|xlinkShow|xlinkTitle|xlinkType|xmlBase|xmlns|xmlnsXlink|xmlLang|xmlSpace|y|y1|y2|yChannelSelector|z|zoomAndPan|for|class|autofocus)|(([Dd][Aa][Tt][Aa]|[Aa][Rr][Ii][Aa]|x)-.*))$/,q=H((function(e){return L.test(e)||111===e.charCodeAt(0)&&110===e.charCodeAt(1)&&e.charCodeAt(2)<91})),D=function(){function e(e){var t=this;this._insertTag=function(e){var n;n=0===t.tags.length?t.insertionPoint?t.insertionPoint.nextSibling:t.prepend?t.container.firstChild:t.before:t.tags[t.tags.length-1].nextSibling,t.container.insertBefore(e,n),t.tags.push(e)},this.isSpeedy=void 0===e.speedy||e.speedy,this.tags=[],this.ctr=0,this.nonce=e.nonce,this.key=e.key,this.container=e.container,this.prepend=e.prepend,this.insertionPoint=e.insertionPoint,this.before=null}var t=e.prototype;return t.hydrate=function(e){e.forEach(this._insertTag)},t.insert=function(e){this.ctr%(this.isSpeedy?65e3:1)==0&&this._insertTag(function(e){var t=document.createElement("style");return t.setAttribute("data-emotion",e.key),void 0!==e.nonce&&t.setAttribute("nonce",e.nonce),t.appendChild(document.createTextNode("")),t.setAttribute("data-s",""),t}(this));var t=this.tags[this.tags.length-1];if(this.isSpeedy){var n=function(e){if(e.sheet)return e.sheet;for(var t=0;t<document.styleSheets.length;t++)if(document.styleSheets[t].ownerNode===e)return document.styleSheets[t]}(t);try{n.insertRule(e,n.cssRules.length)}catch(e){}}else t.appendChild(document.createTextNode(e));this.ctr++},t.flush=function(){this.tags.forEach((function(e){return e.parentNode&&e.parentNode.removeChild(e)})),this.tags=[],this.ctr=0},e}(),G=Math.abs,W=String.fromCharCode,U=Object.assign;function V(e){return e.trim()}function X(e,t,n){return e.replace(t,n)}function Y(e,t){return e.indexOf(t)}function Q(e,t){return 0|e.charCodeAt(t)}function K(e,t,n){return e.slice(t,n)}function Z(e){return e.length}function J(e){return e.length}function ee(e,t){return t.push(e),e}var te=1,ne=1,re=0,ae=0,oe=0,ie="";function se(e,t,n,r,a,o,i){return{value:e,root:t,parent:n,type:r,props:a,children:o,line:te,column:ne,length:i,return:""}}function le(e,t){return U(se("",null,null,"",null,null,0),e,{length:-e.length},t)}function ce(){return oe=ae>0?Q(ie,--ae):0,ne--,10===oe&&(ne=1,te--),oe}function ue(){return oe=ae<re?Q(ie,ae++):0,ne++,10===oe&&(ne=1,te++),oe}function de(){return Q(ie,ae)}function pe(){return ae}function me(e,t){return K(ie,e,t)}function fe(e){switch(e){case 0:case 9:case 10:case 13:case 32:return 5;case 33:case 43:case 44:case 47:case 62:case 64:case 126:case 59:case 123:case 125:return 4;case 58:return 3;case 34:case 39:case 40:case 91:return 2;case 41:case 93:return 1}return 0}function he(e){return te=ne=1,re=Z(ie=e),ae=0,[]}function ge(e){return ie="",e}function ye(e){return V(me(ae-1,_e(91===e?e+2:40===e?e+1:e)))}function be(e){for(;(oe=de())&&oe<33;)ue();return fe(e)>2||fe(oe)>3?"":" "}function ve(e,t){for(;--t&&ue()&&!(oe<48||oe>102||oe>57&&oe<65||oe>70&&oe<97););return me(e,pe()+(t<6&&32==de()&&32==ue()))}function _e(e){for(;ue();)switch(oe){case e:return ae;case 34:case 39:34!==e&&39!==e&&_e(oe);break;case 40:41===e&&_e(e);break;case 92:ue()}return ae}function ke(e,t){for(;ue()&&e+oe!==57&&(e+oe!==84||47!==de()););return"/*"+me(t,ae-1)+"*"+W(47===e?e:ue())}function xe(e){for(;!fe(de());)ue();return me(e,ae)}var we="-ms-",Ee="-moz-",Se="-webkit-",Ce="comm",Ae="rule",Oe="decl",Me="@keyframes";function $e(e,t){for(var n="",r=J(e),a=0;a<r;a++)n+=t(e[a],a,e,t)||"";return n}function Pe(e,t,n,r){switch(e.type){case"@layer":if(e.children.length)break;case"@import":case Oe:return e.return=e.return||e.value;case Ce:return"";case Me:return e.return=e.value+"{"+$e(e.children,r)+"}";case Ae:e.value=e.props.join(",")}return Z(n=$e(e.children,r))?e.return=e.value+"{"+n+"}":""}function Te(e){return ge(je("",null,null,null,[""],e=he(e),0,[0],e))}function je(e,t,n,r,a,o,i,s,l){for(var c=0,u=0,d=i,p=0,m=0,f=0,h=1,g=1,y=1,b=0,v="",_=a,k=o,x=r,w=v;g;)switch(f=b,b=ue()){case 40:if(108!=f&&58==Q(w,d-1)){-1!=Y(w+=X(ye(b),"&","&\f"),"&\f")&&(y=-1);break}case 34:case 39:case 91:w+=ye(b);break;case 9:case 10:case 13:case 32:w+=be(f);break;case 92:w+=ve(pe()-1,7);continue;case 47:switch(de()){case 42:case 47:ee(Re(ke(ue(),pe()),t,n),l);break;default:w+="/"}break;case 123*h:s[c++]=Z(w)*y;case 125*h:case 59:case 0:switch(b){case 0:case 125:g=0;case 59+u:-1==y&&(w=X(w,/\f/g,"")),m>0&&Z(w)-d&&ee(m>32?ze(w+";",r,n,d-1):ze(X(w," ","")+";",r,n,d-2),l);break;case 59:w+=";";default:if(ee(x=Ne(w,t,n,c,u,a,s,v,_=[],k=[],d),o),123===b)if(0===u)je(w,t,x,x,_,o,d,s,k);else switch(99===p&&110===Q(w,3)?100:p){case 100:case 108:case 109:case 115:je(e,x,x,r&&ee(Ne(e,x,x,0,0,a,s,v,a,_=[],d),k),a,k,d,s,r?_:k);break;default:je(w,x,x,x,[""],k,0,s,k)}}c=u=m=0,h=y=1,v=w="",d=i;break;case 58:d=1+Z(w),m=f;default:if(h<1)if(123==b)--h;else if(125==b&&0==h++&&125==ce())continue;switch(w+=W(b),b*h){case 38:y=u>0?1:(w+="\f",-1);break;case 44:s[c++]=(Z(w)-1)*y,y=1;break;case 64:45===de()&&(w+=ye(ue())),p=de(),u=d=Z(v=w+=xe(pe())),b++;break;case 45:45===f&&2==Z(w)&&(h=0)}}return o}function Ne(e,t,n,r,a,o,i,s,l,c,u){for(var d=a-1,p=0===a?o:[""],m=J(p),f=0,h=0,g=0;f<r;++f)for(var y=0,b=K(e,d+1,d=G(h=i[f])),v=e;y<m;++y)(v=V(h>0?p[y]+" "+b:X(b,/&\f/g,p[y])))&&(l[g++]=v);return se(e,t,n,0===a?Ae:s,l,c,u)}function Re(e,t,n){return se(e,t,n,Ce,W(oe),K(e,2,-2),0)}function ze(e,t,n,r){return se(e,t,n,Oe,K(e,0,r),K(e,r+1,-1),r)}var Ie=function(e,t,n){for(var r=0,a=0;r=a,a=de(),38===r&&12===a&&(t[n]=1),!fe(a);)ue();return me(e,ae)},Be=new WeakMap,Fe=function(e){if("rule"===e.type&&e.parent&&!(e.length<1)){for(var t=e.value,n=e.parent,r=e.column===n.column&&e.line===n.line;"rule"!==n.type;)if(!(n=n.parent))return;if((1!==e.props.length||58===t.charCodeAt(0)||Be.get(n))&&!r){Be.set(e,!0);for(var a=[],o=function(e,t){return ge(function(e,t){var n=-1,r=44;do{switch(fe(r)){case 0:38===r&&12===de()&&(t[n]=1),e[n]+=Ie(ae-1,t,n);break;case 2:e[n]+=ye(r);break;case 4:if(44===r){e[++n]=58===de()?"&\f":"",t[n]=e[n].length;break}default:e[n]+=W(r)}}while(r=ue());return e}(he(e),t))}(t,a),i=n.props,s=0,l=0;s<o.length;s++)for(var c=0;c<i.length;c++,l++)e.props[l]=a[s]?o[s].replace(/&\f/g,i[c]):i[c]+" "+o[s]}}},He=function(e){if("decl"===e.type){var t=e.value;108===t.charCodeAt(0)&&98===t.charCodeAt(2)&&(e.return="",e.value="")}};function Le(e,t){switch(function(e,t){return 45^Q(e,0)?(((t<<2^Q(e,0))<<2^Q(e,1))<<2^Q(e,2))<<2^Q(e,3):0}(e,t)){case 5103:return Se+"print-"+e+e;case 5737:case 4201:case 3177:case 3433:case 1641:case 4457:case 2921:case 5572:case 6356:case 5844:case 3191:case 6645:case 3005:case 6391:case 5879:case 5623:case 6135:case 4599:case 4855:case 4215:case 6389:case 5109:case 5365:case 5621:case 3829:return Se+e+e;case 5349:case 4246:case 4810:case 6968:case 2756:return Se+e+Ee+e+we+e+e;case 6828:case 4268:return Se+e+we+e+e;case 6165:return Se+e+we+"flex-"+e+e;case 5187:return Se+e+X(e,/(\w+).+(:[^]+)/,Se+"box-$1$2"+we+"flex-$1$2")+e;case 5443:return Se+e+we+"flex-item-"+X(e,/flex-|-self/,"")+e;case 4675:return Se+e+we+"flex-line-pack"+X(e,/align-content|flex-|-self/,"")+e;case 5548:return Se+e+we+X(e,"shrink","negative")+e;case 5292:return Se+e+we+X(e,"basis","preferred-size")+e;case 6060:return Se+"box-"+X(e,"-grow","")+Se+e+we+X(e,"grow","positive")+e;case 4554:return Se+X(e,/([^-])(transform)/g,"$1"+Se+"$2")+e;case 6187:return X(X(X(e,/(zoom-|grab)/,Se+"$1"),/(image-set)/,Se+"$1"),e,"")+e;case 5495:case 3959:return X(e,/(image-set\([^]*)/,Se+"$1$`$1");case 4968:return X(X(e,/(.+:)(flex-)?(.*)/,Se+"box-pack:$3"+we+"flex-pack:$3"),/s.+-b[^;]+/,"justify")+Se+e+e;case 4095:case 3583:case 4068:case 2532:return X(e,/(.+)-inline(.+)/,Se+"$1$2")+e;case 8116:case 7059:case 5753:case 5535:case 5445:case 5701:case 4933:case 4677:case 5533:case 5789:case 5021:case 4765:if(Z(e)-1-t>6)switch(Q(e,t+1)){case 109:if(45!==Q(e,t+4))break;case 102:return X(e,/(.+:)(.+)-([^]+)/,"$1"+Se+"$2-$3$1"+Ee+(108==Q(e,t+3)?"$3":"$2-$3"))+e;case 115:return~Y(e,"stretch")?Le(X(e,"stretch","fill-available"),t)+e:e}break;case 4949:if(115!==Q(e,t+1))break;case 6444:switch(Q(e,Z(e)-3-(~Y(e,"!important")&&10))){case 107:return X(e,":",":"+Se)+e;case 101:return X(e,/(.+:)([^;!]+)(;|!.+)?/,"$1"+Se+(45===Q(e,14)?"inline-":"")+"box$3$1"+Se+"$2$3$1"+we+"$2box$3")+e}break;case 5936:switch(Q(e,t+11)){case 114:return Se+e+we+X(e,/[svh]\w+-[tblr]{2}/,"tb")+e;case 108:return Se+e+we+X(e,/[svh]\w+-[tblr]{2}/,"tb-rl")+e;case 45:return Se+e+we+X(e,/[svh]\w+-[tblr]{2}/,"lr")+e}return Se+e+we+e+e}return e}var qe=[function(e,t,n,r){if(e.length>-1&&!e.return)switch(e.type){case Oe:e.return=Le(e.value,e.length);break;case Me:return $e([le(e,{value:X(e.value,"@","@"+Se)})],r);case Ae:if(e.length)return function(e,t){return e.map(t).join("")}(e.props,(function(t){switch(function(e,t){return(e=/(::plac\w+|:read-\w+)/.exec(e))?e[0]:e}(t)){case":read-only":case":read-write":return $e([le(e,{props:[X(t,/:(read-\w+)/,":-moz-$1")]})],r);case"::placeholder":return $e([le(e,{props:[X(t,/:(plac\w+)/,":"+Se+"input-$1")]}),le(e,{props:[X(t,/:(plac\w+)/,":-moz-$1")]}),le(e,{props:[X(t,/:(plac\w+)/,we+"input-$1")]})],r)}return""}))}}],De=function(e){var t=e.key;if("css"===t){var n=document.querySelectorAll("style[data-emotion]:not([data-s])");Array.prototype.forEach.call(n,(function(e){-1!==e.getAttribute("data-emotion").indexOf(" ")&&(document.head.appendChild(e),e.setAttribute("data-s",""))}))}var r,a,o=e.stylisPlugins||qe,i={},s=[];r=e.container||document.head,Array.prototype.forEach.call(document.querySelectorAll('style[data-emotion^="'+t+' "]'),(function(e){for(var t=e.getAttribute("data-emotion").split(" "),n=1;n<t.length;n++)i[t[n]]=!0;s.push(e)}));var l,c,u,d,p=[Pe,(d=function(e){l.insert(e)},function(e){e.root||(e=e.return)&&d(e)})],m=(c=[Fe,He].concat(o,p),u=J(c),function(e,t,n,r){for(var a="",o=0;o<u;o++)a+=c[o](e,t,n,r)||"";return a});a=function(e,t,n,r){l=n,$e(Te(e?e+"{"+t.styles+"}":t.styles),m),r&&(f.inserted[t.name]=!0)};var f={key:t,sheet:new D({key:t,container:r,nonce:e.nonce,speedy:e.speedy,prepend:e.prepend,insertionPoint:e.insertionPoint}),nonce:e.nonce,inserted:i,registered:{},insert:a};return f.sheet.hydrate(s),f},Ge={animationIterationCount:1,aspectRatio:1,borderImageOutset:1,borderImageSlice:1,borderImageWidth:1,boxFlex:1,boxFlexGroup:1,boxOrdinalGroup:1,columnCount:1,columns:1,flex:1,flexGrow:1,flexPositive:1,flexShrink:1,flexNegative:1,flexOrder:1,gridRow:1,gridRowEnd:1,gridRowSpan:1,gridRowStart:1,gridColumn:1,gridColumnEnd:1,gridColumnSpan:1,gridColumnStart:1,msGridRow:1,msGridRowSpan:1,msGridColumn:1,msGridColumnSpan:1,fontWeight:1,lineHeight:1,opacity:1,order:1,orphans:1,tabSize:1,widows:1,zIndex:1,zoom:1,WebkitLineClamp:1,fillOpacity:1,floodOpacity:1,stopOpacity:1,strokeDasharray:1,strokeDashoffset:1,strokeMiterlimit:1,strokeOpacity:1,strokeWidth:1},We=/[A-Z]|^ms/g,Ue=/_EMO_([^_]+?)_([^]*?)_EMO_/g,Ve=function(e){return 45===e.charCodeAt(1)},Xe=function(e){return null!=e&&"boolean"!=typeof e},Ye=H((function(e){return Ve(e)?e:e.replace(We,"-$&").toLowerCase()})),Qe=function(e,t){switch(e){case"animation":case"animationName":if("string"==typeof t)return t.replace(Ue,(function(e,t,n){return Ze={name:t,styles:n,next:Ze},t}))}return 1===Ge[e]||Ve(e)||"number"!=typeof t||0===t?t:t+"px"};function Ke(e,t,n){if(null==n)return"";if(void 0!==n.__emotion_styles)return n;switch(typeof n){case"boolean":return"";case"object":if(1===n.anim)return Ze={name:n.name,styles:n.styles,next:Ze},n.name;if(void 0!==n.styles){var r=n.next;if(void 0!==r)for(;void 0!==r;)Ze={name:r.name,styles:r.styles,next:Ze},r=r.next;return n.styles+";"}return function(e,t,n){var r="";if(Array.isArray(n))for(var a=0;a<n.length;a++)r+=Ke(e,t,n[a])+";";else for(var o in n){var i=n[o];if("object"!=typeof i)null!=t&&void 0!==t[i]?r+=o+"{"+t[i]+"}":Xe(i)&&(r+=Ye(o)+":"+Qe(o,i)+";");else if(!Array.isArray(i)||"string"!=typeof i[0]||null!=t&&void 0!==t[i[0]]){var s=Ke(e,t,i);switch(o){case"animation":case"animationName":r+=Ye(o)+":"+s+";";break;default:r+=o+"{"+s+"}"}}else for(var l=0;l<i.length;l++)Xe(i[l])&&(r+=Ye(o)+":"+Qe(o,i[l])+";")}return r}(e,t,n);case"function":if(void 0!==e){var a=Ze,o=n(e);return Ze=a,Ke(e,t,o)}}if(null==t)return n;var i=t[n];return void 0!==i?i:n}var Ze,Je=/label:\s*([^\s;\n{]+)\s*(;|$)/g,et=!!a.useInsertionEffect&&a.useInsertionEffect,tt=et||function(e){return e()},nt=(et||a.useLayoutEffect,a.createContext("undefined"!=typeof HTMLElement?De({key:"css"}):null));nt.Provider;var rt=a.createContext({}),at=function(e,t,n){var r=e.key+"-"+t.name;!1===n&&void 0===e.registered[r]&&(e.registered[r]=t.styles)},ot=q,it=function(e){return"theme"!==e},st=function(e){return"string"==typeof e&&e.charCodeAt(0)>96?ot:it},lt=function(e,t,n){var r;if(t){var a=t.shouldForwardProp;r=e.__emotion_forwardProp&&a?function(t){return e.__emotion_forwardProp(t)&&a(t)}:a}return"function"!=typeof r&&n&&(r=e.__emotion_forwardProp),r},ct=function(e){var t=e.cache,n=e.serialized,r=e.isStringTag;return at(t,n,r),tt((function(){return function(e,t,n){at(e,t,n);var r=e.key+"-"+t.name;if(void 0===e.inserted[t.name]){var a=t;do{e.insert(t===a?"."+r:"",a,e.sheet,!0),a=a.next}while(void 0!==a)}}(t,n,r)})),null},ut=function e(t,n){var r,o,i=t.__emotion_real===t,s=i&&t.__emotion_base||t;void 0!==n&&(r=n.label,o=n.target);var l=lt(t,n,i),c=l||st(s),u=!c("as");return function(){var d=arguments,p=i&&void 0!==t.__emotion_styles?t.__emotion_styles.slice(0):[];if(void 0!==r&&p.push("label:"+r+";"),null==d[0]||void 0===d[0].raw)p.push.apply(p,d);else{p.push(d[0][0]);for(var m=d.length,f=1;f<m;f++)p.push(d[f],d[0][f])}var h,g=(h=function(e,t,n){var r,i,d,m,f=u&&e.as||s,h="",g=[],y=e;if(null==e.theme){for(var b in y={},e)y[b]=e[b];y.theme=a.useContext(rt)}"string"==typeof e.className?(r=t.registered,i=g,d=e.className,m="",d.split(" ").forEach((function(e){void 0!==r[e]?i.push(r[e]+";"):m+=e+" "})),h=m):null!=e.className&&(h=e.className+" ");var v=function(e,t,n){if(1===e.length&&"object"==typeof e[0]&&null!==e[0]&&void 0!==e[0].styles)return e[0];var r=!0,a="";Ze=void 0;var o=e[0];null==o||void 0===o.raw?(r=!1,a+=Ke(n,t,o)):a+=o[0];for(var i=1;i<e.length;i++)a+=Ke(n,t,e[i]),r&&(a+=o[i]);Je.lastIndex=0;for(var s,l="";null!==(s=Je.exec(a));)l+="-"+s[1];var c=function(e){for(var t,n=0,r=0,a=e.length;a>=4;++r,a-=4)t=1540483477*(65535&(t=255&e.charCodeAt(r)|(255&e.charCodeAt(++r))<<8|(255&e.charCodeAt(++r))<<16|(255&e.charCodeAt(++r))<<24))+(59797*(t>>>16)<<16),n=1540483477*(65535&(t^=t>>>24))+(59797*(t>>>16)<<16)^1540483477*(65535&n)+(59797*(n>>>16)<<16);switch(a){case 3:n^=(255&e.charCodeAt(r+2))<<16;case 2:n^=(255&e.charCodeAt(r+1))<<8;case 1:n=1540483477*(65535&(n^=255&e.charCodeAt(r)))+(59797*(n>>>16)<<16)}return(((n=1540483477*(65535&(n^=n>>>13))+(59797*(n>>>16)<<16))^n>>>15)>>>0).toString(36)}(a)+l;return{name:c,styles:a,next:Ze}}(p.concat(g),t.registered,y);h+=t.key+"-"+v.name,void 0!==o&&(h+=" "+o);var _=u&&void 0===l?st(f):c,k={};for(var x in e)u&&"as"===x||_(x)&&(k[x]=e[x]);return k.className=h,k.ref=n,a.createElement(a.Fragment,null,a.createElement(ct,{cache:t,serialized:v,isStringTag:"string"==typeof f}),a.createElement(f,k))},(0,a.forwardRef)((function(e,t){var n=(0,a.useContext)(nt);return h(e,n,t)})));return g.displayName=void 0!==r?r:"Styled("+("string"==typeof s?s:s.displayName||s.name||"Component")+")",g.defaultProps=t.defaultProps,g.__emotion_real=g,g.__emotion_base=s,g.__emotion_styles=p,g.__emotion_forwardProp=l,Object.defineProperty(g,"toString",{value:function(){return"."+o}}),g.withComponent=function(t,r){return e(t,F({},n,r,{shouldForwardProp:lt(g,r,!0)})).apply(void 0,p)},g}}.bind();["a","abbr","address","area","article","aside","audio","b","base","bdi","bdo","big","blockquote","body","br","button","canvas","caption","cite","code","col","colgroup","data","datalist","dd","del","details","dfn","dialog","div","dl","dt","em","embed","fieldset","figcaption","figure","footer","form","h1","h2","h3","h4","h5","h6","head","header","hgroup","hr","html","i","iframe","img","input","ins","kbd","keygen","label","legend","li","link","main","map","mark","marquee","menu","menuitem","meta","meter","nav","noscript","object","ol","optgroup","option","output","p","param","picture","pre","progress","q","rp","rt","ruby","s","samp","script","section","select","small","source","span","strong","style","sub","summary","sup","table","tbody","td","textarea","tfoot","th","thead","time","title","tr","track","u","ul","var","video","wbr","circle","clipPath","defs","ellipse","foreignObject","g","image","line","linearGradient","mask","path","pattern","polygon","polyline","radialGradient","rect","stop","svg","text","tspan"].forEach((function(e){ut[e]=ut(e)}));const dt=[0,100],pt=[0,100],mt=e=>`${1===e.length?"0":""}${e}`,ft=(e,t,n)=>Math.max(Math.min(e,n),t),ht=(e,t)=>Math.floor(Math.random()*(t-e+1))+e,gt=(e,t,n)=>{const r=ht(e,t);for(let a=0;a<n?.length;a++){const o=n[a];if(2===o?.length&&r>=o[0]&&r<=o[1])return gt(e,t,n)}return r},yt=(e,t)=>"number"==typeof t?t:e%Math.abs(t[1]-t[0])+t[0],bt=(e,t)=>"number"==typeof e?ft(Math.abs(e),...t):1===e.length||e[0]===e[1]?ft(Math.abs(e[0]),...t):[Math.abs(ft(e[0],...t)),ft(Math.abs(e[1]),...t)],vt=(e,t,n)=>(n<0?n+=1:n>1&&(n-=1),n<1/6?e+6*(t-e)*n:n<.5?t:n<2/3?e+(t-e)*(2/3-n)*6:e),_t=(e,t,n)=>{let r,a,o;if(e/=360,n/=100,0==(t/=100))r=a=o=n;else{const i=n<.5?n*(1+t):n+t-n*t,s=2*n-i;r=vt(s,i,e+1/3),a=vt(s,i,e),o=vt(s,i,e-1/3)}return[Math.round(255*r),Math.round(255*a),Math.round(255*o)]},kt=(e,t,n,r)=>(299*e+587*t+114*n)/1e3>=r,xt=(e,t,n)=>`hsl(${e}, ${t}%, ${n}%)`,wt=(e,t,n,r)=>"rgb"===r?`rgb(${e}, ${t}, ${n})`:`#${mt(e.toString(16))}${mt(t.toString(16))}${mt(n.toString(16))}`,Et=(e,{format:t="hex",saturation:n=[50,55],lightness:r=[50,60],differencePoint:a=130}={})=>{const o=Math.abs((e=>{const t=e.length;let n=0;for(let r=0;r<t;r++)n=(n<<5)-n+e.charCodeAt(r),n&=n;return n})(String(e))),i=yt(o,[0,360]),s=yt(o,bt(n,dt)),l=yt(o,bt(r,pt)),[c,u,d]=_t(i,s,l);return{color:"hsl"===t?xt(i,s,l):wt(c,u,d,t),isLight:kt(c,u,d,a)}};Et.random=({format:e="hex",saturation:t=[50,55],lightness:n=[50,60],differencePoint:r=130,excludeHue:a}={})=>{t=bt(t,dt),n=bt(n,pt);const o=a?gt(0,359,a):ht(0,359),i="number"==typeof t?t:ht(...t),s="number"==typeof n?n:ht(...n),[l,c,u]=_t(o,i,s);return{color:"hsl"===e?xt(o,i,s):wt(l,c,u,e),isLight:kt(l,c,u,r)}};const St=Et,Ct=ut.span`
	white-space: nowrap;
	border-radius: 200px;
	height: 24px;
	line-height: 24px;
	padding: 3px 9px;
	display: inline-block;
`,At=({text:e})=>{const{backgroundColor:t,color:n}=function(e){if(["subscribed","active","yes","true","1"].includes(e))return{backgroundColor:"#78c67a",color:"#111111"};if(["unsubscribed","inactive","no","false","0"].includes(e))return{backgroundColor:"#fbcfbd",color:"#241c15"};if(["pending","waiting","maybe","2"].includes(e))return{backgroundColor:"#fbeeca",color:"#241c15"};const t=St(e,{saturation:[60,100],lightness:[30,45]});return{backgroundColor:t.color,color:t.isLight?"#111111":"#ffffff"}}(e);return(0,a.createElement)(Ct,{style:{backgroundColor:t,color:n}},e)},Ot=({actionUrl:e,buttonText:t,modalTitle:n,modalDescription:r,icon:o,isDestructive:i=!1})=>{const[s,c]=(0,a.useState)(!1),d=n&&r;return(0,a.createElement)(a.Fragment,null,(0,a.createElement)(l.Button,{icon:o,iconSize:16,size:"compact",showTooltip:!0,label:t,type:"button",onClick:d?()=>c(!0):void 0,href:d?void 0:e,variant:"tertiary"}),s&&(0,a.createElement)(l.Modal,{onRequestClose:()=>c(!1),title:n,size:"small"},(0,a.createElement)(l.__experimentalVStack,{spacing:4},(0,a.createElement)(l.__experimentalText,null,r),(0,a.createElement)(l.__experimentalHStack,{spacing:4,justify:"flex-start",alignment:"flex-start"},(0,a.createElement)(l.Button,{variant:"primary",text:t,type:"button",href:e,isDestructive:i}),(0,a.createElement)(l.Button,{variant:"secondary",text:(0,u.__)("Cancel","newsletter-optin-box"),type:"button",onClick:()=>c(!1)})))))},Mt=e=>{let t=e.label;return"future"===e.status&&(t=(0,u.__)("Scheduled")),(0,a.createElement)(l.__experimentalHStack,{alignment:"center",justify:"flex-start",spacing:1},(0,a.createElement)(At,{text:t}),e.action&&(0,a.createElement)(Ot,{...e.action}))};window.noptin=window.noptin||{},window.noptin.viewCampaigns={components:r};const $t=(e,t)=>{if(t){const n=t.getAttribute("data-app"),r=n?JSON.parse(n):{};s.createRoot?(0,s.createRoot)(t).render((0,a.createElement)(e,{...r})):(0,s.render)((0,a.createElement)(e,{...r}),t)}};i()((()=>{$t(R,document.getElementById("noptin-email-campaigns__editor--add-new__in-table")),document.querySelectorAll(".noptin-email-campaigns__editor--add-new__button").forEach((e=>{$t(N,e)})),document.querySelectorAll(".noptin-email-status__app").forEach((e=>{$t(Mt,e)}))}))},184:(e,t)=>{var n;!function(){"use strict";var r={}.hasOwnProperty;function a(){for(var e=[],t=0;t<arguments.length;t++){var n=arguments[t];if(n){var o=typeof n;if("string"===o||"number"===o)e.push(n);else if(Array.isArray(n)){if(n.length){var i=a.apply(null,n);i&&e.push(i)}}else if("object"===o){if(n.toString!==Object.prototype.toString&&!n.toString.toString().includes("[native code]")){e.push(n.toString());continue}for(var s in n)r.call(n,s)&&n[s]&&e.push(s)}}}return e.join(" ")}e.exports?(a.default=a,e.exports=a):void 0===(n=function(){return a}.apply(t,[]))||(e.exports=n)}()}},n={};function r(e){var a=n[e];if(void 0!==a)return a.exports;var o=n[e]={exports:{}};return t[e](o,o.exports,r),o.exports}r.m=t,e=[],r.O=(t,n,a,o)=>{if(!n){var i=1/0;for(u=0;u<e.length;u++){for(var[n,a,o]=e[u],s=!0,l=0;l<n.length;l++)(!1&o||i>=o)&&Object.keys(r.O).every((e=>r.O[e](n[l])))?n.splice(l--,1):(s=!1,o<i&&(i=o));if(s){e.splice(u--,1);var c=a();void 0!==c&&(t=c)}}return t}o=o||0;for(var u=e.length;u>0&&e[u-1][2]>o;u--)e[u]=e[u-1];e[u]=[n,a,o]},r.n=e=>{var t=e&&e.__esModule?()=>e.default:()=>e;return r.d(t,{a:t}),t},r.d=(e,t)=>{for(var n in t)r.o(t,n)&&!r.o(e,n)&&Object.defineProperty(e,n,{enumerable:!0,get:t[n]})},r.o=(e,t)=>Object.prototype.hasOwnProperty.call(e,t),r.r=e=>{"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},(()=>{var e={581:0,732:0};r.O.j=t=>0===e[t];var t=(t,n)=>{var a,o,[i,s,l]=n,c=0;if(i.some((t=>0!==e[t]))){for(a in s)r.o(s,a)&&(r.m[a]=s[a]);if(l)var u=l(r)}for(t&&t(n);c<i.length;c++)o=i[c],r.o(e,o)&&e[o]&&e[o][0](),e[o]=0;return r.O(u)},n=globalThis.webpackChunknoptin_premium=globalThis.webpackChunknoptin_premium||[];n.forEach(t.bind(null,0)),n.push=t.bind(null,n.push.bind(n))})();var a=r.O(void 0,[732],(()=>r(567)));a=r.O(a)})();