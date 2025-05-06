import LiveSettings from './editor/LiveSettings.js'

window.addEventListener('elementor/init', () => {
  // Initialize the LiveSettings with the elementor instance
  LiveSettings.init(window.elementor)
})
