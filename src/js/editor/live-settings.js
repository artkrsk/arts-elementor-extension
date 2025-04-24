/**
 * Elementor Editor Live Settings
 * Handles live settings changes in the Elementor editor
 */

// Module-level variables
let elementorInstance = null
let previewWindow = null

/**
 * Sets the preview window reference
 */
const setWindow = () => {
  previewWindow = elementorInstance.$preview.get(0).contentWindow
}

/**
 * Sets up callbacks for settings changes
 */
const addSettingsChangeCallbacks = () => {
  const { addChangeCallback } = elementorInstance.settings.page

  // Get settings from the global variable or use empty array as fallback
  // This variable should be localized by WordPress
  const tabsControls = window.artsElementorExtensionEditorLiveSettings || []

  // Use arrow function to avoid 'this' context issues
  tabsControls.forEach((setting) =>
    addChangeCallback(setting, (value) => onSettingChange(setting, value))
  )
}

/**
 * Handles setting change events
 * @param {string} setting - The setting that changed
 * @param {*} value - The new value
 */
const onSettingChange = (setting, value) => {
  const settings = elementorInstance.settings.page.model.attributes

  emitEvent('arts/elementor_extension/editor/setting_changed', {
    settings,
    setting,
    value
  })
}

/**
 * Adds event listener for reload preview event
 */
const addReloadPreviewListener = () => {
  if (previewWindow) {
    previewWindow.removeEventListener(
      'arts/elementor_extension/editor/reload_preview',
      onReloadPreview
    )
    previewWindow.addEventListener(
      'arts/elementor_extension/editor/reload_preview',
      onReloadPreview
    )
  }
}

/**
 * Handles reload preview event
 * @param {CustomEvent} event - The event object
 */
const onReloadPreview = (event) => {
  // Check if event.detail exists
  const detail = event.detail || {}

  // Extract route and section with default values (undefined)
  const { route, section } = detail

  // Pass to reloadPreview - the function already has proper handling for undefined values
  reloadPreview(route, section)
}

/**
 * Updates the preview
 * @param {string} route - The route to navigate to
 * @param {string} section - The section to activate
 */
const updatePreview = (route, section) => {
  elementorInstance.once('preview:loaded', () => {
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
            elementorInstance.getPanelView().getCurrentPageView().activateSection(section)
            elementorInstance.getPanelView().getCurrentPageView().openActiveSection()
            elementorInstance.getPanelView().getCurrentPageView().render()
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
 */
const reloadPreview = (route, section) => {
  window.elementorCommon.elements.$body.addClass('elementor-panel-loading')
  window.$e.run('document/save/update').then(() => {
    updatePreview(route, section)
  })
}

/**
 * Emits an event to the preview window
 * @param {string} eventName - The name of the event
 * @param {Object} data - The data to send with the event
 */
const emitEvent = (eventName, data) => {
  if (previewWindow) {
    const event = new window.CustomEvent(eventName, {
      detail: data
    })

    previewWindow.dispatchEvent(event)
  }
}

/**
 * Initializes the live settings module
 * @param {Object} elementor - The Elementor object
 * @returns {Object} - Module API
 */
const init = (elementor) => {
  elementorInstance = elementor

  // Initialize when preview is loaded
  elementor.once('preview:loaded', () => {
    setWindow()
    addSettingsChangeCallbacks()
    addReloadPreviewListener()
  })

  // Return public API
  return {
    updatePreview,
    reloadPreview,
    emitEvent
  }
}

export default { init }
