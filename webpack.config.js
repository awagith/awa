const path = require('path');
const TerserPlugin = require('terser-webpack-plugin');

module.exports = {
  mode: 'production',
  
  entry: {
    // Critical path only - carregado imediatamente
    'critical': './app/design/frontend/ayo/ayo_default/web/js/src/critical.js',
    
    // Lazy loaded bundles - carregados sob demanda
    'product-detail': './app/design/frontend/ayo/ayo_default/web/js/src/product-detail.js',
    'product-grid': './app/design/frontend/ayo/ayo_default/web/js/src/product-grid.js',
    'modal-core': './app/design/frontend/ayo/ayo_default/web/js/src/modal-core.js',
    'form-components': './app/design/frontend/ayo/ayo_default/web/js/src/form-components.js'
  },
  
  output: {
    filename: '[name].min.js',
    chunkFilename: '[name].[contenthash:8].chunk.js',
    path: path.resolve(__dirname, 'app/design/frontend/ayo/ayo_default/web/js/dist'),
    publicPath: '/static/frontend/ayo/ayo_default/pt_BR/js/dist/',
    clean: true
  },
  
  optimization: {
    minimize: true,
    minimizer: [
      new TerserPlugin({
        terserOptions: {
          compress: {
            drop_console: true,
            drop_debugger: true,
            pure_funcs: ['console.log', 'console.info', 'console.debug'],
            passes: 3, // Mais passes para melhor compressão
            unsafe: true, // Otimizações mais agressivas
            unsafe_comps: true,
            unsafe_math: true,
            unsafe_methods: true
          },
          mangle: {
            safari10: true,
            properties: {
              regex: /^_/ // Mangle propriedades privadas
            }
          },
          format: {
            comments: false,
            ascii_only: true // Melhor compatibilidade
          }
        },
        extractComments: false,
        parallel: true // Paralelização para build mais rápido
      })
    ],
    
    splitChunks: {
      chunks: 'all',
      maxInitialRequests: 5,
      maxAsyncRequests: 5,
      minSize: 2000,
      maxSize: 100000, // Evitar chunks muito grandes
      
      cacheGroups: {
        // Vendor code compartilhado
        vendor: {
          test: /[\\/]node_modules[\\/]/,
          name: 'vendor-common',
          priority: 10,
          reuseExistingChunk: true,
          enforce: true,
          minChunks: 1
        },
        
        // Common code entre bundles
        common: {
          minChunks: 2,
          priority: 5,
          reuseExistingChunk: true,
          name: 'common',
          minSize: 2000,
          enforce: true
        },
        
        // Utils compartilhados
        utils: {
          test: /[\\/]utils[\\/]/,
          name: 'utils',
          priority: 8,
          reuseExistingChunk: true,
          minSize: 1000
        }
      }
    },
    
    runtimeChunk: {
      name: 'runtime'
    },
    
    // Tree shaking mais agressivo
    usedExports: true,
    sideEffects: false
  },
  
  module: {
    rules: [
      {
        test: /\.js$/,
        exclude: /node_modules/,
        use: {
          loader: 'babel-loader',
          options: {
            presets: [
              ['@babel/preset-env', {
                targets: {
                  browsers: ['> 1%', 'last 2 versions', 'not dead']
                },
                modules: false,
                useBuiltIns: 'usage',
                corejs: 3
              }]
            ],
            cacheDirectory: true
          }
        }
      }
    ]
  },
  
  resolve: {
    extensions: ['.js'],
    alias: {
      '@modules': path.resolve(__dirname, 'app/design/frontend/ayo/ayo_default/web/js/modules'),
      '@utils': path.resolve(__dirname, 'app/design/frontend/ayo/ayo_default/web/js/utils')
    }
  },
  
  performance: {
    hints: 'warning',
    maxEntrypointSize: 250000, // 250KB warning (bundle total)
    maxAssetSize: 500000, // 500KB warning (asset individual)
    assetFilter: function(assetFilename) {
      // Não avisar sobre source maps
      return !assetFilename.endsWith('.map');
    }
  },
  
  stats: {
    colors: true,
    hash: false,
    version: false,
    timings: true,
    assets: true,
    chunks: false,
    modules: false,
    reasons: false,
    children: false,
    source: false,
    errors: true,
    errorDetails: true,
    warnings: true,
    publicPath: false
  }
};
