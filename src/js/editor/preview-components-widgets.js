/**
 * Elementor Editor Preview Components Widgets
 * Handles component widgets in the Elementor editor preview
 */

/**
 * ArtsWidgetComponentHandler class
 * Base handler for Arts widgets with components
 */
class ArtsWidgetComponentHandler extends window.elementorModules.frontend.handlers.Base {
  /**
   * Constructor for the component handler
   * @param {Object} args - Constructor arguments
   */
  constructor(args) {
    super(args)

    this.handlers = {
      update: () => {
        window.app.loaded.finally(() => {
          window.app.refresher.run()
        })
      }
    }
  }

  /**
   * Called when the widget is initialized
   */
  onInit() {
    this.addLightboxComponent()

    this.loadComponent()
      .then((instances) => {
        const instance = instances && instances[0]

        if (instance && typeof instance.destroy === 'function') {
          if (document.body.contains(instance.element)) {
            // Destroy component when widget is removed or refreshed
            Object.assign(this, {
              onDestroy: this.destroy.bind(this, instance)
            })
            window.elementor.hooks.addAction(
              'editor/widgets/loop-grid/on-init',
              this.destroy.bind(this, instance)
            )
          } else {
            // By this moment the element doesn't exist in DOM anymore
            // for some reason (e.g. super-quick adding/removal action)
            // so call destroy() immediately
            this.destroy(instance)
          }
        }
        this.handlers.update()
      })
      .catch((e) => {
        console.error('Failed to load component')
        console.error(e)
      })
  }

  /**
   * Loads the component for the widget
   * @returns {Promise} - Promise that resolves with the component instances
   */
  loadComponent() {
    const el = this.$element.get(0)
    const scope = this.hasWidgetLightbox() ? el.parentElement : el

    return window.app.componentsManager.init({
      scope,
      loadOnlyFirst: true
    })
  }

  /**
   * Adds lightbox component attribute if needed
   */
  addLightboxComponent() {
    if (this.hasWidgetLightbox()) {
      this.$element.attr('data-arts-component-name', 'PSWP')
    } else if (this.$element.attr('data-arts-component-name') === 'PSWP') {
      this.$element.removeAttr('data-arts-component-name')
    }
  }

  /**
   * Checks if the widget has lightbox functionality
   * @returns {boolean} - True if the widget has lightbox
   */
  hasWidgetLightbox() {
    const scope = this.$element.get(0),
      setting = this.getElementSettings('links_mode')

    return setting === 'lightbox' && !scope.querySelector('[data-arts-component-name="PSWP"]')
  }

  /**
   * Destroys the component
   * @param {Object} instance - Component instance to destroy
   */
  destroy(instance) {
    if (instance && instance.element) {
      window.app.componentsManager.destroyComponent({ element: instance.element })
      this.handlers.update()
    }
  }
}

/**
 * Initializes the preview components widgets module
 * @returns {Object} - Module API
 */
const init = () => {
  // Expose the handler class to the global scope
  window.ArtsWidgetComponentHandler = ArtsWidgetComponentHandler

  return {
    ArtsWidgetComponentHandler
  }
}

export default { init }
