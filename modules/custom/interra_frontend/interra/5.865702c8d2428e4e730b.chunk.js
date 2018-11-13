webpackJsonp([5],{"./app/components/Breadcrumb/A.js":function(e,t,n){"use strict";var o=n("./node_modules/styled-components/dist/styled-components.es.js"),s=n("./app/components/Breadcrumb/buttonStyles.js");o.a.a.withConfig({displayName:"A__A"})(["",""],s.a)},"./app/components/Breadcrumb/StyledButton.js":function(e,t,n){"use strict";var o=n("./node_modules/styled-components/dist/styled-components.es.js"),s=n("./app/components/Breadcrumb/buttonStyles.js");o.a.button.withConfig({displayName:"StyledButton__StyledButton"})(["",""],s.a)},"./app/components/Breadcrumb/Wrapper.js":function(e,t,n){"use strict";var o=n("./node_modules/styled-components/dist/styled-components.es.js");o.a.div.withConfig({displayName:"Wrapper__Wrapper"})(["width: 100%;text-align: center;margin: 4em 0;"])},"./app/components/Breadcrumb/buttonStyles.js":function(e,t,n){"use strict";var o=n("./node_modules/styled-components/dist/styled-components.es.js"),s=n.i(o.b)(["display: inline-block;box-sizing: border-box;padding: 0.25em 2em;text-decoration: none;border-radius: 4px;-webkit-font-smoothing: antialiased;-webkit-touch-callout: none;user-select: none;cursor: pointer;outline: 0;font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;font-weight: bold;font-size: 16px;border: 2px solid #41addd;color: #41addd;&:active {background: #41addd;color: #fff;}"]);t.a=s},"./app/components/Breadcrumb/index.js":function(e,t,n){"use strict";function o(e){var t=l;return!e.loading&&e.breadcrumbs&&(t=e.breadcrumbs.map(function(t,n){var o="";o=t.icon?r("span",{className:"glyphicon glyphicon-"+t.icon}):""+t.title;var s="";return s=n===e.breadcrumbs.length-1?o:r(i.Link,{to:t.loc},void 0,o),r("li",{},t.title,s)})),r("ul",{className:"breadcrumb",style:{backgroundColor:"inherit"}},void 0,t)}var s=n("./node_modules/react/react.js"),a=(n.n(s),n("./node_modules/prop-types/index.js")),i=(n.n(a),n("./node_modules/react-router-dom/index.js")),r=(n.n(i),n("./app/components/Breadcrumb/A.js"),n("./app/components/Breadcrumb/StyledButton.js"),n("./app/components/Breadcrumb/Wrapper.js"),function(){var e="function"==typeof Symbol&&Symbol.for&&Symbol.for("react.element")||60103;return function(t,n,o,s){var a=t&&t.defaultProps,i=arguments.length-3;if(n||0===i||(n={}),n&&a)for(var r in a)void 0===n[r]&&(n[r]=a[r]);else n||(n=a||{});if(1===i)n.children=s;else if(i>1){for(var l=Array(i),c=0;c<i;c++)l[c]=arguments[c+3];n.children=l}return{$$typeof:e,type:t,key:void 0===o?null:""+o,ref:null,props:n,_owner:null}}}()),l=r("li",{});t.a=o},"./app/components/H1/index.js":function(e,t,n){"use strict";var o=n("./node_modules/styled-components/dist/styled-components.es.js"),s=o.a.h1.withConfig({displayName:"H1__H1"})(["font-size: 2.2em;margin-bottom: 20px;margin-top: 10px;"]);t.a=s},"./app/components/PageContainer/index.js":function(e,t,n){"use strict";var o=n("./node_modules/styled-components/dist/styled-components.es.js"),s=o.a.div.withConfig({displayName:"PageContainer__PageContainer"})(["padding: 20px 30px;font-size: 1.25em;"]);t.a=s},"./app/containers/FeaturePage/List.js":function(e,t,n){"use strict";var o=n("./node_modules/styled-components/dist/styled-components.es.js");o.a.ul.withConfig({displayName:"List__List"})(["font-family: Georgia, Times, 'Times New Roman', serif;padding-left: 1.75em;"])},"./app/containers/FeaturePage/ListItem.js":function(e,t,n){"use strict";var o=n("./node_modules/styled-components/dist/styled-components.es.js");o.a.li.withConfig({displayName:"ListItem__ListItem"})(["margin: 1em 0;"])},"./app/containers/FeaturePage/ListItemTitle.js":function(e,t,n){"use strict";var o=n("./node_modules/styled-components/dist/styled-components.es.js");o.a.p.withConfig({displayName:"ListItemTitle__ListItemTitle"})(["font-weight: bold;"])},"./app/containers/FeaturePage/index.js":function(e,t,n){"use strict";function o(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function s(e,t){if(!e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called");return!t||"object"!=typeof t&&"function"!=typeof t?e:t}function a(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function, not "+typeof t);e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,enumerable:!1,writable:!0,configurable:!0}}),t&&(Object.setPrototypeOf?Object.setPrototypeOf(e,t):e.__proto__=t)}Object.defineProperty(t,"__esModule",{value:!0});var i=n("./node_modules/react/react.js"),r=n.n(i),l=n("./node_modules/react-helmet/lib/Helmet.js"),c=(n.n(l),n("./node_modules/react-intl/lib/index.es.js")),d=n("./app/components/H1/index.js"),p=n("./app/containers/FeaturePage/messages.js"),u=(n("./app/containers/FeaturePage/List.js"),n("./app/containers/FeaturePage/ListItem.js"),n("./app/containers/FeaturePage/ListItemTitle.js"),n("./app/components/Breadcrumb/index.js")),m=n("./app/components/PageContainer/index.js"),f=function(){var e="function"==typeof Symbol&&Symbol.for&&Symbol.for("react.element")||60103;return function(t,n,o,s){var a=t&&t.defaultProps,i=arguments.length-3;if(n||0===i||(n={}),n&&a)for(var r in a)void 0===n[r]&&(n[r]=a[r]);else n||(n=a||{});if(1===i)n.children=s;else if(i>1){for(var l=Array(i),c=0;c<i;c++)l[c]=arguments[c+3];n.children=l}return{$$typeof:e,type:t,key:void 0===o?null:""+o,ref:null,props:n,_owner:null}}}(),y=function(){function e(e,t){for(var n=0;n<t.length;n++){var o=t[n];o.enumerable=o.enumerable||!1,o.configurable=!0,"value"in o&&(o.writable=!0),Object.defineProperty(e,o.key,o)}}return function(t,n,o){return n&&e(t.prototype,n),o&&e(t,o),t}}(),b=f(l.Helmet,{},void 0,f("title",{},void 0,"About"),f("meta",{name:"description",content:"About"})),g=function(e){function t(){return o(this,t),s(this,(t.__proto__||Object.getPrototypeOf(t)).apply(this,arguments))}return a(t,e),y(t,[{key:"shouldComponentUpdate",value:function(){return!1}},{key:"render",value:function(){var e=[{title:"Home",loc:"/",icon:"home"},{title:"About",loc:"/about"}];return f(m.a,{},void 0,b,f(u.a,{breadcrumbs:e}),f("div",{style:{lineHeight:"2.5em",maxWidth:"700px"}},void 0,f(d.a,{},void 0,r.a.createElement(c.c,p.a.header)),r.a.createElement(c.c,p.a.esplain)))}}]),t}(r.a.Component);t.default=g},"./app/containers/FeaturePage/messages.js":function(e,t,n){"use strict";var o=n("./node_modules/react-intl/lib/index.es.js");t.a=n.i(o.d)({header:{id:"boilerplate.containers.FeaturePage.header",defaultMessage:"Features"},esplain:{id:"boilerplate.containers.FeaturePage.esplain",defaultMessage:"This is an dev version of DKAN 8."}})}});