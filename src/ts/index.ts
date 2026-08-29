import LiveSettings from './editor/LiveSettings'

window.addEventListener('elementor/init', () => {
  if (window.elementor) {
    LiveSettings.init(window.elementor)
  }
})
