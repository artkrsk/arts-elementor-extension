import eslint from '@eslint/js'
import tseslint from 'typescript-eslint'
import prettier from 'eslint-config-prettier'
import globals from 'globals'

// Common globals for Elementor environment
const elementorGlobals = {
	$e: 'readonly',
	elementor: 'readonly',
	elementorCommon: 'readonly',
	jQuery: 'readonly',
	Backbone: 'readonly',
	_: 'readonly',
	artsElementorExtensionEditorLiveSettings: 'readonly'
}

export default [
	eslint.configs.recommended,
	...tseslint.configs.recommended,
	prettier,
	{
		ignores: [
			'node_modules/',
			'dist/',
			'coverage/',
			'__e2e__/',
			'__tests__/',
			'vendor/',
			
			'src/php'
		]
	},
	// Configuration for build/config files
	{
		files: [
			'*.config.js',
			'config/**/*.js',
			'vite.config.js',
			'__builder__/**/*.js',
			'__build__/**/*.js'
		],
		languageOptions: {
			ecmaVersion: 2022,
			sourceType: 'module',
			globals: {
				...globals.node, // Node.js environment globals
				...elementorGlobals // Elementor-specific globals
			}
		},
		rules: {
			'no-console': 'off'
		}
	},
	// Configuration for TypeScript files
	{
		files: ['src/ts/**/*.ts'],
		languageOptions: {
			ecmaVersion: 2022,
			sourceType: 'module',
			globals: {
				...globals.browser, // Browser globals
				...elementorGlobals, // Elementor-specific globals
				window: 'readonly',
				document: 'readonly',
				CustomEvent: 'readonly'
			}
		},
		rules: {
			'no-unused-vars': 'warn',
			'no-console': ['warn', { allow: ['warn', 'error'] }]
		}
	}
]
