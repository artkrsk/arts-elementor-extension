import { debounce } from '@arts/utilities'

/**
 * Bridges Elementor editor setting changes and preview-iframe size changes
 * into CustomEvents the preview frame can listen for. One instance per editor
 * session — exposed as a default-export factory at the bottom of this file.
 */
class LiveSettings {
  constructor() {
    this.elementorInstance = null
    this.previewWindow = null
    this.resizeObserver = null
    this.debouncedResizeEmit = null
  }

  /**
   * Captures the elementor instance and defers DOM-dependent wiring to the
   * first `preview:loaded` event so the preview iframe is available.
   *
   * @param {Object} elementor - The Elementor object
   * @returns {Object} - Public API methods
   */
  init(elementor) {
    this.elementorInstance = elementor

    this.elementorInstance.once('preview:loaded', () => {
      this.setWindow()
      this.addSettingsChangeCallbacks()
      this.addReloadPreviewListener()
    })

    return {
      updatePreview: this.updatePreview.bind(this),
      reloadPreview: this.reloadPreview.bind(this),
      emitEvent: this.emitEvent.bind(this),
      disconnect: this.disconnect.bind(this)
    }
  }

  /** @private */
  setWindow() {
    this.previewWindow = this.elementorInstance.$preview.get(0).contentWindow
    this.initResizeObserver()
  }

  /**
   * Observes both the preview iframe's document.body and the outer
   * #elementor-preview wrapper, debouncing emissions of
   * `arts/elementor_extension/editor/preview_resized` by 300ms to avoid
   * thrashing during drag-resize.
   *
   * @private
   */
  initResizeObserver() {
    if (!this.previewWindow) return

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
    }, 300)

    this.resizeObserver = new ResizeObserver(this.debouncedResizeEmit)

    if (this.previewWindow.document && this.previewWindow.document.body) {
      this.resizeObserver.observe(this.previewWindow.document.body)
    }

    const elementorPreview = document.getElementById('elementor-preview')

    if (elementorPreview) {
      this.resizeObserver.observe(elementorPreview)
    }
  }

  /** @private */
  disconnectResizeObserver() {
    if (this.resizeObserver) {
      this.resizeObserver.disconnect()
      this.resizeObserver = null
    }
    this.debouncedResizeEmit = null
  }

  /**
   * Subscribes to Elementor page settings changes for the control IDs that PHP
   * localized into `window.artsElementorExtensionEditorLiveSettings`.
   *
   * @private
   */
  addSettingsChangeCallbacks() {
    const { addChangeCallback } = this.elementorInstance.settings.page

    const tabsControls = window.artsElementorExtensionEditorLiveSettings || []

    tabsControls.forEach((setting) =>
      addChangeCallback(setting, (value) => this.onSettingChange(setting, value))
    )
  }

  /**
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
   * Reattaches the `arts/elementor_extension/editor/reload_preview` listener on
   * the preview window. The prior listener is removed first so repeated calls
   * (e.g. after preview reloads) don't stack handlers.
   *
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
   * @param {CustomEvent} event - The event object
   * @private
   */
  onReloadPreview(event) {
    const detail = event.detail || {}
    const { route, section } = detail

    this.reloadPreview(route, section)
  }

  /**
   * Triggers an Elementor `preview/reload` and — after the next `preview:loaded`
   * — restores panel state: if a `panel/global/...` route is active, reopens it;
   * otherwise navigates to `route` (when provided) and activates `section`
   * inside the current page view.
   *
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
   * Sets the editor loading state, runs `document/save/update`, then defers to
   * updatePreview() to reload and restore panel state.
   *
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
   * Dispatches a CustomEvent into the preview iframe. No-op until the preview
   * window has been captured by setWindow().
   *
   * @param {string} eventName
   * @param {Object} data - Becomes event.detail
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

  /** @public */
  disconnect() {
    this.disconnectResizeObserver()
  }
}

export default {
  init: (elementor) => new LiveSettings().init(elementor)
}
