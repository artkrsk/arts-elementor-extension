/*!
 * Arts Elementor Extension v1.0.4
 * https://artemsemkin.com
 * https://github.com/artkrsk/arts-elementor-extension
 * © 2025 Artem Semkin
 * License: MIT
 */
// node_modules/.pnpm/@arts+utilities@file+..+ArtsUtilities_aeb58f64b3d32c916b3ea371a7fa3797/node_modules/@arts/utilities/dist/core/events/Debounce.mjs
var debounce = (fn, wait) => {
  let timeout;
  const debounced = function(...args) {
    clearTimeout(timeout);
    timeout = window.setTimeout(() => fn.apply(this, args), wait);
  };
  return debounced;
};

// src/js/editor/LiveSettings.js
var LiveSettings = class {
  /**
   * Creates an instance of LiveSettings
   */
  constructor() {
    this.elementorInstance = null;
    this.previewWindow = null;
    this.resizeObserver = null;
    this.debouncedResizeEmit = null;
  }
  /**
   * Initializes the live settings module
   * @param {Object} elementor - The Elementor object
   * @returns {Object} - Public API methods
   */
  init(elementor) {
    this.elementorInstance = elementor;
    this.elementorInstance.once("preview:loaded", () => {
      this.setWindow();
      this.addSettingsChangeCallbacks();
      this.addReloadPreviewListener();
    });
    return {
      updatePreview: this.updatePreview.bind(this),
      reloadPreview: this.reloadPreview.bind(this),
      emitEvent: this.emitEvent.bind(this),
      disconnect: this.disconnect.bind(this)
    };
  }
  /**
   * Sets the preview window reference
   * @private
   */
  setWindow() {
    this.previewWindow = this.elementorInstance.$preview.get(0).contentWindow;
    this.initResizeObserver();
  }
  /**
   * Initializes the ResizeObserver to watch for preview window size changes
   * @private
   */
  initResizeObserver() {
    if (!this.previewWindow) return;
    this.debouncedResizeEmit = debounce((entries) => {
      const entry = entries[0];
      if (entry) {
        const { width, height } = entry.contentRect;
        this.emitEvent("arts/elementor_extension/editor/preview_resized", {
          width,
          height,
          contentRect: entry.contentRect
        });
      }
    }, 300);
    this.resizeObserver = new ResizeObserver(this.debouncedResizeEmit);
    if (this.previewWindow.document && this.previewWindow.document.body) {
      this.resizeObserver.observe(this.previewWindow.document.body);
    }
    const elementorPreview = document.getElementById("elementor-preview");
    if (elementorPreview) {
      this.resizeObserver.observe(elementorPreview);
    }
  }
  /**
   * Disconnects the ResizeObserver
   * @private
   */
  disconnectResizeObserver() {
    if (this.resizeObserver) {
      this.resizeObserver.disconnect();
      this.resizeObserver = null;
    }
    this.debouncedResizeEmit = null;
  }
  /**
   * Sets up callbacks for settings changes
   * @private
   */
  addSettingsChangeCallbacks() {
    const { addChangeCallback } = this.elementorInstance.settings.page;
    const tabsControls = window.artsElementorExtensionEditorLiveSettings || [];
    tabsControls.forEach(
      (setting) => addChangeCallback(setting, (value) => this.onSettingChange(setting, value))
    );
  }
  /**
   * Handles setting change events
   * @param {string} setting - The setting that changed
   * @param {*} value - The new value
   * @private
   */
  onSettingChange(setting, value) {
    const settings = this.elementorInstance.settings.page.model.attributes;
    this.emitEvent("arts/elementor_extension/editor/setting_changed", {
      settings,
      setting,
      value
    });
  }
  /**
   * Adds event listener for reload preview event
   * @private
   */
  addReloadPreviewListener() {
    if (this.previewWindow) {
      this.previewWindow.removeEventListener(
        "arts/elementor_extension/editor/reload_preview",
        this.onReloadPreview.bind(this)
      );
      this.previewWindow.addEventListener(
        "arts/elementor_extension/editor/reload_preview",
        this.onReloadPreview.bind(this)
      );
    }
  }
  /**
   * Handles reload preview event
   * @param {CustomEvent} event - The event object
   * @private
   */
  onReloadPreview(event) {
    const detail = event.detail || {};
    const { route, section } = detail;
    this.reloadPreview(route, section);
  }
  /**
   * Updates the preview
   * @param {string} route - The route to navigate to
   * @param {string} section - The section to activate
   * @public
   */
  updatePreview(route, section) {
    this.elementorInstance.once("preview:loaded", () => {
      window.$e.data.get("globals/index").then(() => {
        setTimeout(() => {
          if (window.$e.routes.current.panel.search("panel/global") >= 0) {
            window.$e.run("panel/global/open").then(() => {
              if (route) {
                window.$e.route(route);
              }
              window.elementorCommon.elements.$body.removeClass("elementor-panel-loading");
            });
          } else {
            if (route) {
              window.$e.route(route);
            }
            if (section) {
              this.elementorInstance.getPanelView().getCurrentPageView().activateSection(section);
              this.elementorInstance.getPanelView().getCurrentPageView().openActiveSection();
              this.elementorInstance.getPanelView().getCurrentPageView().render();
            }
            window.elementorCommon.elements.$body.removeClass("elementor-panel-loading");
          }
        }, 500);
      });
    });
    window.$e.run("preview/reload");
  }
  /**
   * Reloads the preview
   * @param {string} route - The route to navigate to after reload
   * @param {string} section - The section to activate after reload
   * @public
   */
  reloadPreview(route, section) {
    window.elementorCommon.elements.$body.addClass("elementor-panel-loading");
    window.$e.run("document/save/update").then(() => {
      this.updatePreview(route, section);
    });
  }
  /**
   * Emits an event to the preview window
   * @param {string} eventName - The name of the event
   * @param {Object} data - The data to send with the event
   * @public
   */
  emitEvent(eventName, data) {
    if (this.previewWindow) {
      const event = new CustomEvent(eventName, {
        detail: data
      });
      this.previewWindow.dispatchEvent(event);
    }
  }
  /**
   * Disconnects all observers and cleans up resources
   * @public
   */
  disconnect() {
    this.disconnectResizeObserver();
  }
};
var LiveSettings_default = {
  init: (elementor) => new LiveSettings().init(elementor)
};

// src/js/index.js
window.addEventListener("elementor/init", () => {
  LiveSettings_default.init(window.elementor);
});
//# sourceMappingURL=index.mjs.map
