(()=>{var e,t={93:(e,t,r)=>{"use strict";const n=window.wp.domReady;var a=r.n(n);const o=window.React,l=window.wp.i18n,i=window.noptinEmailSettingsMisc||{},s=(i.license,window.wp.url),c=window.wp.components,u=window.noptinEmailEditorSettings||{},m=window.noptinEmailSettingsMisc||{},p=(m.license,u.types||{}),d=(Object.keys(p).map((e=>({value:e,label:p[e].label}))),Object.keys(u.templates||{}).map((e=>({value:e,label:u.templates[e]}))),m.license||{}),_=(d.is_usable&&d.key,/_published|_unpublished|_deleted|_user_role$/),g=["noptin_subscriber_","delete_user","new_user","update_user","wp_login","after_password_reset","create_or_update_"],y=[["woocommerce","subscription"],["latest_","_digest"]],b=["WordPress Users"],f=["latest_posts_digest"],h=["periodic"],E=(e,t=void 0)=>{if(!e||f.includes(e))return!1;if(h.includes(e))return!0;if(t&&b.includes(t))return!0;if(y.some((t=>t.every((t=>e.includes(t))))))return!0;const r=e.replace(/^automation_rule_/,"");return _.test(r)?"post_published"!==r:g.some((e=>r.startsWith(e)))};function w(e,t){const r={};Array.isArray(e)||Object.entries(e).forEach((([e,n])=>{if("email"!==e){if(!n.category){if(!i.isTest)return;n.category="Deprecated"}r[n.category]||(r[n.category]={}),r[n.category][e]={...n,selectText:"triggers"===t?(0,l.__)("Use trigger","newsletter-optin-box"):(0,l.__)("Set-up","newsletter-optin-box"),forcePremium:E(e,n.category)},n.alt_category&&(r[n.alt_category]||(r[n.alt_category]={}),r[n.alt_category][e]=r[n.category][e])}})),Array.isArray(i.integrations)&&i.integrations.forEach((n=>{n.plan&&"free"!==n.plan&&n[t]&&!Array.isArray(n[t])&&Object.entries(n[t]).forEach((([t,a])=>{a.forEach((({id:a,label:o,description:l,premium:i=!1})=>{if("premium"===n.plan||i)if(e[a]){const t=e[a].category;t&&r[t][a]&&(r[t][a].forcePremium=!0)}else r[t]||(r[t]={}),r[t][a]={name:a,label:o,description:l,category:t,image:n.icon_url,forcePremium:!0,is_installed:!1,installation:n.installation}}))}))}));const n={},a=(0,l.__)("General","newsletter-optin-box");return r.hasOwnProperty(a)&&(n[a]=r[a]),Object.keys(r).sort().forEach((e=>{e!==a&&(n[e]=r[e])})),n}const v={"noptin-trigger":{title:(0,l.__)("Select a trigger for your automation rule","newsletter-optin-box"),show:!0,arg:"noptin-trigger",cardGroups:w(i.data?.triggers||{},"triggers")},"noptin-action":{title:(0,l.__)("Select an action for your automation rule","newsletter-optin-box"),show:!0,arg:"noptin-action",cardGroups:w(i.data?.actions||{},"actions")}},x=(0,o.createContext)(void 0),k=({children:e})=>{const[t,r]=(0,o.useState)(i.data?.add_new||(0,s.addQueryArgs)(window.location.href,{noptin_edit_automation_rule:"0"})),n=(0,o.useMemo)((()=>{let e="";const n={};for(const[r,a]of Object.entries(v)){const o=a.arg?(0,s.getQueryArg)(t,a.arg):"";if(!o){e=r;break}n[r]=o}return{currentTitle:v[e]?.title||"",currentStep:e,isLastStep:e===Object.keys(v).pop(),isFirstStep:e===Object.keys(v)[0],hasSteps:Object.keys(v).length>0,stepValues:n,steps:v,campaign:"automation-rules",removeQueryArgs:(...e)=>{r((0,s.removeQueryArgs)(t,...e))},addQueryArg:(e,n)=>{r((0,s.addQueryArgs)(t,{[e]:n}))},withQueryArg:(e,r)=>(0,s.addQueryArgs)(t,{[e]:r}),url:t}}),[t,r]);return(0,o.createElement)(x.Provider,{value:n},e)};var S=r(942),C=r.n(S);const O=window.wp.element,A=({categories:e,selectedCategory:t,onClickCategory:r})=>{const n="noptin-campaign-explorer__sidebar";return(0,o.createElement)("div",{className:n},(0,o.createElement)("div",{className:`${n}__categories-list`},e.map((e=>(0,o.createElement)(c.Button,{key:e,label:e,className:`${n}__categories-list__item`,isPressed:t===e,onClick:()=>{r(e)}},e)))))},j=({image:e,title:t})=>{if("string"==typeof e&&e.startsWith("http"))return(0,o.createElement)("img",{src:e,width:24,alt:t,style:{maxWidth:24}});if("string"==typeof e)return(0,o.createElement)(c.Icon,{size:24,icon:e,style:{color:"#424242"}});if(e&&"object"==typeof e){const t=e.fill||"#008000",r=e.path||"",n=e.viewBox||"0 0 24 24";return e.path?(0,o.createElement)(c.SVG,{viewBox:n,xmlns:"http://www.w3.org/2000/svg",style:{maxWidth:24}},(0,o.createElement)(c.Path,{fill:t,d:r})):(0,o.createElement)(c.Icon,{size:24,style:{color:t},icon:e.icon})}return(0,o.createElement)(c.Icon,{size:24,icon:"email",style:{color:"#424242"}})},T=({name:e,label:t,description:r,image:n,onSelect:a,hrefCallback:i,href:u,...m})=>{const p=(e=>{const t=e.learnMoreUrl?(0,o.createElement)(c.Button,{variant:"secondary",href:e.learnMoreUrl},(0,o.createElement)("span",{className:"noptin-selectable-card-action__label"},(0,l.__)("Learn More"))," ",(0,o.createElement)(c.Icon,{size:16,icon:"arrow-right-alt"})):null;if(e.forcePremium?!1!==e.is_installed&&e?.licenseDetails?.key:!1!==e.is_installed)return{upgradeText:null,button:(0,o.createElement)(c.Button,{variant:"primary",onClick:e.onClick,href:e.href},(0,o.createElement)("span",{className:"noptin-selectable-card__label"},e.selectText||(0,l.__)("Select"))," ",(0,o.createElement)(c.Icon,{size:16,icon:"arrow-right-alt"})),secondaryButton:t};const r=(t,r)=>!1===e.is_installed&&e.installation?.[t]?e.installation?.[t]:e.licenseDetails[t]||r,n=r("install_desc",(0,l.__)("Activate your license key to unlock","newsletter-optin-box")),a=r("install_text",(0,l.__)("View Pricing","newsletter-optin-box")),i=r("install_url",(0,s.addQueryArgs)("https://noptin.com/pricing/",{utm_source:e.name||"license",utm_campaign:e.campaign||"noptin",utm_medium:"plugin-dashboard"}));return{upgradeText:n,button:(0,o.createElement)(c.Button,{variant:"primary",href:i},(0,o.createElement)("span",{className:"noptin-selectable-card-action__label"},a)," ",(0,o.createElement)(c.Icon,{size:16,icon:"lock"})),secondaryButton:e.licenseDetails?.key?t:(0,o.createElement)(c.Button,{variant:"secondary",href:e.licenseDetails?.activate_url},(0,o.createElement)("span",{className:"noptin-selectable-card-action__label"},(0,l.__)("Activate"))," ",(0,o.createElement)(c.Icon,{size:16,icon:"unlock"}))}})({onClick:(0,o.useCallback)((()=>a?a(e):null),[e,a]),name:e.replace("automation_rule_",""),href:i?i(e):u,...m});return(0,o.createElement)(c.Card,{className:`noptin-selectable-card noptin-selectable-card__${e}`,size:"small"},(0,o.createElement)(c.CardHeader,null,(0,o.createElement)(c.__experimentalHeading,{level:4,numberOfLines:1},t),(0,o.createElement)(j,{image:n,title:t})),(0,o.createElement)(c.CardBody,null,(0,o.createElement)(c.__experimentalVStack,{spacing:4},r&&(0,o.createElement)(c.__experimentalText,{as:"p",variant:"muted"},r),p.upgradeText&&(0,o.createElement)(c.__experimentalText,{as:"em",isDestructive:!0},p.upgradeText))),(0,o.createElement)(c.CardFooter,{isBorderless:!0,justify:p.secondaryButton?"space-between":"flex-end"},p.secondaryButton,p.button),(0,o.createElement)(c.__experimentalElevation,{value:1,hover:3}))},B=({showTitle:e,selectedCategory:t,cards:r,...n})=>{const a="noptin-campaign-explorer";let l=Object.entries(r);return l.sort(((e,t)=>e[1].label.localeCompare(t[1].label))),(0,o.createElement)("div",{className:`${a}__list`},e&&(0,o.createElement)(c.__experimentalHeading,{level:2,lineHeight:"48px",className:`${a}__category-name`},t),(0,o.createElement)("div",{role:"listbox",className:`${a}-list`},l.map((([e,t])=>(0,o.createElement)(T,{key:e,name:e,...n,...t})))))},M=({cardGroups:e,...t})=>{const r=Object.keys(e).length,[n,a]=(0,o.useState)(Object.keys(e)[0]);(0,o.useEffect)((()=>{r>0&&!e[n]&&a(Object.keys(e)[0])}),[e,n]);const l=r>1,i=(0,o.useMemo)((()=>Object.keys(e)),[e]),s=(0,o.useMemo)((()=>e[n]||{}),[e,n]),c=C()("noptin-campaign-explorer",{"noptin-campaign-explorer--show-sidebar":l});return(0,o.createElement)("div",{className:c},l&&(0,o.createElement)(A,{selectedCategory:n,categories:i,onClickCategory:a}),(0,o.createElement)(B,{showTitle:l,selectedCategory:n,cards:s,...t}))},P=e=>{if(!e.isOpen)return null;const t=e.steps[e.currentStep];if(t.modal)return(0,o.createElement)(c.Modal,{onRequestClose:e.closeModal,...t.modal.props},t.modal.content);if(t.cardGroups){const r=[...Object.keys(e.stepValues)].pop(),n=(0,o.createElement)(o.Fragment,null,r&&!e.isFirstStep&&(0,o.createElement)(c.Button,{icon:"arrow-left-alt",onClick:()=>e.removeQueryArgs(e.steps[r]?.arg||""),label:(0,l.__)("Back","newsletter-optin-box"),showTooltip:!0})),a=e.isLastStep?void 0:r=>{t.arg&&e.addQueryArg(t.arg,r)};return(0,o.createElement)(c.Modal,{title:e.currentTitle,onRequestClose:e.closeModal,headerActions:n,isFullScreen:!0},(0,o.createElement)(M,{cardGroups:t.cardGroups,licenseDetails:e.licenseDetails,onSelect:a,hrefCallback:t.arg&&e.isLastStep?r=>e.withQueryArg(t.arg,r):void 0,campaign:e.campaign}))}return null},I=({buttonProps:e,...t})=>{const[r,n]=(0,o.useState)(!1),a=(0,o.useCallback)((()=>{n(!0)}),[n]),l=(0,o.useCallback)((()=>{n(!1)}),[n]),i=t.currentStep&&t.hasSteps,s={...e,onClick:i?a:void 0,href:i?void 0:t.url};return(0,o.createElement)(o.Fragment,null,(0,o.createElement)(c.Button,{...s}),(0,o.createElement)(P,{isOpen:r,closeModal:l,...t}))},N=({text:e})=>{const t=(()=>{const e=(0,o.useContext)(x);if(!e)throw new Error("useModal must be used within a ModalProvider");return e})();return(0,o.createElement)(I,{licenseDetails:i.license||{},buttonProps:{variant:"primary",type:"button",text:e||(0,l.__)("Add New Automation","newsletter-optin-box")},...t})},D=({text:e})=>(0,o.createElement)(k,null,(0,o.createElement)(N,{text:e})),z=()=>(0,o.createElement)(c.__experimentalVStack,{alignment:"center",justify:"center",spacing:6,style:{minHeight:320}},(0,o.createElement)(c.Icon,{icon:"admin-generic",size:100,style:{color:"#646970"}}),(0,o.createElement)(c.__experimentalText,{align:"center",color:"#646970",size:16,isBlock:!0},(0,l.__)('Automation rules are simple "if this, then that" commands. Trigger an action when a product is purchased, a user creates an account, someone is tagged, etc.',"newsletter-optin-box")),(0,o.createElement)(D,{text:(0,l.__)("Create your first automation rule","newsletter-optin-box")}),(0,o.createElement)(c.__experimentalText,{align:"center",size:14,isBlock:!0},(0,o.createElement)("a",{href:"https://noptin.com/guide/automation-rules/",style:{color:"#646970"},target:"_blank"},(0,l.__)("Or Learn more","newsletter-optin-box")))),Q=window.wp.apiFetch;var $=r.n(Q);const H=({ruleId:e})=>{const[t,r]=(0,o.useState)(!1);return(0,o.createElement)(o.Fragment,null,(0,o.createElement)(c.Button,{icon:"trash",size:"compact",showTooltip:!0,label:(0,l.__)("Delete","newsletter-optin-box"),type:"button",onClick:()=>r(!0),isDestructive:!0}),t&&(0,o.createElement)(c.Modal,{onRequestClose:()=>r(!1),title:(0,l.__)("Delete Rule","newsletter-optin-box"),size:"small"},(0,o.createElement)(c.__experimentalVStack,{spacing:4},(0,o.createElement)(c.__experimentalText,null,(0,l.__)("Are you sure you want to delete this automation rule?","newsletter-optin-box")),(0,o.createElement)(c.__experimentalHStack,{spacing:4,justify:"flex-start",alignment:"flex-start"},(0,o.createElement)(c.Button,{variant:"primary",text:(0,l.__)("Delete","newsletter-optin-box"),type:"button",onClick:()=>{r(!1);const t=document.querySelector(`.noptin_automation_rule_${e}`);t&&t.classList.add("noptin-fade-out"),$()({path:`/noptin/v1/automation_rules/${e}`,method:"DELETE"}).then((e=>(t&&t.remove(),e))).catch((e=>{alert(e.message),t&&t.classList.remove("noptin-fade-out")}))},isDestructive:!0}),(0,o.createElement)(c.Button,{variant:"secondary",text:(0,l.__)("Cancel","newsletter-optin-box"),type:"button",onClick:()=>r(!1)})))))},L=({ruleId:e,status:t})=>{const[r,n]=(0,o.useState)(t);return(0,o.createElement)(c.ToggleControl,{checked:r,label:r?(0,l.__)("Active","newsletter-optin-box"):(0,l.__)("Inactive","newsletter-optin-box"),className:"noptin-toggle-button",onChange:()=>{n(!r),$()({path:`/noptin/v1/automation_rules/${e}`,method:"PATCH",data:{status:!r}}).catch((e=>{alert(e.message),n(r)}))},__nextHasNoMarginBottom:!0})},F=e=>(0,o.createElement)(c.__experimentalHStack,{alignment:"center",justify:"flex-end",spacing:1},(0,o.createElement)(c.Button,{href:e.editUrl,label:(0,l.__)("Edit","newsletter-optin-box"),size:"compact",icon:"edit",showTooltip:!0}),(0,o.createElement)(H,{ruleId:e.ruleId}),(0,o.createElement)(L,{ruleId:e.ruleId,status:e.status})),G=(e,t)=>{if(t){const r=t.getAttribute("data-app"),n=r?JSON.parse(r):{};O.createRoot?(0,O.createRoot)(t).render((0,o.createElement)(e,{...n})):(0,O.render)((0,o.createElement)(e,{...n}),t)}};a()((()=>{G(z,document.getElementById("noptin-automation-rules__editor--add-new__in-table")),document.querySelectorAll(".noptin-automation-rules__editor--add-new__button").forEach((e=>{G(D,e)})),document.querySelectorAll(".noptin-automation-rule-actions__app").forEach((e=>{G(F,e)}))}))},942:(e,t)=>{var r;!function(){"use strict";var n={}.hasOwnProperty;function a(){for(var e="",t=0;t<arguments.length;t++){var r=arguments[t];r&&(e=l(e,o(r)))}return e}function o(e){if("string"==typeof e||"number"==typeof e)return e;if("object"!=typeof e)return"";if(Array.isArray(e))return a.apply(null,e);if(e.toString!==Object.prototype.toString&&!e.toString.toString().includes("[native code]"))return e.toString();var t="";for(var r in e)n.call(e,r)&&e[r]&&(t=l(t,r));return t}function l(e,t){return t?e?e+" "+t:e+t:e}e.exports?(a.default=a,e.exports=a):void 0===(r=function(){return a}.apply(t,[]))||(e.exports=r)}()}},r={};function n(e){var a=r[e];if(void 0!==a)return a.exports;var o=r[e]={exports:{}};return t[e](o,o.exports,n),o.exports}n.m=t,e=[],n.O=(t,r,a,o)=>{if(!r){var l=1/0;for(u=0;u<e.length;u++){for(var[r,a,o]=e[u],i=!0,s=0;s<r.length;s++)(!1&o||l>=o)&&Object.keys(n.O).every((e=>n.O[e](r[s])))?r.splice(s--,1):(i=!1,o<l&&(l=o));if(i){e.splice(u--,1);var c=a();void 0!==c&&(t=c)}}return t}o=o||0;for(var u=e.length;u>0&&e[u-1][2]>o;u--)e[u]=e[u-1];e[u]=[r,a,o]},n.n=e=>{var t=e&&e.__esModule?()=>e.default:()=>e;return n.d(t,{a:t}),t},n.d=(e,t)=>{for(var r in t)n.o(t,r)&&!n.o(e,r)&&Object.defineProperty(e,r,{enumerable:!0,get:t[r]})},n.o=(e,t)=>Object.prototype.hasOwnProperty.call(e,t),(()=>{var e={229:0,833:0};n.O.j=t=>0===e[t];var t=(t,r)=>{var a,o,[l,i,s]=r,c=0;if(l.some((t=>0!==e[t]))){for(a in i)n.o(i,a)&&(n.m[a]=i[a]);if(s)var u=s(n)}for(t&&t(r);c<l.length;c++)o=l[c],n.o(e,o)&&e[o]&&e[o][0](),e[o]=0;return n.O(u)},r=globalThis.webpackChunknoptin_premium=globalThis.webpackChunknoptin_premium||[];r.forEach(t.bind(null,0)),r.push=t.bind(null,r.push.bind(r))})();var a=n.O(void 0,[833],(()=>n(93)));a=n.O(a)})();