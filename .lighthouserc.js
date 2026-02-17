/**
 * Lighthouse CI Configuration - Performance Budgets
 * AWA Motos - Tema Ayo NextSky
 * Baseado em: Web.dev Performance Best Practices
 */

module.exports = {
  ci: {
    collect: {
      url: [
        'http://localhost/',
        'http://localhost/catalog/category/view/id/1',
        'http://localhost/catalog/product/view/id/1',
      ],
      numberOfRuns: 3,
      settings: {
        chromeFlags: '--no-sandbox --disable-gpu',
      },
    },
    assert: {
      assertions: {
        // Performance Budgets
        'categories:performance': ['error', { minScore: 0.85 }],
        'categories:accessibility': ['error', { minScore: 0.90 }],
        'categories:best-practices': ['error', { minScore: 0.85 }],
        'categories:seo': ['error', { minScore: 0.85 }],
        
        // Core Web Vitals
        'first-contentful-paint': ['error', { maxNumericValue: 2000 }], // 2s
        'largest-contentful-paint': ['error', { maxNumericValue: 2800 }], // 2.8s
        'total-blocking-time': ['error', { maxNumericValue: 300 }], // 300ms
        'cumulative-layout-shift': ['error', { maxNumericValue: 0.1 }],
        'speed-index': ['error', { maxNumericValue: 3000 }], // 3s
        
        // Resource Sizes
        'resource-summary:script:size': ['error', { maxNumericValue: 500000 }], // 500KB
        'resource-summary:stylesheet:size': ['error', { maxNumericValue: 300000 }], // 300KB
        'resource-summary:image:size': ['warn', { maxNumericValue: 2000000 }], // 2MB
        
        // Network
        'uses-optimized-images': 'warn',
        'uses-text-compression': 'error',
        'uses-responsive-images': 'warn',
        'modern-image-formats': 'warn',
        
        // CSS
        'unused-css-rules': ['warn', { maxLength: 50 }], // Máximo 50 seletores não usados
        'render-blocking-resources': ['error', { maxLength: 2 }], // Máximo 2 recursos bloqueantes
        
        // JavaScript
        'unused-javascript': ['warn', { maxLength: 50 }], // Máximo 50KB não usado
        'bootup-time': ['error', { maxNumericValue: 1000 }], // 1s
        'mainthread-work-breakdown': ['error', { maxNumericValue: 2000 }], // 2s
        
        // Fonts
        'font-display': 'error',
        'preload-lcp-image': 'warn',
        
        // Caching
        'uses-long-cache-ttl': 'warn',
        
        // Third-party
        'third-party-summary': 'warn',
        'third-party-facades': 'warn',
      },
    },
    upload: {
      target: 'temporary-public-storage',
    },
  },
};
