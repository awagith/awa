// ========================================
// ASSET MINIFICATION & BUNDLING CONFIGURATION
// Magento 2 Performance Optimization
// ========================================

module.exports = {
  // CSS Minification Configuration
  cssMinification: {
    enabled: true,
    minify: true,
    sourceMap: false,
    inline: false,
    bundling: {
      enabled: true,
      bundles: {
        'critical': [
          'css/source/performance/_critical_css.less',
          'css/source/_theme/_color_system_fixed.less',
          'css/source/_header/_main_fixed.less'
        ],
        'main': [
          'css/source/_styles_main_fixed.less'
        ],
        'products': [
          'css/source/_products/_grid_fixed.less',
          'css/source/_products/_card.less'
        ],
        'performance': [
          'css/source/performance/_lazy_loading.less',
          'css/source/performance/_core_web_vitals.less',
          'css/source/performance/_image_optimization.less'
        ]
      }
    }
  },
  
  // JavaScript Minification Configuration
  jsMinification: {
    enabled: true,
    minify: true,
    sourceMap: false,
    bundling: {
      enabled: true,
      bundles: {
        'critical': [
          'js/critical.js',
          'js/lazy-loading.js',
          'js/core-vitals.js'
        ],
        'main': [
          'js/main.js',
          'js/navigation.js',
          'js/product-grid.js'
        ],
        'vendor': [
          'js/vendor/jquery.min.js',
          'js/vendor/bootstrap.min.js'
        ]
      }
    }
  },
  
  // Asset Optimization
  optimization: {
    removeComments: true,
    removeWhitespace: true,
    mergeIdenticalSelectors: true,
    reduceSelectors: true,
    optimizeUrls: true,
    autoprefixer: true,
    purgecss: {
      enabled: true,
      content: ['./**/*.html', './**/*.phtml'],
      defaultExtractor: content => content.match(/[\w-/:]+(?<!:)/g) || [],
      whitelistPatterns: [/^lazy-/, /^critical-/, /^performance-/]
    }
  },
  
  // Compression Settings
  compression: {
    gzip: true,
    brotli: true,
    level: 9,
    threshold: 1024
  },
  
  // Cache Busting
  cacheBusting: {
    enabled: true,
    strategy: 'hash'
  },
  
  // Critical CSS Extraction
  criticalCSS: {
    enabled: true,
    aboveTheFold: {
      minify: true,
      extract: true,
      inline: true,
      dimensions: [
        { width: 375, height: 667 }, // Mobile
        { width: 768, height: 1024 }, // Tablet
        { width: 1920, height: 1080 } // Desktop
      ]
    }
  },
  
  // Bundle Splitting
  bundleSplitting: {
    enabled: true,
    strategy: 'experience',
    chunks: 'all',
    maxSize: 244 * 1024, // 244KB
    minSize: 20 * 1024   // 20KB
  },
  
  // Tree Shaking
  treeShaking: {
    enabled: true,
    mode: 'strict'
  },
  
  // Asset Hints
  assetHints: {
    preload: ['critical.css', 'critical.js'],
    prefetch: ['main.css', 'main.js'],
    preconnect: ['https://fonts.googleapis.com', 'https://www.google-analytics.com']
  }
};