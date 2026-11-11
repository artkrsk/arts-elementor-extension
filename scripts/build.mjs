// Builds the editor's live-settings bridge into the PHP tree, where Managers/Editor.php enqueues it.
// The widget handler beside it (libraries/arts-elementor-widget-handler) is written by hand and not built.
import { build } from 'esbuild'
import { dirname, resolve } from 'node:path'
import { fileURLToPath } from 'node:url'

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..')

await build({
  entryPoints: [resolve(root, 'src/ts/index.ts')],
  outfile: resolve(root, 'src/php/libraries/arts-elementor-extension/index.umd.js'),
  bundle: true,
  minify: true,
  format: 'iife',
  target: 'es2022',
  logLevel: 'warning'
})
