'use strict';

(function (elementor) {
	let previewWindow;

	elementor.once('preview:loaded', () => {
		setWindow();
		addSettingsChangeCallbacks();
		addReloadPreviewListener();
	});

	function addSettingsChangeCallbacks() {
		const { addChangeCallback } = elementor.settings.page;
		const tabsControls = artsElementorExtensionEditorLiveSettings || [];

		tabsControls.forEach((setting) => addChangeCallback(setting, onSettingChange.bind(this, setting)));
	}

	function onSettingChange(setting, value) {
		const settings = elementor.settings.page.model.attributes;

		emitEvent('arts/elementor_extension/editor/setting_changed', {
			settings,
			setting,
			value,
		});
	}

	function setWindow() {
		previewWindow = elementor.$preview.get(0).contentWindow;
	}

	function updatePreview(route, section) {
		elementor.once('preview:loaded', () => {
			// file deepcode ignore PromiseNotCaughtGeneral: <no catch function available>
			$e.data.get('globals/index').then(() => {
				setTimeout(() => {
					if ($e.routes.current.panel.search('panel/global') >= 0) {
						$e.run('panel/global/open').then(() => {
							if (route) {
								$e.route(route);
							}

							elementorCommon.elements.$body.removeClass('elementor-panel-loading');
						});
					} else {
						if (route) {
							$e.route(route);
						}

						if (section) {
							elementor.getPanelView().getCurrentPageView().activateSection(section);
							elementor.getPanelView().getCurrentPageView().openActiveSection();
							elementor.getPanelView().getCurrentPageView().render();
						}

						elementorCommon.elements.$body.removeClass('elementor-panel-loading');
					}
				}, 500);
			});
		});

		$e.run('preview/reload');
	}

	function reloadPreview(route, section) {
		elementorCommon.elements.$body.addClass('elementor-panel-loading');
		// file deepcode ignore PromiseNotCaughtGeneral: <no catch function available>
		$e.run('document/save/update').then(() => {
			updatePreview(route, section);
		});
	}

	function onReloadPreview(event) {
		// Check if event.detail exists
		const detail = event.detail || {};

		// Extract route and section with default values (undefined)
		const { route, section } = detail;

		// Pass to reloadPreview - the function already has proper handling for undefined values
		reloadPreview(route, section);
	}

	function addReloadPreviewListener() {
		if (previewWindow) {
			previewWindow.removeEventListener('arts/elementor_extension/editor/reload_preview', onReloadPreview);
			previewWindow.addEventListener('arts/elementor_extension/editor/reload_preview', onReloadPreview);
		}
	}

	function emitEvent(eventName, data) {
		if (previewWindow) {
			const event = new CustomEvent(eventName, {
				detail: data,
			});

			previewWindow.dispatchEvent(event);
		}
	}
})(elementor);
