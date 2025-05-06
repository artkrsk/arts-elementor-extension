/*!
 * Arts Elementor Extension v1.0.2
 * https://artemsemkin.com
 * https://github.com/artkrsk/arts-elementor-extension
 * © 2025 Artem Semkin
 * License: MIT
 */
(function(root, factory) {if (typeof define === 'function' && define.amd) {define(["jquery","elementor","backbone"], factory);} else if (typeof module === 'object' && module.exports) {module.exports = factory(require('jquery'), require('elementor'), require('backbone'));} else {root.ArtsElementorExtension = factory(root.jQuery, root.elementor, root.Backbone);}}(typeof self !== 'undefined' ? self : this, function(jQuery, elementor, Backbone) {
/*!
 * Arts Elementor Extension v1.0.2
 * https://artemsemkin.com
 * https://github.com/artkrsk/arts-elementor-extension
 * © 2025 Artem Semkin
 * License: MIT
 */
var ArtsElementorExtension = (() => {
  // src/js/index.js
  console.log("test test");
  (function(elementor2) {
    elementor2.once("preview:loaded", () => {
      console.log("Elementor preview loaded");
    });
  })("elementor");
})();
//# sourceMappingURL=index.umd.js.map

return ArtsElementorExtension;}));