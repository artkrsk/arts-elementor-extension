import LiveSettings from './editor/LiveSettings.js'

window.addEventListener('elementor/init', () => {
  LiveSettings.init(window.elementor)
})
