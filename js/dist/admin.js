(()=>{var e={175:e=>{"use strict";var t=/(?:https?:)?\/\/(?:(?:[\w-]+\.)+[\w/#@~.-]*)(?:\?(?:[\w&=.!,;$#%-]+)?)?/gi;e.exports=function(e){return(e||"").replace(t,(function(e){return'<a href="'+e+'">'+e+"</a>"}))}}},t={};function o(r){var n=t[r];if(void 0!==n)return n.exports;var i=t[r]={exports:{}};return e[r](i,i.exports,o),i.exports}o.n=e=>{var t=e&&e.__esModule?()=>e.default:()=>e;return o.d(t,{a:t}),t},o.d=(e,t)=>{for(var r in t)o.o(t,r)&&!o.o(e,r)&&Object.defineProperty(e,r,{enumerable:!0,get:t[r]})},o.o=(e,t)=>Object.prototype.hasOwnProperty.call(e,t),(()=>{"use strict";const e=flarum.core.compat["admin/app"];var t=o.n(e);function r(e,t){return r=Object.setPrototypeOf?Object.setPrototypeOf.bind():function(e,t){return e.__proto__=t,e},r(e,t)}const n=flarum.core.compat["common/components/Alert"];var i=o.n(n);const s=flarum.core.compat["admin/components/ExtensionPage"];var a=o.n(s);const p=flarum.core.compat["common/helpers/humanTime"];var c=o.n(p);const l=flarum.core.compat["common/utils/extractText"];var f=o.n(l),u=o(175),g=o.n(u),d=function(e){var o,n;function s(){return e.apply(this,arguments)||this}n=e,(o=s).prototype=Object.create(n.prototype),o.prototype.constructor=o,r(o,n);var a=s.prototype;return a.oninit=function(t){e.prototype.oninit.call(this,t)},a.content=function(){var e=this.setting("fof-geoip.service")(),o=1e3*Number(t().data.settings["fof-geoip.services."+e+".last_error_time"]),r=t().data.settings["fof-geoip.services."+e+".error"];return r&&(r=g()(r)),[m("div",{className:"container"},m("div",{className:"geopage"},m("div",{className:"Form-group"},this.buildSettingComponent({type:"select",setting:"fof-geoip.service",label:t().translator.trans("fof-geoip.admin.settings.service_label"),options:t().data["fof-geoip.services"].reduce((function(e,o){return e[o]=t().translator.trans("fof-geoip.admin.settings.service_"+o+"_label"),e}),{}),required:!0,help:e&&m.trust(g()(f()(t().translator.trans("fof-geoip.admin.settings.service_"+e+"_description"))))})),r?i().component({className:"Form-group",dismissible:!1},[m("b",{style:{textTransform:"uppercase",marginRight:"5px"}},c()(o)),m.trust(r)]):"",["ipdata","ipapi-pro","ipsevenex"].includes(e)?[this.buildSettingComponent({type:"string",setting:"fof-geoip.services."+e+".access_key",label:t().translator.trans("fof-geoip.admin.settings.access_key_label"),required:!0})]:[],"ipdata"===e?this.buildSettingComponent({type:"number",setting:"fof-geoip.services.ipdata.quota",label:t().translator.trans("fof-geoip.admin.settings.quota_label"),min:1500,placeholder:1500}):[],this.buildSettingComponent({setting:"fof-geoip.showFlag",type:"boolean",label:t().translator.trans("fof-geoip.admin.settings.show_flag_label"),help:t().translator.trans("fof-geoip.admin.settings.show_flag_help")}),this.submitButton()))]},s}(a());t().initializers.add("fof/geoip",(function(){t().extensionData.for("fof-geoip").registerPage(d).registerPermission({icon:"fas fa-globe",permission:"fof-geoip.canSeeCountry",label:t().translator.trans("fof-geoip.admin.permissions.see_country")},"moderate",50)}))})(),module.exports={}})();
//# sourceMappingURL=admin.js.map