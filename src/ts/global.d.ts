import type { $e, ElementorCommon, ElementorEditor } from '@artemsemkin/elementor-types'

declare global {
  interface Window {
    /** Editor-context global (absent on the plain frontend). */
    elementor?: ElementorEditor
    elementorCommon?: ElementorCommon
    $e?: $e
    /** Control IDs localized by PHP for live-preview subscriptions. */
    artsElementorExtensionEditorLiveSettings?: string[]
  }
}

export {}
