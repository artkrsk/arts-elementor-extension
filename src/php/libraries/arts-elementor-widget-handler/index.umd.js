'use strict'
;(function () {
  // Elementor editor
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
    }
  }
})()
