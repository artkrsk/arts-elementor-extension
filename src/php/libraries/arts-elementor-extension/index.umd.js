/*!
 * Arts Elementor Extension v1.0.3
 * https://artemsemkin.com
 * https://github.com/artkrsk/arts-elementor-extension
 * © 2025 Artem Semkin
 * License: MIT
 */
(function(root, factory) {if (typeof define === 'function' && define.amd) {define(["jquery","elementor","backbone"], factory);} else if (typeof module === 'object' && module.exports) {module.exports = factory(require('jquery'), require('elementor'), require('backbone'));} else {root.ArtsElementorExtension = factory(root.jQuery, root.elementor, root.Backbone);}}(typeof self !== 'undefined' ? self : this, function(jQuery, elementor, Backbone) {
/*!
 * Arts Elementor Extension v1.0.3
 * https://artemsemkin.com
 * https://github.com/artkrsk/arts-elementor-extension
 * © 2025 Artem Semkin
 * License: MIT
 */
var ArtsElementorExtension=(()=>{var o=class{constructor(){this.elementorInstance=null,this.previewWindow=null}init(e){return this.elementorInstance=e,this.elementorInstance.once("preview:loaded",()=>{this.setWindow(),this.addSettingsChangeCallbacks(),this.addReloadPreviewListener()}),{updatePreview:this.updatePreview.bind(this),reloadPreview:this.reloadPreview.bind(this),emitEvent:this.emitEvent.bind(this)}}setWindow(){this.previewWindow=this.elementorInstance.$preview.get(0).contentWindow}addSettingsChangeCallbacks(){let{addChangeCallback:e}=this.elementorInstance.settings.page;(window.artsElementorExtensionEditorLiveSettings||[]).forEach(n=>e(n,i=>this.onSettingChange(n,i)))}onSettingChange(e,t){let n=this.elementorInstance.settings.page.model.attributes;this.emitEvent("arts/elementor_extension/editor/setting_changed",{settings:n,setting:e,value:t})}addReloadPreviewListener(){this.previewWindow&&(this.previewWindow.removeEventListener("arts/elementor_extension/editor/reload_preview",this.onReloadPreview.bind(this)),this.previewWindow.addEventListener("arts/elementor_extension/editor/reload_preview",this.onReloadPreview.bind(this)))}onReloadPreview(e){let t=e.detail||{},{route:n,section:i}=t;this.reloadPreview(n,i)}updatePreview(e,t){this.elementorInstance.once("preview:loaded",()=>{window.$e.data.get("globals/index").then(()=>{setTimeout(()=>{window.$e.routes.current.panel.search("panel/global")>=0?window.$e.run("panel/global/open").then(()=>{e&&window.$e.route(e),window.elementorCommon.elements.$body.removeClass("elementor-panel-loading")}):(e&&window.$e.route(e),t&&(this.elementorInstance.getPanelView().getCurrentPageView().activateSection(t),this.elementorInstance.getPanelView().getCurrentPageView().openActiveSection(),this.elementorInstance.getPanelView().getCurrentPageView().render()),window.elementorCommon.elements.$body.removeClass("elementor-panel-loading"))},500)})}),window.$e.run("preview/reload")}reloadPreview(e,t){window.elementorCommon.elements.$body.addClass("elementor-panel-loading"),window.$e.run("document/save/update").then(()=>{this.updatePreview(e,t)})}emitEvent(e,t){if(this.previewWindow){let n=new CustomEvent(e,{detail:t});this.previewWindow.dispatchEvent(n)}}},r={init:s=>new o().init(s)};window.addEventListener("elementor/init",()=>{r.init(window.elementor)});})();

return ArtsElementorExtension;}));