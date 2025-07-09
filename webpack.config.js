const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

module.exports = {
	...defaultConfig,
	entry: {
		// Admin scripts
		'admin/index': path.resolve( process.cwd(), 'src/admin/index.js' ),
		'admin/template-preview': path.resolve(
			process.cwd(),
			'src/admin/template-preview.js'
		),
		'admin/onboarding': path.resolve(
			process.cwd(),
			'src/admin/onboarding/index.js'
		),
		// Editor scripts (main entry for Gutenberg)
		editor: path.resolve( process.cwd(), 'src/editor/index.js' ),
		// Blocks
		'blocks/ai-layout/index': path.resolve(
			process.cwd(),
			'src/blocks/ai-layout/index.js'
		),
		// Public scripts
		'public/index': path.resolve( process.cwd(), 'src/public/index.js' ),
	},
	output: {
		...defaultConfig.output,
		path: path.resolve( process.cwd(), 'build' ),
		filename: '[name].js',
	},
	resolve: {
		...defaultConfig.resolve,
		alias: {
			...defaultConfig.resolve.alias,
			'@components': path.resolve( process.cwd(), 'src/components' ),
			'@utils': path.resolve( process.cwd(), 'src/utils' ),
			'@hooks': path.resolve( process.cwd(), 'src/hooks' ),
			'@admin': path.resolve( process.cwd(), 'src/admin' ),
			'@blocks': path.resolve( process.cwd(), 'src/blocks' ),
			'@editor': path.resolve( process.cwd(), 'src/editor' ),
		},
	},
	// Add TypeScript support
	module: {
		...defaultConfig.module,
		rules: [
			...defaultConfig.module.rules,
			{
				test: /\.tsx?$/,
				use: [
					{
						loader: 'ts-loader',
						options: {
							transpileOnly: true,
						},
					},
				],
				exclude: /node_modules/,
			},
		],
	},
	// Optimize for production
	optimization: {
		...defaultConfig.optimization,
		splitChunks: {
			cacheGroups: {
				wordpress: {
					test: /[\\/]node_modules[\\/]@wordpress[\\/]/,
					name: 'wordpress-vendor',
					priority: 10,
					reuseExistingChunk: true,
				},
				vendor: {
					test: /[\\/]node_modules[\\/]/,
					name: 'vendor',
					priority: 5,
				},
			},
		},
	},
};
