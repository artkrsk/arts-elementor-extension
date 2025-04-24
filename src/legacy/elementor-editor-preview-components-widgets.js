'use strict';

(function () {
	// Elementor editor
	window.addEventListener('elementor/frontend/init', onElementorInit, {
		once: true
	});

	function onElementorInit() {
		class ArtsWidgetComponentHandler extends elementorModules.frontend.handlers.Base {
			constructor(args) {
				super(args);

				this.handlers = {
					update: () => {
						window.app.loaded.finally(() => {
							window.app.refresher.run();
						});
					}
				};
			}

			onInit() {
				this.addLightboxComponent();

				// if (!this.isWidgetInsideHeader()) {
				this.loadComponent().then((instances) => {
					const instance = instances && instances[0];

					if (instance && typeof instance.destroy === 'function') {
						if (document.body.contains(instance.element)) {
							// Destroy component when widget is removed or refreshed
							Object.assign(this, {
								onDestroy: this.destroy.bind(this, instance)
							});
							elementor.hooks.addAction('editor/widgets/loop-grid/on-init', this.destroy.bind(this, instance));
						} else {
							// By this moment the element doesn't exist in DOM anymore
							// for some reason (e.g. super-quick adding/removal action)
							// so call destroy() immediately
							this.destroy(instance);
						}
					}
					this.handlers.update();
				}).catch((e) => {
					console.error('Failed to load component');
					console.error(e);
				});
				// } else {
				// 	if (!isHeaderLoading) {
				// 		const app = getApp();
				// 		isHeaderLoading = true;

				// 		app.loadHeader().finally(() => {
				// 			app.componentsManager.updateRef('headerRef', 'Header', app.componentsManager.instances.persistent);

				// 			isHeaderLoading = false;
				// 		});
				// 	}
				// }
			}

			loadComponent() {
				const el = this.$element.get(0);
				const scope = this.hasWidgetLightbox() ? el.parentElement : el;

				return window.app.componentsManager.init({
					scope,
					loadOnlyFirst: true
				});
			}

			addLightboxComponent() {
				if (this.hasWidgetLightbox()) {
					this.$element.attr('data-arts-component-name', 'PSWP');
				} else if (this.$element.attr('data-arts-component-name') === 'PSWP') {
					this.$element.removeAttr('data-arts-component-name');
				}
			}

			hasWidgetLightbox() {
				const
					scope = this.$element.get(0),
					setting = this.getElementSettings('links_mode');

				return setting === 'lightbox' && !scope.querySelector('[data-arts-component-name="PSWP"]');
			}

			onDestroy() {
				// Do nothing

				// if (this.isWidgetInsideHeader()) {
				// 	const app = getApp();
				// 	const headerRef = app.componentsManager.getComponentByName('Header');

				// 	if (headerRef) {
				// 		app.componentsManager.disposeComponent(headerRef.element, 'persistent');
				// 	}

				// 	if (!isHeaderLoading) {
				// 		isHeaderLoading = true;

				// 		app.loadHeader().finally(() => {
				// 			app.componentsManager.updateRef('headerRef', 'Header', app.componentsManager.instances.persistent);
				// 			isHeaderLoading = false;
				// 		});
				// 	}
				// }
			}

			destroy(instance) {
				if (instance && instance.element) {
					window.app.componentsManager.destroyComponent({ element: instance.element });
					this.handlers.update();
				}
			}
		}

		window.ArtsWidgetComponentHandler = ArtsWidgetComponentHandler;
	}
})();
