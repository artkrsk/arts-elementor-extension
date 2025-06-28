import { debounce } from '@arts/utilities'

/**
 * Elementor Editor Live Settings
 * Handles live settings changes in the Elementor editor
 */

/**
 * LiveSettings class for handling Elementor editor settings changes
 */
class LiveSettings {
  /**
   * Creates an instance of LiveSettings
   */
  constructor() {
    this.elementorInstance = null
    this.previewWindow = null
    this.resizeObserver = null
    this.debouncedResizeEmit = null
  }

  /**
   * Initializes the live settings module
   * @param {Object} elementor - The Elementor object
   * @returns {Object} - Public API methods
   */
  init(elementor) {
    this.elementorInstance = elementor

    // Initialize when preview is loaded
    this.elementorInstance.once('preview:loaded', () => {
      this.setWindow()
      this.addSettingsChangeCallbacks()
      this.addReloadPreviewListener()
    })

    // Return public API
    return {
      updatePreview: this.updatePreview.bind(this),
      reloadPreview: this.reloadPreview.bind(this),
      emitEvent: this.emitEvent.bind(this),
      disconnect: this.disconnect.bind(this)
    }
  }

  /**
   * Sets the preview window reference
   * @private
   */
  setWindow() {
    this.previewWindow = this.elementorInstance.$preview.get(0).contentWindow
    this.initResizeObserver()
  }

  /**
   * Initializes the ResizeObserver to watch for preview window size changes
   * @private
   */
  initResizeObserver() {
    if (!this.previewWindow) return

    // Create debounced function for resize events
    this.debouncedResizeEmit = debounce((entries) => {
      const entry = entries[0]
      if (entry) {
        const { width, height } = entry.contentRect
        this.emitEvent('arts/elementor_extension/editor/preview_resized', {
          width,
          height,
          contentRect: entry.contentRect
        })
      }
    }, 250) // 250ms debounce delay

    // Create ResizeObserver
    this.resizeObserver = new ResizeObserver(this.debouncedResizeEmit)

    // Observe the preview window's document body
    if (this.previewWindow.document && this.previewWindow.document.body) {
      this.resizeObserver.observe(this.previewWindow.document.body)
    }
  }

  /**
   * Disconnects the ResizeObserver
   * @private
   */
  disconnectResizeObserver() {
    if (this.resizeObserver) {
      this.resizeObserver.disconnect()
      this.resizeObserver = null
    }
    this.debouncedResizeEmit = null
  }

  /**
   * Sets up callbacks for settings changes
   * @private
   */
  addSettingsChangeCallbacks() {
    const { addChangeCallback } = this.elementorInstance.settings.page

    // Get settings from the global variable or use empty array as fallback
    // This variable should be localized by WordPress
    const tabsControls = window.artsElementorExtensionEditorLiveSettings || []

    // Bind the method to this instance
    tabsControls.forEach((setting) =>
      addChangeCallback(setting, (value) => this.onSettingChange(setting, value))
    )
  }

  /**
   * Handles setting change events
   * @param {string} setting - The setting that changed
   * @param {*} value - The new value
   * @private
   */
  onSettingChange(setting, value) {
    const settings = this.elementorInstance.settings.page.model.attributes

    this.emitEvent('arts/elementor_extension/editor/setting_changed', {
      settings,
      setting,
      value
    })
  }

  /**
   * Adds event listener for reload preview event
   * @private
   */
  addReloadPreviewListener() {
    if (this.previewWindow) {
      this.previewWindow.removeEventListener(
        'arts/elementor_extension/editor/reload_preview',
        this.onReloadPreview.bind(this)
      )
      this.previewWindow.addEventListener(
        'arts/elementor_extension/editor/reload_preview',
        this.onReloadPreview.bind(this)
      )
    }
  }

  /**
   * Handles reload preview event
   * @param {CustomEvent} event - The event object
   * @private
   */
  onReloadPreview(event) {
    // Check if event.detail exists
    const detail = event.detail || {}

    // Extract route and section with default values (undefined)
    const { route, section } = detail

    // Pass to reloadPreview - the function already has proper handling for undefined values
    this.reloadPreview(route, section)
  }

  /**
   * Updates the preview
   * @param {string} route - The route to navigate to
   * @param {string} section - The section to activate
   * @public
   */
  updatePreview(route, section) {
    this.elementorInstance.once('preview:loaded', () => {
      window.$e.data.get('globals/index').then(() => {
        setTimeout(() => {
          if (window.$e.routes.current.panel.search('panel/global') >= 0) {
            window.$e.run('panel/global/open').then(() => {
              if (route) {
                window.$e.route(route)
              }

              window.elementorCommon.elements.$body.removeClass('elementor-panel-loading')
            })
          } else {
            if (route) {
              window.$e.route(route)
            }

            if (section) {
              this.elementorInstance.getPanelView().getCurrentPageView().activateSection(section)
              this.elementorInstance.getPanelView().getCurrentPageView().openActiveSection()
              this.elementorInstance.getPanelView().getCurrentPageView().render()
            }

            window.elementorCommon.elements.$body.removeClass('elementor-panel-loading')
          }
        }, 500)
      })
    })

    window.$e.run('preview/reload')
  }

  /**
   * Reloads the preview
   * @param {string} route - The route to navigate to after reload
   * @param {string} section - The section to activate after reload
   * @public
   */
  reloadPreview(route, section) {
    window.elementorCommon.elements.$body.addClass('elementor-panel-loading')
    window.$e.run('document/save/update').then(() => {
      this.updatePreview(route, section)
    })
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
      })

      this.previewWindow.dispatchEvent(event)
    }
  }

  /**
   * Disconnects all observers and cleans up resources
   * @public
   */
  disconnect() {
    this.disconnectResizeObserver()
  }
}

// Create and export a singleton instance
export default {
  init: (elementor) => new LiveSettings().init(elementor)
}
