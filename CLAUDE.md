# Arts Elementor Extension

A PHP Composer library (`arts/elementor-extension`) plus a small bundled editor-JS layer that
other Arts plugins and themes depend on to build Elementor extensions — widgets, skins, kit
settings tabs, and an editor live-settings bridge — declaratively. The PHP side registers
everything with Elementor; the JS layer bridges editor/preview changes into DOM CustomEvents.
Consumers rarely call the API directly: they extend the `Base*` classes and register them through
`arts/elementor_extension/*` filters.

Built on the in-house `arts/base` framework (singleton `Plugin` + typed managers container) and
`arts/utilities`.

## Commands

- PHP analysis/style: `composer check` (runs `phpcs` then `phpstan`); autofix with `composer fix`
  (phpcbf). Configs: `phpcs.xml` (WordPress standards, tabs), `phpstan.neon`.
- JS lint/format: `pnpm lint` (eslint), `pnpm format:check` (prettier).
- Build via the `__build__` git submodule (arts-public-builder): `pnpm build`, watch `pnpm dev`.
  Build output lands in `dist/` (npm entry) and `src/php/libraries/*` (editor bundles PHP enqueues).

## Layout

```
src/php/          PSR-4 `Arts\ElementorExtension\` (composer autoload)
  Plugin.php      boot + Elementor/PHP version gating (admin notices on failure)
  Managers/       Categories, Editor, Tabs, Widgets (+ BaseManager)
  Containers/     typed ManagersContainer (IDE/PHPStan property types only)
  Widgets/        BaseWidget, BaseSkin, Traits\{Preloads,WPML}
  Tabs/           BaseTab (custom kit settings tabs)
  Plugins/        BasePlugin / BaseManager — extension points for consumer plugins
  libraries/      pre-built editor JS enqueued by PHP (build output — do not hand-edit)
  languages/      arts-elementor-extension.pot (generated)
src/js/           editor bridge, plain JS + JSDoc types (jsconfig checkJs)
  editor/         LiveSettings (preview↔editor bridge), utilities (settings mapping)
```

## Architecture

`Plugin` (extends `arts/base` `BasePlugin`) boots on `elementor/loaded`, installs the typed
`ManagersContainer`, and wires four managers — each hooked to an Elementor registration action and
each reading one `arts/elementor_extension/*` filter for its config:

- **Categories** → `elementor/elements/categories_registered` — adds widget categories.
- **Widgets** → `elementor/widgets/register` (+ `init` for per-widget init actions) — instantiates
  and registers widget classes.
- **Tabs** → `elementor/kit/register_tabs` — registers `BaseTab` subclasses as kit settings tabs.
- **Editor** → `elementor/editor/after_enqueue_scripts` + `wp_enqueue_scripts` — enqueues the
  live-settings and widget-handler JS bundles.

Managers extend the local `Managers\BaseManager`, whose `init()` deliberately does **not** call
`parent::init()`: it inserts an `apply_filters()` step between `init_properties()` and
`add_managers()` so subclasses can filter their config before wiring.

`BaseWidget` extends Elementor `Widget_Base` and splits control registration into
`register_controls_{content,settings,layout,style}()`; the `Preloads`/`WPML` traits integrate with
ArtsOptimizer and WPML. `BaseSkin` extends `Skin_Base`. `BaseTab` extends Elementor `Tab_Base` and
can persist control values to WordPress options.

## Extension model (consumer API)

- Boot: extend `Plugins\BasePlugin`; its `do_run()` calls `Plugin::instance()`.
- Register by filtering arrays:
  - `arts/elementor_extension/widgets/widgets` → `[ ['file' => …, 'class' => WidgetClass], … ]`
  - `arts/elementor_extension/tabs/tabs` → `[ ['file' => …, 'class' => TabClass], … ]`
  - `arts/elementor_extension/widgets/categories` → `[ ['name' => …, 'title' => …, 'icon'? => …], … ]`
  - `arts/elementor_extension/editor/live_settings` → control IDs that re-emit change events in the
    editor (Tabs auto-appends each tab's `EDITOR_CHANGE_CALLBACK_CONTROLS`).
- `BaseTab`: a control declared with `save_db => 'option'` and a type in `SYNC_CONTROL_TYPES` is
  copied to a WP option on the publish-transition save (`before_save`).

## Hook & event catalog

Fires (actions), each passing the registered set + the manager: `.../widgets/categories_registered`,
`.../tabs/tabs_registered`, `.../widgets/widgets_registered` (prefix `arts/elementor_extension`).
External integrations: `Preloads` adds `arts/optimizer/preloads/{assets,modules,images,prefetch}_map`;
`WPML` adds `wpml_elementor_widgets_to_translate`.
JS CustomEvents dispatched into the preview iframe:
`arts/elementor_extension/editor/{preview_resized,setting_changed}`; listens for `.../reload_preview`.
The frontend widget handler dispatches `arts/elementor-base-widget/widget/{init,destroy}` on `document`.

## Frozen identifiers

- Text domain: `arts-elementor-extension`.
- Script handles: `arts-elementor-extension-editor-live-settings`, `arts-elementor-extension-widget-handler`.
- Localized editor global: `artsElementorExtensionEditorLiveSettings`; frontend handler class:
  `window.ArtsWidgetComponentHandler`.
- Default kit tab id: `BaseTab::TAB_ID = 'arts-elementor-extension-custom-tab'`.
- Boot config keys gating startup: `required_elementor_version` (default 3.18), `required_php_version`
  (default 7.4).

## Gotchas

- **Strauss duplication is the dominant constraint.** This package can be vendored (Strauss-prefixed)
  into several sibling plugins and the theme at once, so every de-dup guard is load-bearing:
  `class_exists()` checks before `require`, the `$GLOBALS['__arts_elementor_widget_handlers']` ledger,
  and the `$wp_scripts->registered[…]->extra['after']` inspection before `wp_add_inline_script`. Don't
  "simplify" these away.
- Editor JS is bundled into `src/php/libraries/*` and enqueued by PHP — edit `src/js/` and rebuild,
  never the bundles.
- `Utilities::is_elementor_editor_active()` / `is_elementor_feature_active()` are `arts/utilities`
  wrappers, not Elementor APIs (the latter checks the experiments manager, e.g. `e_optimized_markup`).

## Dependencies

- Composer: `php >=8.0`, `arts/base`, `arts/utilities` (PSR-4 `Arts\ElementorExtension\` → `src/php/`).
- npm: `@arts/utilities` (workspace `file:../ArtsUtilities`); build/test tooling (esbuild, vite,
  vitest, eslint, prettier, sass).
