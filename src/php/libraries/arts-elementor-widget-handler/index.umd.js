'use strict'
;(function () {
  window.addEventListener('elementor/frontend/init', onElementorInit, {
    once: true
  })

  function onElementorInit() {
    window.ArtsWidgetComponentHandler = class extends (
      window.elementorModules.frontend.handlers.Base
    ) {
      onInit(args) {
        super.onInit(args)

        const event = new CustomEvent('arts/elementor-base-widget/widget/init', {
          detail: {
            instance: this,
            element: this.$element.get(0)
          }
        })

        document.dispatchEvent(event)
      }

      onDestroy(args) {
        super.onDestroy(args)

        const event = new CustomEvent('arts/elementor-base-widget/widget/destroy', {
          detail: {
            instance: this,
            element: this.$element.get(0)
          }
        })

        document.dispatchEvent(event)
      }

      // A control change that does NOT re-render the widget (Style-tab
      // `selectors` controls, render_type 'ui'): Elementor only rewrites the
      // element's <style> tag, so no init/destroy pair fires. Defining this
      // method is what makes the base handler subscribe to the editor's
      // change channel for this element.
      onElementChange(propertyName) {
        const event = new CustomEvent('arts/elementor-base-widget/widget/change', {
          detail: {
            instance: this,
            element: this.$element.get(0),
            propertyName
          }
        })

        document.dispatchEvent(event)
      }
    }
  }
})()
