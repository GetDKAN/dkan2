(this.webpackJsonpapp=this.webpackJsonpapp||[]).push([[0],{255:function(e,t,n){e.exports=n(580)},560:function(e,t,n){},580:function(e,t,n){"use strict";n.r(t);var a=n(0),s=n.n(a),r=n(252),i=n.n(r),o=n(77),c=n.n(o),u=n(159),d=n(58),l=n(43),m=n(157),p=n.n(m),f=(n(549),n(160)),h=n.n(f),b=(n(560),n(561));var w=function(e){var t=e.tempUUID,n=Object(l.d)(),r=Object(a.useState)(""),i=Object(d.a)(r,2),o=i[0],m=i[1],w=Object(a.useState)(""),v=Object(d.a)(w,2),g=v[0],E=v[1],O=Object(a.useState)({}),j=Object(d.a)(O,2),S=j[0],y=j[1],D=Object(a.useState)({}),k=Object(d.a)(D,2),U=k[0],x=k[1],I=Object(a.useState)({}),N=Object(d.a)(I,2),T=N[0],C=N[1];function L(e){var a=function(e){var n={};return Object.keys(e).forEach((function(t){isNaN(t)&&(n[t]=e[t])})),e.identifier||(n.identifier=t.toString()),n}(e.formData);o.length>0?b.put("/api/1/metastore/schemas/dataset/items/"+o,a).then((function(){E("The dataset with identifier "+o+" has been updated.")})).catch((function(e){e.response&&E(e.response.data.message)})):b.post("/api/1/metastore/schemas/dataset/items",a).then((function(e){var t=e.data.identifier,a=new URLSearchParams(window.location.search);a.set("id",t),n.push(window.location.pathname+"?"+a.toString()),m(t),E("A dataset with the identifier "+t+" has been created.")})).catch((function(e){e.response&&E(e.response.data.message)})),window.scrollTo(0,0)}function Y(){var e=new URLSearchParams(window.location.search).getAll("id");return e.length>0?e[0]:null}Object(a.useEffect)((function(){function e(){return(e=Object(u.a)(c.a.mark((function e(){var t,n;return c.a.wrap((function(e){for(;;)switch(e.prev=e.next){case 0:return e.next=2,b.get("/api/1/metastore/schemas/dataset").then((function(e){var t=e.data;t.required=t.required.filter((function(e){return"identifier"!==e})),delete t.properties.identifier.minLength,y(t)}));case 2:return e.sent,e.next=5,b.get("/api/1/metastore/schemas/dataset.ui");case 5:t=e.sent,x(t.data),(n=Y())&&m(n);case 9:case"end":return e.stop()}}),e)})))).apply(this,arguments)}!function(){e.apply(this,arguments)}()}),[]),Object(a.useEffect)((function(){function e(){return(e=Object(u.a)(c.a.mark((function e(){var t;return c.a.wrap((function(e){for(;;)switch(e.prev=e.next){case 0:return e.next=2,b.get("/api/1/metastore/schemas/dataset/items/"+o);case 2:t=e.sent,C(t.data);case 4:case"end":return e.stop()}}),e)})))).apply(this,arguments)}!function(){e.apply(this,arguments)}()}),[o]),Object(a.useEffect)((function(){g.length>0&&f.toast.success(g)}),[g]);var B={DescriptionField:function(e){var t=e.id,n=e.description;return s.a.createElement("div",{className:"dc-field-label",id:t,dangerouslySetInnerHTML:{__html:n}})}};return s.a.createElement(s.a.Fragment,null,s.a.createElement(h.a,{timerExpires:1e4,position:"top-left",pauseOnHover:!0,intent:"success"}),s.a.createElement("button",{className:"btn btn-default",type:"button",onClick:function(e){return window.location.href="/admin/content/datasets"}},"Back to Datasets"),s.a.createElement(p.a,{id:"dc-json-editor",schema:S,fields:B,formData:T,uiSchema:U,autoComplete:"on",transformErrors:function(e){return e.map((function(e){return"pattern"===e.name&&".contactPoint.hasEmail"===e.property&&(e.message="Enter a valid email address."),"pattern"===e.name&&e.property.includes(".distribution")&&e.property.includes(".isssued")&&(e.message="Dates should be ISO 8601 of least resolution. In other words, as much of YYYY-MM-DDThh:mm:ss.sTZD as is relevant to this dataset."),e}))},onSubmit:function(e){E(""),L(e)},onError:function(e){window.scrollTo(0,0),console.error(e)}},s.a.createElement("div",{className:"dc-form-actions"},s.a.createElement("button",{className:"btn btn-success",type:"submit"},"Submit"),s.a.createElement("button",{className:"btn btn-default",type:"button",onClick:function(e){return window.location.href="/admin/content/datasets"}},"Cancel"))))};Boolean("localhost"===window.location.hostname||"[::1]"===window.location.hostname||window.location.hostname.match(/^127(?:\.(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)){3}$/));var v=n(158),g=window.drupalSettings.tempUUID;i.a.render(s.a.createElement(v.a,null,s.a.createElement(w,{tempUUID:g})),document.getElementById("app")),"serviceWorker"in navigator&&navigator.serviceWorker.ready.then((function(e){e.unregister()}))}},[[255,1,2]]]);
//# sourceMappingURL=main.fd3563e3.chunk.js.map