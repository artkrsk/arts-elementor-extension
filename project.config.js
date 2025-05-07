/**
 * Project Configuration for `@arts/elementor-extension`
 * Library for creating Elementor extensions, widgets, skins, tabs, and more in declarative way
 */
export default {
  // Basic project information
  name: 'Arts Elementor Extension',
  entry: './src/js/index.js',
  author: 'Artem Semkin',
  license: 'MIT',
  description:
    'Library for creating Elementor extensions, widgets, skins, tabs, and more in declarative way',
  homepage: 'https://artemsemkin.com',
  repository: 'https://github.com/artkrsk/arts-elementor-extension',
  donateUrl: 'https://buymeacoffee.com/artemsemkin',

  // Path configuration
  paths: {
    root: './',
    src: './src',
    dist: './dist',
    php: './src/php',
    styles: './src/styles',
    js: './src/js',
    wordpress: {
      plugin: './src/wordpress-plugin',
      languages: './src/php/languages'
    },
    library: {
      base: 'libraries',
      name: 'arts-elementor-extension',
      assets: 'src/php/libraries/arts-elementor-extension'
      // assets: '/Users/art/Projects/Framework/packages/ArtsSmoothScrolling/vendor/arts/elementor-extension/src/php/libraries/arts-elementor-extension'
    },
    aliases: {
      '@': './src/js',
      '@core': './src/js/core',
      '@controls': './src/js/controls',
      '@hooks': './src/js/hooks',
      '@services': './src/js/services'
    }
  },

  // Development configuration
  dev: {
    root: './src/js/www',
    server: {
      port: 8080,
      host: 'localhost'
    }
  },

  // Live reloading server configuration
  liveReload: {
    enabled: true,
    port: 3000,
    host: 'localhost',
    https: {
      key: '/Users/art/.localhost-ssl/smooth-scrolling.local+4-key.pem',
      cert: '/Users/art/.localhost-ssl/smooth-scrolling.local+4.pem'
    },
    injectChanges: true,
    reloadDebounce: 500,
    reloadThrottle: 1000,
    notify: {
      styles: {
        top: 'auto',
        bottom: '0',
        right: '0',
        left: 'auto',
        padding: '5px',
        borderRadius: '5px 0 0 0',
        fontSize: '12px'
      }
    },
    ghostMode: {
      clicks: false,
      forms: false,
      scroll: false
    },
    open: false,
    snippet: false
  },

  // WordPress sync configuration
  wordpress: {
    enabled: true,
    source: './src/php',
    extensions: ['.js', '.css', '.php', '.jsx', '.ts', '.tsx'],
    targets: [], // Targets will be added by the build system based on environment
    debug: false
  },

  // WordPress plugin development configuration
  wordpressPlugin: {
    enabled: false,
    source: './src/wordpress-plugin',
    extensions: ['.php', '.js', '.css', '.jsx', '.ts', '.tsx', '.json', '.txt', '.md'],
    target: null, // Set in the environment-specific config
    debug: false,
    vendor: {
      source: './vendor',
      target: 'vendor',
      extensions: ['.php', '.js', '.css', '.json', '.txt', '.md'],
      delete: true,
      watch: true
    },
    packageName: 'arts-elementor-extension',
    zipOutputName: 'arts-elementor-extension.zip',
    packageExclude: [
      'node_modules',
      '.git',
      '.DS_Store',
      '**/.DS_Store',
      '.*',
      '**/.*',
      '*.log',
      '*.map',
      '*.zip',
      'package.json',
      'package-lock.json',
      'pnpm-lock.yaml',
      'yarn.lock',
      'README.md',
      'LICENSE',
      '.gitignore',
      '.editorconfig',
      '.eslintrc',
      '.prettierrc',
      'tsconfig.json',
      'vite.config.js',
      'vitest.config.js',
      'cypress.config.js',
      '__tests__',
      '__e2e__',
      'coverage',
      'dist'
    ],
    sourceFiles: {
      php: './src/php',
      vendor: './vendor',
      dist: {
        files: ['index.umd.js', 'index.css']
      },
      composer: ['composer.json', 'composer.lock']
    }
  },

  // Build configuration
  build: {
    formats: ['esm', 'iife'],
    target: 'es2018',
    sourcemap: false,
    createDistFolder: false, // Option to disable dist folder creation
    externals: {
      jquery: 'jQuery',
      elementor: 'elementor',
      backbone: 'Backbone'
    },
    globals: {
      jquery: 'jQuery',
      elementor: 'elementor',
      backbone: 'Backbone'
    },
    cleanOutputDir: true,
    umd: {
      name: 'ArtsElementorExtension',
      exports: 'named',
      globals: {
        jquery: 'jQuery',
        elementor: 'elementor',
        backbone: 'Backbone'
      }
    },
    // Output filenames by format
    output: {
      cjs: 'index.cjs',
      iife: 'index.iife.js'
    }
  },

  // Sass configuration
  sass: {
    enabled: true,
    entry: './src/styles/index.sass',
    output: './dist/index.css',
    options: {
      sourceMap: false,
      outputStyle: 'compressed',
      includePaths: ['node_modules']
    },
    // Direct library output path for compiled CSS
    libraryOutput: './src/php/libraries/arts-elementor-extension/index.css'
  },

  // Watch options
  watch: {
    ignored: ['**/node_modules/**', '**/dist/**', '**/.*', '**/.*/**']
  },

  // Internationalization options
  i18n: {
    enabled: true,
    src: 'src/php/**/*.php',
    dest: 'src/php/languages/arts-elementor-extension.pot',
    domain: 'arts-elementor-extension',
    package: 'Arts Elementor Extension',
    bugReport: 'https://artemsemkin.com',
    lastTranslator: 'Artem Semkin',
    team: 'Artem Semkin',
    relativeTo: './'
  }
}
