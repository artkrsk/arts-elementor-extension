import webpack from 'webpack'
import TerserPlugin from 'terser-webpack-plugin'
import fs from 'fs-extra'
import path from 'path'
import chokidar from 'chokidar'
import debounce from 'debounce'
import { logger } from '../../logger/index.js'
import { generateBanner } from '../common/banner.js'
import { getPackageMetadata } from '../common/version.js'
import { getLibraryDir } from '../common/paths.js'
import { isDevelopment, getConfigValue } from '../../config/index.js'

// Define a temporary directory for Webpack builds
const getWebpackTempOutputDir = (config) => {
  return path.join(config._absoluteProjectRoot, '__build__', 'temp_webpack_build')
}

/**
 * Compile TypeScript files using Webpack
 * @param {Object} config - Project configuration
 * @param {boolean} watchMode - Whether to run in watch mode
 * @param {Object} liveReloadServer - Live reload server instance (optional)
 * @returns {Promise<void>}
 */
export async function compileTypeScript(config, watchMode = false, liveReloadServer = null) {
  logger.info(`🔧 Compiling TypeScript with Webpack (${watchMode ? 'watch' : 'build'} mode)...`)

  const isDev = isDevelopment(config)
  const packageMetadata = await getPackageMetadata(config)
  const webpackTempOutputDir = getWebpackTempOutputDir(config)
  const finalOutputDir = getLibraryDir(config, isDev)

  try {
    // Get entry point from config
    const entryPoint =
      getConfigValue(config, 'ts.entry', null) ||
      getConfigValue(config, 'entry', './src/js/index.js').replace(
        new RegExp(`${getConfigValue(config, 'ts.jsExtension', '.js')}$`),
        getConfigValue(config, 'ts.extension', '.ts')
      )
    const entryPointPath = path.resolve(config._absoluteProjectRoot, entryPoint)

    // Get tsconfig path from config or use default
    const tsconfigPath = getConfigValue(config, 'ts.tsconfigPath', 'tsconfig.json')
    const tsconfigFullPath = path.isAbsolute(tsconfigPath)
      ? tsconfigPath
      : path.join(config._absoluteProjectRoot, tsconfigPath)

    // Webpack Configuration
    const webpackConfig = {
      mode: isDev ? 'development' : 'production',
      entry: entryPointPath,
      output: {
        path: webpackTempOutputDir,
        filename: 'index.umd.js',
        chunkFilename: 'chunk.[name].js',
        library: {
          name: config.build.umd.name,
          type: 'umd',
          export: 'default'
        },
        globalObject: 'this',
        clean: true
      },
      devtool: isDev ? 'source-map' : false,
      target: ['web', 'es2018'],
      module: {
        rules: [
          {
            test: /\.tsx?$/,
            loader: 'ts-loader',
            exclude: /node_modules/,
            options: {
              ...((await fs.pathExists(tsconfigFullPath)) ? { configFile: tsconfigFullPath } : {})
            }
          }
        ]
      },
      resolve: {
        extensions: ['.tsx', '.ts', '.js']
      },
      externals: config.build.externals || {},
      optimization: {
        minimize: !isDev,
        minimizer: [
          new TerserPlugin({
            terserOptions: {
              compress: {
                drop_console: !isDev
              },
              format: {
                comments: false
              }
            },
            extractComments: false
          })
        ]
      },
      plugins: [
        new webpack.BannerPlugin({
          banner: generateBanner(packageMetadata),
          raw: true
        })
      ],
      ...getConfigValue(config, 'ts.webpackOptions', {})
    }

    const compiler = webpack(webpackConfig)

    const handleBuildComplete = async (err, stats) => {
      if (err) {
        logger.error('❌ Webpack fatal error:', err.stack || err)
        if (err.details) {
          logger.error(err.details)
        }
        return
      }

      const info = stats.toJson()

      if (stats.hasErrors()) {
        logger.error('❌ Webpack compilation failed with errors:')
        info.errors.forEach((e) => logger.error(e.message || e))
      } else {
        if (stats.hasWarnings()) {
          logger.warn('⚠️ Webpack compilation finished with warnings:')
          info.warnings.forEach((w) => logger.warn(w.message || w))
        }
        logger.success(`✅ Webpack build completed in ${stats.endTime - stats.startTime}ms`)

        try {
          await syncWebpackOutputToLibrary(webpackTempOutputDir, finalOutputDir, isDev)
          logger.success('🎉 TypeScript compilation and sync completed')

          // Notify live reload server after files are synced
          if (liveReloadServer) {
            const mainJsFile = path.join(finalOutputDir, 'index.umd.js')
            liveReloadServer.notifyChange(mainJsFile)
          }
        } catch (syncError) {
          logger.error('❌ Syncing Webpack output failed:', syncError)
        }
      }
    }

    if (watchMode) {
      logger.info('👀 Starting Webpack watch...')
      return compiler.watch(
        {
          aggregateTimeout: getConfigValue(config, 'ts.watchAggregateTimeout', 300),
          ignored: getConfigValue(config, 'watch.ignored', ['**/node_modules/**'])
        },
        handleBuildComplete
      )
    } else {
      // Run single build
      return new Promise((resolve, reject) => {
        compiler.run((err, stats) => {
          handleBuildComplete(err, stats)
          compiler.close((closeErr) => {
            if (closeErr) {
              logger.error('❌ Error closing Webpack compiler:', closeErr)
            }
            if (err || stats.hasErrors()) {
              reject(new Error('Webpack build failed.'))
            } else {
              resolve()
            }
          })
        })
      })
    }
  } catch (error) {
    logger.error('❌ Error setting up Webpack configuration:', error)
    throw error
  }
}

/**
 * Sync Webpack build output from temporary dir to the final library directory.
 * @param {string} sourceDir - Temporary Webpack output directory
 * @param {string} targetDir - Final library directory
 * @param {boolean} isDev - Whether this is a development build (for sourcemaps)
 * @returns {Promise<void>}
 */
async function syncWebpackOutputToLibrary(sourceDir, targetDir, isDev) {
  logger.info(`📂 Syncing build output to ${targetDir}...`)

  try {
    await fs.ensureDir(targetDir)
    const items = await fs.readdir(sourceDir)

    for (const item of items) {
      const sourcePath = path.join(sourceDir, item)
      const targetPath = path.join(targetDir, item)
      const stats = await fs.stat(sourcePath)

      if (stats.isDirectory()) {
        await fs.copy(sourcePath, targetPath, { overwrite: true })
      } else if (stats.isFile()) {
        await fs.copyFile(sourcePath, targetPath)

        // Copy corresponding sourcemap in development mode
        const sourceMapPath = `${sourcePath}.map`
        if (isDev && (await fs.pathExists(sourceMapPath))) {
          await fs.copyFile(sourceMapPath, `${targetPath}.map`)
        }
      }
    }

    // Clean up the temporary directory after successful copy
    await fs.remove(sourceDir)

    logger.success(`✅ Sync complete to: ${targetDir}`)
  } catch (error) {
    logger.error(`❌ Failed to sync build output:`, error)
    await fs.remove(sourceDir).catch(() => {})
    throw error
  }
}

/**
 * Watch TypeScript files for changes
 * @param {Object} config - Project configuration
 * @param {Object} liveReloadServer - Live reload server instance
 * @returns {Object} - Watcher instance
 */
export async function watchTypeScript(config, liveReloadServer) {
  const tsDir = path.resolve(getConfigValue(config, 'paths.ts', config.paths.js))
  logger.info(`👀 Watching TypeScript files in ${path.relative(process.cwd(), tsDir)}`)

  const extensions = getConfigValue(config, 'ts.watchExtensions', ['.ts', '.tsx'])
  const filePattern = `**/*.{${extensions.map((ext) => ext.substring(1)).join(',')}}`

  const debouncedBuild = debounce(
    async (filePath) => {
      logger.info(`🔄 TypeScript file changed: ${path.relative(process.cwd(), filePath)}`)

      try {
        await compileTypeScript(config, false, liveReloadServer)
      } catch (error) {
        // Error is already logged in compileTypeScript
      }
    },
    getConfigValue(config, 'ts.debounceTime', 300)
  )

  const watcherOptions = {
    ignored: getConfigValue(config, 'watch.ignored', [
      '**/node_modules/**',
      '**/dist/**',
      '**/__build__/temp_webpack_build/**'
    ]),
    persistent: true,
    ignoreInitial: true,
    ignorePermissionErrors: getConfigValue(config, 'ts.ignorePermissionErrors', true),
    ...getConfigValue(config, 'ts.watcherOptions', {})
  }

  const watcher = chokidar.watch(tsDir, watcherOptions)

  watcher.on('change', debouncedBuild)
  watcher.on('add', debouncedBuild)
  watcher.on('unlink', debouncedBuild)
  watcher.on('error', (error) => {
    logger.error(`❌ TypeScript watcher error:`, error)
  })

  return watcher
}

export default {
  compileTypeScript,
  watchTypeScript
}
