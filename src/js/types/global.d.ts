/**
 * Global type declarations for Arts Elementor Extension
 */

interface ElementorE {
  data: {
    get(endpoint: string): Promise<any>
  }
  routes: {
    current: {
      panel: string
    }
  }
  run(route: string): Promise<any>
  route(route: string): void
}

interface ElementorCommon {
  elements: {
    $body: JQuery
  }
}

interface Window {
  elementor: {
    $preview: JQuery
    once(event: string, callback: Function): void
    hooks: {
      addAction(hookName: string, callback: Function): void
    }
    getPanelView(): {
      getCurrentPageView(): {
        activateSection(section: string): void
        openActiveSection(): void
        render(): void
      }
    }
    settings: {
      page: {
        model: {
          attributes: any
        }
        addChangeCallback(setting: string, callback: Function): void
      }
    }
  }
  elementorCommon: ElementorCommon
  $e: ElementorE
  elementorModules: {
    frontend: {
      handlers: {
        Base: any
      }
    }
  }
  app: {
    loaded: Promise<any>
    refresher: {
      run(): void
    }
    componentsManager: {
      init(options: { scope: Element; loadOnlyFirst?: boolean }): Promise<any[]>
      destroyComponent(options: { element: Element }): void
    }
  }
  ArtsWidgetComponentHandler: any
  artsElementorExtensionEditorLiveSettings: string[]
}
