(()=>{"use strict";var t,e={585:()=>{const t={noptin_nonce:window.noptinParams?.nonce||"",conversion_page:window.location.href,action:"noptin_process_ajax_subscriber",noptin_process_request:"1",noptin_timestamp:Math.floor(Date.now()/1e3),noptin_submitted:Math.floor(Date.now()/1e3)},e=e=>{Object.entries(t).forEach((([t,n])=>{const o=document.createElement("input");o.type="hidden",o.name=t,o.value=n,e.appendChild(o)}));const n=e.querySelector(".noptin_feedback_error"),o=e.querySelector(".noptin-response");if(n&&!o){const t=document.createElement("div");t.classList.add("noptin-response","noptin-form-notice"),t.setAttribute("role","alert"),n.after(t)}const r=document.createElement("input");r.type="text",r.id=`noptin_${Math.random().toString(36).substr(2,9)}`,r.name="noptin_ign",r.autocomplete="off",r.style.position="absolute",r.style.left="-9999px",r.style.top="-9999px",r.style.opacity="0",r.style.height="0",r.style.width="0",r.style.zIndex="-1",r.setAttribute("aria-hidden","true"),r.tabIndex=-1;const i=document.createElement("label");i.htmlFor=r.id,i.textContent="Leave this field empty",i.style.display="none",e.appendChild(i),e.appendChild(r);const s=e.querySelector(".noptin_form_input_email");s&&s.setAttribute("name","noptin_fields[email]"),e.addEventListener("submit",(function(t){t.preventDefault(),e.classList.add("noptin-submitting"),e.classList.remove("noptin-form-submitted","noptin-has-error","noptin-has-success");const n=e.querySelector(".noptin-response");n&&(n.innerHTML=""),e.querySelector('input[name="noptin_submitted"]')?.setAttribute("value",Math.floor(Date.now()/1e3).toString());try{window.fetch(window.noptinParams?.resturl||"/wp-json/noptin/v1/form",{method:"POST",body:new FormData(e,t?.submitter),credentials:"same-origin",headers:{Accept:"application/json"}}).then((t=>{if(t.status>=200&&t.status<300)return t;throw t})).then((t=>t.json())).then((t=>{if(t){if(!1===t.success)e.classList.add("noptin-has-error"),n&&(n.innerHTML=t.data);else{if(!0!==t.success)return void e.submit();{window.NOPTIN_SUBSCRIBED=!0;try{"function"==typeof window.gtag&&window.gtag("event","subscribe",{method:"Noptin Form"})}catch(t){console.error(t.message)}const o=e.querySelector(".noptin_form_redirect");if(o&&o.getAttribute("value"))return void(window.location.href=o.getAttribute("value"));"redirect"===t.data.action&&(window.location.href=t.data.redirect_url),t.data.msg&&(e.classList.add("noptin-has-success"),n&&(n.innerHTML=t.data.msg))}}e.classList.add("noptin-form-submitted"),e.classList.remove("noptin-submitting")}else e.submit()})).catch((t=>e.submit()))}catch(t){console.log(t),this.submit()}}))};var n;n=()=>{window.FormData?window.noptinParams?.resturl?document.querySelectorAll("form.noptin-newsletter-form, form.noptin-optin-form, .noptin-optin-form-wrapper form, .wp-block-noptin-email-optin form, .noptin-email-optin-widget form").forEach(e):console.error("noptinParams.resturl is not defined."):console.error("FormData is not supported.")},"loading"===document.readyState?document.addEventListener("DOMContentLoaded",n):n()}},n={};function o(t){var r=n[t];if(void 0!==r)return r.exports;var i=n[t]={exports:{}};return e[t](i,i.exports,o),i.exports}o.m=e,t=[],o.O=(e,n,r,i)=>{if(!n){var s=1/0;for(l=0;l<t.length;l++){n=t[l][0],r=t[l][1],i=t[l][2];for(var a=!0,p=0;p<n.length;p++)(!1&i||s>=i)&&Object.keys(o.O).every((t=>o.O[t](n[p])))?n.splice(p--,1):(a=!1,i<s&&(s=i));if(a){t.splice(l--,1);var c=r();void 0!==c&&(e=c)}}return e}i=i||0;for(var l=t.length;l>0&&t[l-1][2]>i;l--)t[l]=t[l-1];t[l]=[n,r,i]},o.o=(t,e)=>Object.prototype.hasOwnProperty.call(t,e),(()=>{var t={472:0,364:0};o.O.j=e=>0===t[e];var e=(e,n)=>{var r,i,s=n[0],a=n[1],p=n[2],c=0;if(s.some((e=>0!==t[e]))){for(r in a)o.o(a,r)&&(o.m[r]=a[r]);if(p)var l=p(o)}for(e&&e(n);c<s.length;c++)i=s[c],o.o(t,i)&&t[i]&&t[i][0](),t[i]=0;return o.O(l)},n=self.webpackChunknoptin_premium=self.webpackChunknoptin_premium||[];n.forEach(e.bind(null,0)),n.push=e.bind(null,n.push.bind(n))})();var r=o.O(void 0,[364],(()=>o(585)));r=o.O(r)})();