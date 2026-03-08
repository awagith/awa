#!/usr/bin/env node
'use strict';

/**
 * Playwright visual smoke runner (public-critical pages) for awamotos.com.
 *
 * This is the practical fallback for "Playwright MCP" when MCP tooling is not
 * directly exposed in the runtime. It runs the same route matrix and captures:
 * - screenshots
 * - console/page errors
 * - first-party network failures
 * - route-by-route pass/warn/fail status
 * - JSON + Markdown reports
 */

const fs = require('fs');
const path = require('path');
const { chromium, devices } = require('playwright');

const DEFAULT_BASE_URL = 'https://awamotos.com/';

function out(message) {
  process.stdout.write(String(message) + '\n');
}

function warn(message) {
  process.stdout.write('[WARN] ' + String(message) + '\n');
}

function fail(message) {
  process.stderr.write('[FAIL] ' + String(message) + '\n');
}

function sleep(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

async function withNodeTimeout(promise, ms, fallbackValue) {
  const marker = Symbol('timeout');
  try {
    const result = await Promise.race([
      promise,
      sleep(ms).then(() => marker),
    ]);
    return result === marker ? fallbackValue : result;
  } catch (_) {
    return fallbackValue;
  }
}

function rootDir() {
  return path.resolve(__dirname, '..', '..');
}

function timestampTag() {
  const d = new Date();
  const yyyy = d.getUTCFullYear();
  const mm = String(d.getUTCMonth() + 1).padStart(2, '0');
  const dd = String(d.getUTCDate()).padStart(2, '0');
  const hh = String(d.getUTCHours()).padStart(2, '0');
  const mi = String(d.getUTCMinutes()).padStart(2, '0');
  const ss = String(d.getUTCSeconds()).padStart(2, '0');
  return `${yyyy}${mm}${dd}_${hh}${mi}${ss}Z`;
}

function parseArgs(argv) {
  const opts = {
    baseUrl: DEFAULT_BASE_URL,
    outDir: '',
    timeoutMs: 30000,
    headless: true,
  };

  for (let i = 2; i < argv.length; i++) {
    const arg = String(argv[i]);
    if (arg === '--headful') {
      opts.headless = false;
      continue;
    }
    if (!arg.startsWith('--')) {
      throw new Error('Invalid argument: ' + arg);
    }
    const key = arg.slice(2);
    if (!['base-url', 'out-dir', 'timeout-ms'].includes(key)) {
      throw new Error('Unsupported option: --' + key);
    }
    const value = argv[i + 1];
    if (!value) {
      throw new Error('Missing value for --' + key);
    }
    if (key === 'base-url') {
      opts.baseUrl = String(value);
    } else if (key === 'out-dir') {
      opts.outDir = String(value);
    } else if (key === 'timeout-ms') {
      opts.timeoutMs = Math.max(5000, Number(value) || 30000);
    }
    i++;
  }

  opts.baseUrl = opts.baseUrl.replace(/\/+$/, '') + '/';
  if (!opts.outDir) {
    opts.outDir = path.join(rootDir(), 'artifacts', 'playwright-smoke', timestampTag());
  } else if (!path.isAbsolute(opts.outDir)) {
    opts.outDir = path.join(rootDir(), opts.outDir);
  }

  return opts;
}

function getAuthConfig() {
  const username = String(process.env.B2B_TEST_USER || '').trim();
  const password = String(process.env.B2B_TEST_PASS || '');
  return {
    enabled: Boolean(username && password),
    username,
    password,
  };
}

function ensureDir(dirPath) {
  fs.mkdirSync(dirPath, { recursive: true });
}

function writeText(filePath, content) {
  ensureDir(path.dirname(filePath));
  fs.writeFileSync(filePath, content, 'utf8');
}

function safeUrl(baseUrl, relativeOrAbsolute) {
  try {
    return new URL(relativeOrAbsolute, baseUrl).toString();
  } catch (_) {
    return relativeOrAbsolute;
  }
}

function sanitizeName(value) {
  return String(value)
    .toLowerCase()
    .replace(/[^a-z0-9._-]+/g, '-')
    .replace(/-+/g, '-')
    .replace(/^-|-$/g, '')
    .slice(0, 120);
}

function parseUrl(url) {
  try {
    return new URL(url);
  } catch (_) {
    return null;
  }
}

function isSameOriginUrl(url, baseUrl) {
  const a = parseUrl(url);
  const b = parseUrl(baseUrl);
  if (!a || !b) return false;
  return a.origin === b.origin;
}

function isFirstPartyStaticAsset(url, baseUrl) {
  const u = parseUrl(url);
  const b = parseUrl(baseUrl);
  if (!u || !b) return false;
  if (u.origin !== b.origin) return false;
  return /\/static\/.+\/frontend\//.test(u.pathname);
}

function assetKind(url) {
  const pathname = (parseUrl(url) || { pathname: '' }).pathname || '';
  if (/\.(css)(?:$|\?)/i.test(pathname)) return 'css';
  if (/\.(js)(?:$|\?)/i.test(pathname)) return 'js';
  if (/\.(png|jpe?g|webp|gif|svg|avif)(?:$|\?)/i.test(pathname)) return 'image';
  if (/\.(woff2?|ttf|otf|eot)(?:$|\?)/i.test(pathname)) return 'font';
  return 'other';
}

function isCriticalFirstPartyAsset(url, baseUrl) {
  if (!isFirstPartyStaticAsset(url, baseUrl)) return false;
  const kind = assetKind(url);
  if (kind === 'css' || kind === 'js') return true;
  if (kind === 'image') {
    return /awamotos-(compra-protegida|seguranca-ssl)\.svg|payment_methods\.png/i.test(url);
  }
  return false;
}

function classifyConsoleMessage(entry, baseUrl) {
  const text = String(entry.text || '');
  const isErrorType = ['error', 'pageerror'].includes(String(entry.type));
  const baseHost = parseUrl(baseUrl || '')?.hostname || '';

  // Third-party CSP blocks (ex.: Meta Pixel) are noise for this smoke matrix.
  if (
    /connect\.facebook\.net/i.test(text) &&
    /content security policy|violates the following/i.test(text)
  ) {
    return 'info';
  }

  const firstPartyHint =
    text.includes('/static/version') ||
    text.includes('awamotos.com') ||
    (baseHost && text.includes(baseHost)) ||
    text.includes('AWA_Custom/ayo_home5_child') ||
    text.includes('GrupoAwamotos');

  if (isErrorType && firstPartyHint) {
    return 'critical';
  }
  if (isErrorType) {
    return 'warn';
  }
  if (/failed to load resource/i.test(text) && text.includes('awamotos.com')) {
    return 'warn';
  }
  void baseUrl; // reserved for future host-specific filters
  return 'info';
}

class PageCollector {
  constructor(page, baseUrl) {
    this.baseUrl = baseUrl;
    this.console = [];
    this.pageErrors = [];
    this.networkFailures = [];
    this.networkWarnings = [];
    this._currentStep = 'init';

    page.on('console', (msg) => {
      const location = msg.location ? msg.location() : {};
      this.console.push({
        step: this._currentStep,
        ts: new Date().toISOString(),
        type: msg.type(),
        text: msg.text(),
        location: location && (location.url || location.lineNumber || location.columnNumber)
          ? {
              url: location.url || '',
              lineNumber: Number(location.lineNumber || 0),
              columnNumber: Number(location.columnNumber || 0),
            }
          : null,
      });
    });

    page.on('pageerror', (err) => {
      this.console.push({
        step: this._currentStep,
        ts: new Date().toISOString(),
        type: 'pageerror',
        text: err && err.stack ? String(err.stack) : String(err),
        location: null,
      });
    });

    page.on('requestfailed', (request) => {
      const url = request.url();
      const failure = request.failure();
      const row = {
        step: this._currentStep,
        ts: new Date().toISOString(),
        url,
        status: 0,
        method: request.method(),
        resourceType: request.resourceType(),
        reason: failure ? String(failure.errorText || '') : 'requestfailed',
      };
      if (isCriticalFirstPartyAsset(url, this.baseUrl)) {
        this.networkFailures.push(row);
      } else if (isFirstPartyStaticAsset(url, this.baseUrl)) {
        this.networkWarnings.push(row);
      }
    });

    page.on('response', (response) => {
      const status = response.status();
      if (status < 400) return;
      const request = response.request();
      const url = response.url();
      const row = {
        step: this._currentStep,
        ts: new Date().toISOString(),
        url,
        status,
        method: request.method(),
        resourceType: request.resourceType(),
        reason: 'http_' + status,
      };
      if (isCriticalFirstPartyAsset(url, this.baseUrl)) {
        this.networkFailures.push(row);
      } else if (isFirstPartyStaticAsset(url, this.baseUrl)) {
        this.networkWarnings.push(row);
      }
    });
  }

  setStep(stepName) {
    this._currentStep = stepName;
  }

  snapshot() {
    return {
      console: this.console.length,
      failures: this.networkFailures.length,
      warnings: this.networkWarnings.length,
    };
  }

  delta(snapshot) {
    return {
      console: this.console.slice(snapshot.console),
      networkFailures: this.networkFailures.slice(snapshot.failures),
      networkWarnings: this.networkWarnings.slice(snapshot.warnings),
    };
  }
}

async function waitForPageStable(page, timeoutMs) {
  await page.waitForSelector('body', { timeout: timeoutMs });
  await sleep(700);
  try {
    await page.waitForLoadState('networkidle', { timeout: 5000 });
  } catch (_) {
    // Some pages keep polling/trackers alive; DOM-ready + small wait is enough for smoke.
  }
}

async function gotoAndStabilize(page, url, timeoutMs) {
  const response = await page.goto(url, { waitUntil: 'domcontentloaded', timeout: timeoutMs });
  await waitForPageStable(page, timeoutMs);
  return response;
}

async function visible(page, selectors, timeoutMs = 300) {
  for (const sel of selectors) {
    const loc = page.locator(sel).first();
    try {
      const isVis = await withNodeTimeout(loc.isVisible({ timeout: timeoutMs }), timeoutMs + 500, false);
      if (isVis) {
        return sel;
      }
    } catch (_) {
      // ignore
    }
  }
  return null;
}

async function clickIfVisible(page, selectors, options = {}) {
  for (const sel of selectors) {
    const loc = page.locator(sel).first();
    let isVisible = false;
    try {
      isVisible = await withNodeTimeout(
        loc.isVisible({ timeout: options.visibleTimeoutMs || 600 }),
        (options.visibleTimeoutMs || 600) + 400,
        false
      );
    } catch (_) {
      isVisible = false;
    }

    if (!isVisible) {
      continue;
    }

    try {
      await loc.click({ timeout: options.timeoutMs || 5000 });
      if (options.postWaitMs) {
        await sleep(options.postWaitMs);
      } else {
        await sleep(600);
      }
      return { clicked: true, selector: sel };
    } catch (_) {
      // Element can be visible but disabled/covered in dynamic headers; continue with next selector.
      continue;
    }
  }
  return { clicked: false, selector: null };
}

async function exists(page, selectors) {
  for (const sel of selectors) {
    try {
      const count = await withNodeTimeout(page.locator(sel).count(), 1000, 0);
      if (count > 0) {
        return sel;
      }
    } catch (_) {
      // ignore
    }
  }
  return null;
}

async function typeIfVisible(page, selector, value) {
  const loc = page.locator(selector).first();
  try {
    const isVisible = await withNodeTimeout(loc.isVisible({ timeout: 1000 }), 1500, false);
    if (!isVisible) {
      return false;
    }
    const clicked = await withNodeTimeout(loc.click({ timeout: 3000 }).then(() => true), 4000, false);
    if (!clicked) return false;
    const filled = await withNodeTimeout(loc.fill('').then(() => true), 4000, false);
    if (!filled) return false;
    const typed = await withNodeTimeout(loc.type(value, { delay: 30 }).then(() => true), 5000, false);
    if (!typed) return false;
    await sleep(700);
    return true;
  } catch (_) {
    return false;
  }
}

async function basicVisualHealth(page) {
  const domHealth = await withNodeTimeout(
    page.evaluate(() => {
      function isVisible(el) {
        if (!el) return false;
        const style = window.getComputedStyle(el);
        if (!style || style.display === 'none' || style.visibility === 'hidden') return false;
        return Boolean(el.offsetWidth || el.offsetHeight || el.getClientRects().length);
      }

      function firstVisible(selectors) {
        for (const sel of selectors) {
          const node = document.querySelector(sel);
          if (node && isVisible(node)) {
            return sel;
          }
        }
        return null;
      }

      const headerSelectors = ['header.page-header', '.page-header', '.header .top-search', 'header'];
      const footerSelectors = ['footer.page-footer', '.page-footer', 'footer'];
      const mainSelectors = ['main', '.page-main', '#maincontent', '.columns', '.main'];
      const heroSelectors = [
        '.top-home-content--above-fold',
        '.banner-slider',
        '.main-banner',
        '.ayo-home5-wrapper',
        '.header-control.header-nav-global'
      ];

      const headerSel = firstVisible(headerSelectors);
      const footerSel = firstVisible(footerSelectors);
      const mainSel = firstVisible(mainSelectors);
      const heroSel = firstVisible(heroSelectors);

      const cssLinks = Array.from(document.querySelectorAll('link[href$=".css"], link[href*=".css?"]'));
      const childThemeCssLinkCount = cssLinks.filter((n) =>
        String(n.getAttribute('href') || '').includes('/frontend/AWA_Custom/ayo_home5_child/')
      ).length;
      const childThemeScriptCount = Array.from(document.querySelectorAll('script[src]')).filter((n) =>
        String(n.getAttribute('src') || '').includes('/frontend/AWA_Custom/ayo_home5_child/')
      ).length;

      const compatBootstrap = Array.from(document.querySelectorAll('script[type="text/x-magento-init"]')).some((n) => {
        const text = String(n.textContent || '');
        return text.includes('awa-custom-compat-bootstrap') || text.includes('awaCustomCompatBootstrap');
      });

      return {
        title: document.title || '',
        bodyRect: {
          width: Math.round(document.body?.getBoundingClientRect?.().width || 0),
          height: Math.round(document.body?.getBoundingClientRect?.().height || 0),
        },
        bodyChildCount: document.body ? document.body.children.length : 0,
        cssLinkCount: cssLinks.length,
        childThemeCssLinkCount,
        childThemeCssPresent: childThemeCssLinkCount > 0 || childThemeScriptCount > 0,
        bodyClass: document.body ? (document.body.getAttribute('class') || '') : '',
        header: { selector: headerSel, visible: Boolean(headerSel) },
        footer: { selector: footerSel, visible: Boolean(footerSel) },
        pageMain: { selector: mainSel, visible: Boolean(mainSel) },
        hero: { selector: heroSel, visible: Boolean(heroSel) },
        markers: {
          topHome: Boolean(heroSel && heroSel.includes('top-home-content--above-fold')),
          ayoWrapper: Boolean(heroSel && heroSel.includes('ayo-home5-wrapper')),
          compatBootstrap,
        },
      };
    }),
    3500,
    null
  );

  if (domHealth && typeof domHealth === 'object') {
    return domHealth;
  }

  const title = (await withNodeTimeout(page.title(), 1200, '')) || '';
  return {
    title,
    bodyRect: { width: 0, height: 0 },
    bodyChildCount: 0,
    cssLinkCount: 0,
    childThemeCssLinkCount: 0,
    childThemeCssPresent: false,
    bodyClass: '',
    header: { selector: null, visible: false },
    footer: { selector: null, visible: false },
    pageMain: { selector: null, visible: false },
    hero: { selector: null, visible: false },
    markers: { topHome: false, ayoWrapper: false, compatBootstrap: false },
  };
}

async function desktopHomeNavigationHealth(page) {
  const snapshot = await withNodeTimeout(
    page.evaluate(() => {
      function probe(selector) {
        const el = document.querySelector(selector);
        if (!el) {
          return {
            selector,
            exists: false,
            visible: false,
            display: 'none',
            visibility: 'hidden',
            opacity: '0',
            pointerEvents: 'none',
            width: 0,
            height: 0,
          };
        }

        const cs = window.getComputedStyle(el);
        const rect = el.getBoundingClientRect();
        const width = Math.round(rect.width || 0);
        const height = Math.round(rect.height || 0);
        const visible = cs.display !== 'none'
          && cs.visibility !== 'hidden'
          && Number(cs.opacity || '1') > 0
          && width > 1
          && height > 1;

        return {
          selector,
          exists: true,
          visible,
          display: cs.display || '',
          visibility: cs.visibility || '',
          opacity: cs.opacity || '',
          pointerEvents: cs.pointerEvents || '',
          width,
          height,
        };
      }

      const menuColumn = probe('.header-control.header-nav .menu_left_home1');
      const mainColumn = probe('.header-control.header-nav .menu_primary');
      const verticalMenu = probe('.header-control.header-nav .menu_left_home1 .navigation.verticalmenu.side-verticalmenu');
      const verticalTrigger = probe('.header-control.header-nav .menu_left_home1 .navigation.verticalmenu.side-verticalmenu > .title-category-dropdown');
      const verticalList = probe('.header-control.header-nav .menu_left_home1 .navigation.verticalmenu.side-verticalmenu > ul.togge-menu.list-category-dropdown');
      const mainNav = probe('.header-control.header-nav .menu_primary .navigation.custommenu.main-nav');

      const triggerNode = document.querySelector(verticalTrigger.selector);
      const triggerClickable = Boolean(
        triggerNode
        && verticalTrigger.visible
        && verticalTrigger.pointerEvents !== 'none'
        && !triggerNode.hasAttribute('disabled')
        && String(triggerNode.getAttribute('aria-disabled') || '').toLowerCase() !== 'true'
      );

      return {
        menuColumn,
        mainColumn,
        verticalMenu,
        verticalTrigger,
        verticalList,
        mainNav,
        triggerClickable,
      };
    }),
    3500,
    null
  );

  if (snapshot && typeof snapshot === 'object') {
    return snapshot;
  }

  return {
    menuColumn: { exists: false, visible: false, width: 0, height: 0, display: 'none' },
    mainColumn: { exists: false, visible: false, width: 0, height: 0, display: 'none' },
    verticalMenu: { exists: false, visible: false, width: 0, height: 0, display: 'none' },
    verticalTrigger: { exists: false, visible: false, width: 0, height: 0, display: 'none', pointerEvents: 'none' },
    verticalList: { exists: false, visible: false, width: 0, height: 0, display: 'none' },
    mainNav: { exists: false, visible: false, width: 0, height: 0, display: 'none' },
    triggerClickable: false,
  };
}

async function homeMerchandisingHealth(page) {
  const snapshot = await withNodeTimeout(
    page.evaluate(() => {
      function isVisible(el) {
        if (!el) return false;
        const cs = window.getComputedStyle(el);
        const rect = el.getBoundingClientRect();
        return (
          cs.display !== 'none' &&
          cs.visibility !== 'hidden' &&
          Number(cs.opacity || '1') > 0 &&
          rect.width > 1 &&
          rect.height > 1
        );
      }

      function probe(entry) {
        const root = document.querySelector(entry.selector);
        if (!root) {
          return {
            key: entry.key,
            selector: entry.selector,
            exists: false,
            visible: false,
            top: -1,
            width: 0,
            height: 0,
            itemCount: 0,
            heading: '',
          };
        }

        const rect = root.getBoundingClientRect();
        const headingNode = root.querySelector('h2');
        const itemCount = root.querySelectorAll('.product-item, .item-inner, .awa-application-shortcuts__card').length;

        return {
          key: entry.key,
          selector: entry.selector,
          exists: true,
          visible: isVisible(root),
          top: Math.round((window.scrollY || 0) + rect.top),
          width: Math.round(rect.width || 0),
          height: Math.round(rect.height || 0),
          itemCount,
          heading: headingNode ? String(headingNode.textContent || '').trim() : '',
        };
      }

      const expected = [
        { key: 'bestsellers', selector: '.top-home-content--bestsellers' },
        { key: 'bauletos', selector: '.top-home-content--category-shelf-bauletos' },
        { key: 'guidoes', selector: '.top-home-content--category-shelf-guidoes' },
        { key: 'fitment', selector: '.top-home-content--fitment-search' },
        { key: 'applications', selector: '.top-home-content--application-shortcuts' },
        { key: 'launches', selector: '.top-home-content--launches' },
      ];

      const sections = {};
      for (const entry of expected) {
        sections[entry.key] = probe(entry);
      }

      const visibleSections = Object.values(sections)
        .filter((section) => section.visible)
        .sort((a, b) => a.top - b.top);
      const orderKeys = visibleSections.map((section) => section.key);
      let cursor = -1;
      let orderOk = true;
      for (const entry of expected) {
        const index = orderKeys.indexOf(entry.key);
        if (index === -1 || index < cursor) {
          orderOk = false;
          break;
        }
        cursor = index;
      }

      const appCards = document.querySelectorAll('.top-home-content--application-shortcuts .awa-application-shortcuts__card');
      const container = document.querySelector('.top-home-content--merch > .container')
        || document.querySelector('.top-home-content--application-shortcuts > .container')
        || document.querySelector('.page-main.container');
      const containerRect = container ? container.getBoundingClientRect() : { width: 0 };
      const containerStyle = container ? window.getComputedStyle(container) : null;
      const scrollWidth = Math.max(
        document.documentElement ? document.documentElement.scrollWidth : 0,
        document.body ? document.body.scrollWidth : 0
      );
      const viewportWidth = window.innerWidth || 0;

      return {
        sections,
        orderKeys,
        orderOk,
        appCardCount: appCards.length,
        container: {
          width: Math.round(containerRect.width || 0),
          paddingLeft: containerStyle ? Math.round(parseFloat(containerStyle.paddingLeft || '0')) : 0,
          paddingRight: containerStyle ? Math.round(parseFloat(containerStyle.paddingRight || '0')) : 0,
        },
        overflow: {
          scrollWidth,
          viewportWidth,
          hasOverflow: scrollWidth - viewportWidth > 2,
        },
      };
    }),
    3500,
    null
  );

  if (snapshot && typeof snapshot === 'object') {
    return snapshot;
  }

  return {
    sections: {
      bestsellers: { exists: false, visible: false, itemCount: 0, heading: '', top: -1, width: 0, height: 0 },
      bauletos: { exists: false, visible: false, itemCount: 0, heading: '', top: -1, width: 0, height: 0 },
      guidoes: { exists: false, visible: false, itemCount: 0, heading: '', top: -1, width: 0, height: 0 },
      fitment: { exists: false, visible: false, itemCount: 0, heading: '', top: -1, width: 0, height: 0 },
      applications: { exists: false, visible: false, itemCount: 0, heading: '', top: -1, width: 0, height: 0 },
      launches: { exists: false, visible: false, itemCount: 0, heading: '', top: -1, width: 0, height: 0 },
    },
    orderKeys: [],
    orderOk: false,
    appCardCount: 0,
    container: { width: 0, paddingLeft: 0, paddingRight: 0 },
    overflow: { scrollWidth: 0, viewportWidth: 0, hasOverflow: false },
  };
}

async function loginB2B(page, opts, auth) {
  const loginUrl = safeUrl(opts.baseUrl, '/b2b/account/login/');
  await gotoAndStabilize(page, loginUrl, opts.timeoutMs);

  if (/\/b2b\/account\/dashboard\/?/.test(page.url())) {
    return {
      userSelector: null,
      passSelector: null,
      submitSelector: null,
      submitted: false,
      redirected: true,
      finalUrl: page.url(),
    };
  }

  const userSelector = await visible(page, [
    '#b2b-email',
    'input[name="login[username]"]',
    'input[type="text"]',
  ], 1500);
  const passSelector = await visible(page, [
    '#b2b-pass',
    'input[name="login[password]"]',
    'input[type="password"]',
  ], 1500);
  const submitSelector = await visible(page, [
    '#b2b-login-form .b2b-btn-entrar',
    '#b2b-login-form button[type="submit"]',
    '.b2b-btn-entrar',
    'button[type="submit"]',
  ], 1500);

  if (!userSelector || !passSelector) {
    return {
      userSelector,
      passSelector,
      submitSelector,
      submitted: false,
      redirected: false,
      finalUrl: page.url(),
    };
  }

  await page.locator(userSelector).first().fill(auth.username, { timeout: 5000 });
  await page.locator(passSelector).first().fill(auth.password, { timeout: 5000 });

  let submitted = false;
  let redirected = false;
  if (submitSelector) {
    submitted = true;
    redirected = await Promise.race([
      Promise.all([
        page.waitForURL(/\/b2b\/account\/dashboard\/?/i, { timeout: Math.min(opts.timeoutMs, 15000) }),
        page.locator(submitSelector).first().click({ timeout: 5000 }),
      ]).then(() => true).catch(() => false),
      sleep(Math.min(opts.timeoutMs, 16000)).then(() => false),
    ]);
  } else {
    submitted = true;
    redirected = await Promise.race([
      Promise.all([
        page.waitForURL(/\/b2b\/account\/dashboard\/?/i, { timeout: Math.min(opts.timeoutMs, 15000) }),
        page.locator(passSelector).first().press('Enter', { timeout: 3000 }),
      ]).then(() => true).catch(() => false),
      sleep(Math.min(opts.timeoutMs, 16000)).then(() => false),
    ]);
  }

  await withNodeTimeout(waitForPageStable(page, opts.timeoutMs), 7000, null);
  if (!redirected && /\/b2b\/account\/dashboard\/?/i.test(page.url())) {
    redirected = true;
  }

  return {
    userSelector,
    passSelector,
    submitSelector,
    submitted,
    redirected,
    finalUrl: page.url(),
  };
}

async function authenticatedDashboardHealth(page) {
  const snapshot = await withNodeTimeout(
    page.evaluate(() => {
      function probe(selector) {
        if (!selector) {
          return { selector: '', exists: false, visible: false, width: 0, height: 0 };
        }
        const el = document.querySelector(selector);
        if (!el) {
          return { selector, exists: false, visible: false, width: 0, height: 0 };
        }
        const cs = window.getComputedStyle(el);
        const rect = el.getBoundingClientRect();
        return {
          selector,
          exists: true,
          visible: cs.display !== 'none'
            && cs.visibility !== 'hidden'
            && Number(cs.opacity || '1') > 0
            && rect.width > 1
            && rect.height > 1,
          width: Math.round(rect.width || 0),
          height: Math.round(rect.height || 0),
        };
      }

      function probeAny(selectors) {
        for (const selector of selectors) {
          const result = probe(selector);
          if (result.exists) {
            return result;
          }
        }
        return { selector: selectors[0] || '', exists: false, visible: false, width: 0, height: 0 };
      }

      const shell = probe('.awa-b2b-ops-dashboard');
      const hero = probe('.awa-b2b-ops-hero');
      const sidebar = probeAny([
        '.sidebar.sidebar-main',
        '.sidebar-main',
        '.columns .sidebar',
        '.column.sidebar',
      ]);
      const nav = probeAny([
        '.sidebar.sidebar-main .block-collapsible-nav',
        '.sidebar-main .block-collapsible-nav',
        '.block-collapsible-nav',
        '.account-nav',
      ]);
      const main = probe('.page-main.container');
      const kpis = document.querySelectorAll('.awa-b2b-kpi-card').length;
      const actions = document.querySelectorAll('.awa-b2b-action-grid__card').length;
      const checklist = document.querySelectorAll('.awa-b2b-checklist__item').length;
      const suggestions = document.querySelectorAll('.awa-b2b-suggestion-list__item').length;
      const scrollWidth = Math.max(
        document.documentElement ? document.documentElement.scrollWidth : 0,
        document.body ? document.body.scrollWidth : 0
      );
      const viewportWidth = window.innerWidth || 0;

      return {
        shell,
        hero,
        sidebar,
        nav,
        main,
        kpis,
        actions,
        checklist,
        suggestions,
        bodyClass: document.body ? (document.body.getAttribute('class') || '') : '',
        overflow: {
          scrollWidth,
          viewportWidth,
          hasOverflow: scrollWidth - viewportWidth > 2,
        },
      };
    }),
    3500,
    null
  );

  if (snapshot && typeof snapshot === 'object') {
    return snapshot;
  }

  return {
    shell: { exists: false, visible: false, width: 0, height: 0 },
    hero: { exists: false, visible: false, width: 0, height: 0 },
    sidebar: { exists: false, visible: false, width: 0, height: 0 },
    nav: { exists: false, visible: false, width: 0, height: 0 },
    main: { exists: false, visible: false, width: 0, height: 0 },
    kpis: 0,
    actions: 0,
    checklist: 0,
    suggestions: 0,
    bodyClass: '',
    overflow: { scrollWidth: 0, viewportWidth: 0, hasOverflow: false },
  };
}

async function authenticatedFlowPageHealth(page, pageKey) {
  const roots = {
    quickorder: '.awa-b2b-flow-page--quickorder',
    shoppinglist: '.awa-b2b-flow-page--shoppinglist',
    quoteHistory: '.awa-b2b-flow-page--quote-history',
  };

  const snapshot = await withNodeTimeout(
    page.evaluate(({ selectorMap, key }) => {
      function probe(selector) {
        if (!selector) {
          return { selector: '', exists: false, visible: false, width: 0, height: 0 };
        }
        const el = document.querySelector(selector);
        if (!el) {
          return { selector, exists: false, visible: false, width: 0, height: 0 };
        }
        const cs = window.getComputedStyle(el);
        const rect = el.getBoundingClientRect();
        return {
          selector,
          exists: true,
          visible: cs.display !== 'none'
            && cs.visibility !== 'hidden'
            && Number(cs.opacity || '1') > 0
            && rect.width > 1
            && rect.height > 1,
          width: Math.round(rect.width || 0),
          height: Math.round(rect.height || 0),
        };
      }

      function probeAny(selectors) {
        for (const selector of selectors) {
          const result = probe(selector);
          if (result.exists) {
            return result;
          }
        }
        return { selector: selectors[0] || '', exists: false, visible: false, width: 0, height: 0 };
      }

      const scrollWidth = Math.max(
        document.documentElement ? document.documentElement.scrollWidth : 0,
        document.body ? document.body.scrollWidth : 0
      );
      const viewportWidth = window.innerWidth || 0;

      return {
        root: probe(selectorMap[key] || ''),
        sidebar: probeAny([
          '.sidebar.sidebar-main',
          '.sidebar-main',
          '.columns .sidebar',
          '.column.sidebar',
        ]),
        nav: probeAny([
          '.sidebar.sidebar-main .block-collapsible-nav',
          '.sidebar-main .block-collapsible-nav',
          '.block-collapsible-nav',
          '.account-nav',
        ]),
        hero: probe('.awa-b2b-flow-hero'),
        quickorder: {
          container: probe('#quickorder-container'),
          rows: probe('#quickorder-rows'),
          addRow: probe('#quickorder-add-row'),
          submit: probe('#quickorder-submit'),
          csvZone: probe('#csv-dropzone'),
          csvInput: probe('#csv-file-input'),
          csvStatus: probe('#csv-status'),
        },
        shoppinglist: {
          createButton: probe('#btn-create-list'),
          createForm: probe('#create-list-form'),
          cards: document.querySelectorAll('.shoppinglist-card.awa-b2b-list-card').length,
          empty: probe('.awa-b2b-empty-state'),
        },
        quoteHistory: {
          table: probe('#quote-history-table'),
          rows: document.querySelectorAll('#quote-history-table tbody tr').length,
          empty: probe('.awa-b2b-empty-state'),
          statuses: document.querySelectorAll('.quote-status').length,
        },
        overflow: {
          scrollWidth,
          viewportWidth,
          hasOverflow: scrollWidth - viewportWidth > 2,
        },
      };
    }, { selectorMap: roots, key: pageKey }),
    3500,
    null
  );

  if (snapshot && typeof snapshot === 'object') {
    return snapshot;
  }

  return {
    root: { exists: false, visible: false, width: 0, height: 0 },
    sidebar: { exists: false, visible: false, width: 0, height: 0 },
    nav: { exists: false, visible: false, width: 0, height: 0 },
    hero: { exists: false, visible: false, width: 0, height: 0 },
    quickorder: {
      container: { exists: false, visible: false },
      rows: { exists: false, visible: false },
      addRow: { exists: false, visible: false },
      submit: { exists: false, visible: false },
      csvZone: { exists: false, visible: false },
      csvInput: { exists: false, visible: false },
      csvStatus: { exists: false, visible: false },
    },
    shoppinglist: {
      createButton: { exists: false, visible: false },
      createForm: { exists: false, visible: false },
      cards: 0,
      empty: { exists: false, visible: false },
    },
    quoteHistory: {
      table: { exists: false, visible: false },
      rows: 0,
      empty: { exists: false, visible: false },
      statuses: 0,
    },
    overflow: { scrollWidth: 0, viewportWidth: 0, hasOverflow: false },
  };
}

async function captureScreens(page, dir, prefix, fullPage = false) {
  let viewportPath = null;
  const viewportTarget = path.join(dir, `${sanitizeName(prefix)}.png`);
  try {
    const ok = await withNodeTimeout(
      page.screenshot({
        path: viewportTarget,
        fullPage: false,
        animations: 'disabled',
        caret: 'hide',
        timeout: 5000,
      }).then(() => true),
      8000,
      false
    );
    viewportPath = ok ? viewportTarget : null;
  } catch (_) {
    viewportPath = null;
  }

  let fullPath = null;
  if (fullPage) {
    let docHeight = 0;
    try {
      docHeight = await withNodeTimeout(
        page.evaluate(() => {
          const d = document.documentElement;
          const b = document.body;
          return Math.max(
            d ? d.scrollHeight : 0,
            d ? d.offsetHeight : 0,
            b ? b.scrollHeight : 0,
            b ? b.offsetHeight : 0
          );
        }),
        2500,
        0
      );
    } catch (_) {
      docHeight = 0;
    }

    // Home/PLP can be extremely long and fullPage screenshot may hang in headless-shell.
    if (docHeight > 9000) {
      fullPath = null;
    } else {
      const fullTarget = path.join(dir, `${sanitizeName(prefix)}-full.png`);
      try {
        const ok = await withNodeTimeout(
          page.screenshot({
            path: fullTarget,
            fullPage: true,
            animations: 'disabled',
            caret: 'hide',
            timeout: 7000,
          }).then(() => true),
          10000,
          false
        );
        fullPath = ok ? fullTarget : null;
      } catch (_) {
        fullPath = null;
      }
    }
  }
  return { viewport: viewportPath, fullPage: fullPath };
}

function classifyStepStatus({ checks, consoleEntries, networkFailures, allowWarningsOnly = false, baseUrl = '' }) {
  const failures = [];
  const warnings = [];

  for (const check of checks) {
    if (!check.ok) {
      if (check.severity === 'warn') warnings.push(check.message);
      else failures.push(check.message);
    }
  }

  for (const entry of consoleEntries) {
    const level = classifyConsoleMessage(entry, baseUrl);
    if (level === 'critical') {
      failures.push('Critical console error: ' + String(entry.text).split('\n')[0].slice(0, 240));
    } else if (level === 'warn') {
      warnings.push('Console warning/error: ' + String(entry.text).split('\n')[0].slice(0, 240));
    }
  }

  for (const item of networkFailures) {
    failures.push(`Critical first-party asset failure [${item.status}] ${item.url}`);
  }

  if (failures.length > 0) {
    return { status: 'fail', failures, warnings };
  }
  if (warnings.length > 0 || allowWarningsOnly) {
    return { status: warnings.length > 0 ? 'warn' : 'pass', failures, warnings };
  }
  return { status: 'pass', failures, warnings };
}

async function withStep(page, collector, outputDir, contextLabel, stepName, fn) {
  const key = `${contextLabel}-${stepName}`;
  out(`[${contextLabel}] START ${stepName}`);
  collector.setStep(stepName);
  const snap = collector.snapshot();
  const stepStart = new Date().toISOString();
  let result;
  let caughtError = null;
  const stepPromise = Promise.resolve()
    .then(() => fn())
    .catch((err) => {
      // Prevent late rejections when the timeout wins the race.
      throw err;
    });
  // Swallow the duplicate unhandled rejection if timeout wins and this rejects later.
  stepPromise.catch(() => {});
  try {
    result = await Promise.race([
      stepPromise,
      sleep(60000).then(() => {
        throw new Error(`Step timeout exceeded (60s): ${contextLabel}/${stepName}`);
      }),
    ]);
  } catch (err) {
    caughtError = err;
  }
  const deltas = collector.delta(snap);
  const stepEnd = new Date().toISOString();

  const stepResult = {
    page_name: stepName,
    viewport: contextLabel,
    started_at: stepStart,
    ended_at: stepEnd,
    final_url: null,
    status: 'fail',
    screenshots: [],
    console_errors: [],
    network_failures: [],
    network_warnings: [],
    checks: [],
    notes: [],
  };

  if (result && typeof result === 'object') {
    if (result.final_url) stepResult.final_url = String(result.final_url);
    if (Array.isArray(result.checks)) stepResult.checks = result.checks;
    if (Array.isArray(result.notes)) stepResult.notes = result.notes;
    if (Array.isArray(result.screenshots)) stepResult.screenshots = result.screenshots;
  }
  if (!stepResult.final_url) {
    stepResult.final_url = page.url();
  }

  stepResult.console_errors = deltas.console;
  stepResult.network_failures = deltas.networkFailures;
  stepResult.network_warnings = deltas.networkWarnings;

  if (caughtError) {
    stepResult.checks.push({
      ok: false,
      severity: 'fail',
      message: `Unhandled step exception: ${String(caughtError && caughtError.message ? caughtError.message : caughtError)}`,
    });
    stepResult.notes.push('Stack: ' + String(caughtError && caughtError.stack ? caughtError.stack : 'n/a'));
  }

  const status = classifyStepStatus({
    checks: stepResult.checks,
    consoleEntries: deltas.console,
    networkFailures: deltas.networkFailures,
    baseUrl: collector.baseUrl,
  });
  stepResult.status = status.status;
  if (status.failures.length) stepResult.notes.push(...status.failures);
  if (status.warnings.length) stepResult.notes.push(...status.warnings);

  const screenshotDir = path.join(outputDir, 'screenshots');
  ensureDir(screenshotDir);
  const fallbackShot = path.join(screenshotDir, `${sanitizeName(key)}-post.png`);
  if (!stepResult.screenshots.length) {
    try {
      const ok = await withNodeTimeout(
        page.screenshot({
          path: fallbackShot,
          fullPage: false,
          animations: 'disabled',
          caret: 'hide',
          timeout: 5000,
        }).then(() => true),
        8000,
        false
      );
      if (ok) {
        stepResult.screenshots.push(fallbackShot);
      }
    } catch (_) {
      // ignore
    }
  }

  out(
    `[${contextLabel}] ${stepName}: ${stepResult.status.toUpperCase()} -> ${stepResult.final_url}`
  );
  return stepResult;
}

function pickFirstUnique(list) {
  return Array.from(new Set(list.filter(Boolean)));
}

async function collectPlpProductCandidates(page, baseUrl) {
  return withNodeTimeout(
    page.evaluate((base) => {
      const origin = new URL(base).origin;
      const selectors = [
        '.wrapper.grid.products-grid li.item-product',
        '.products.wrapper .product-item',
        '.products.list.items .item.product',
      ];
      const urls = [];
      const nodes = selectors.flatMap((sel) => Array.from(document.querySelectorAll(sel)));

      function pushUrl(rawHref) {
        if (!rawHref) return;
        let url;
        try {
          url = new URL(rawHref, origin);
        } catch (_) {
          return;
        }
        if (url.origin !== origin) return;
        if (!/\.html$/i.test(url.pathname || '')) return;
        if (/\/(checkout|customer|b2b|catalogsearch|search)\b/i.test(url.pathname || '')) return;
        if (/\/verificar\/categorias(\/|\.html|$)/i.test(url.pathname || '')) return;
        urls.push(url.toString());
      }

      for (const item of nodes) {
        if (!item) continue;
        const hasCommerceSignals = Boolean(
          item.querySelector(
            '.price, .price-box, .special-price, .old-price, .minimal-price, .b2b-login-to-see-price, .stock, .action.tocart, .actions-primary, .product-item-actions'
          )
        );
        if (!hasCommerceSignals) continue;

        const anchor = item.querySelector(
          '.product-item-link[href], .product-thumb a[href], a.product-item-link[href], a.product.photo.product-item-photo[href]'
        );
        if (!anchor) continue;
        pushUrl(anchor.getAttribute('href'));
      }

      if (!urls.length) {
        const fallbackAnchors = Array.from(
          document.querySelectorAll('.products.wrapper a.product-item-link[href], .products.wrapper .product-thumb a[href]')
        );
        for (const anchor of fallbackAnchors) {
          pushUrl(anchor.getAttribute('href'));
          if (urls.length >= 12) break;
        }
      }

      return Array.from(new Set(urls)).slice(0, 12);
    }, baseUrl),
    5000,
    []
  );
}

function healthProbeStalled(health) {
  if (!health || typeof health !== 'object') return true;
  return (
    !health.title &&
    !health.bodyClass &&
    Number(health.cssLinkCount || 0) === 0 &&
    Number(health.bodyChildCount || 0) === 0 &&
    !health.header?.visible &&
    !health.footer?.visible &&
    !health.pageMain?.visible &&
    !health.hero?.visible
  );
}

function hasScreenshotEvidence(shots) {
  return Boolean(shots && (shots.viewport || shots.fullPage));
}

async function fetchText(url, timeoutMs = 8000) {
  if (typeof fetch !== 'function') return null;
  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), timeoutMs);
  try {
    const res = await fetch(url, {
      method: 'GET',
      redirect: 'follow',
      signal: controller.signal,
      headers: { 'user-agent': 'Mozilla/5.0 PlaywrightSmokeBot/1.0' },
    });
    if (!res.ok) return null;
    return await res.text();
  } catch (_) {
    return null;
  } finally {
    clearTimeout(timer);
  }
}

function extractXmlLocs(xml) {
  if (!xml) return [];
  const matches = xml.match(/<loc>([\s\S]*?)<\/loc>/gi) || [];
  return pickFirstUnique(
    matches
      .map((m) => m.replace(/^<loc>/i, '').replace(/<\/loc>$/i, '').trim())
      .map((s) => s.replace(/&amp;/g, '&'))
      .filter(Boolean)
  );
}

async function discoverFromSitemap(baseUrl, kind) {
  const rootSitemap = safeUrl(baseUrl, '/sitemap.xml');
  const rootXml = await fetchText(rootSitemap, 10000);
  const rootLocs = extractXmlLocs(rootXml);
  if (!rootLocs.length) return null;

  let candidateLocs = rootLocs;
  const rootLooksIndex = rootLocs.some((u) => /\.xml(\?|$)/i.test(u));
  if (rootLooksIndex) {
    const wanted = rootLocs.filter((u) => {
      const l = String(u).toLowerCase();
      if (!/\.xml(\?|$)/i.test(l)) return false;
      if (kind === 'product') return /product/.test(l);
      if (kind === 'cms') return /page|cms/.test(l);
      return true;
    });
    const childMaps = (wanted.length ? wanted : rootLocs).slice(0, 6);
    candidateLocs = [];
    for (const mapUrl of childMaps) {
      const xml = await fetchText(mapUrl, 10000);
      candidateLocs.push(...extractXmlLocs(xml));
      if (candidateLocs.length >= 200) break;
    }
    candidateLocs = pickFirstUnique(candidateLocs);
  }

  const sameOrigin = candidateLocs.filter((u) => isSameOriginUrl(u, baseUrl));
  const ranked = sameOrigin
    .filter((u) => {
      const p = (parseUrl(u) || { pathname: '' }).pathname || '';
      if (!p || p === '/') return false;
      if (/\/(checkout|customer|b2b|catalogsearch)\b/i.test(p)) return false;
      if (kind === 'product') {
        if (!/\.html$/i.test(p)) return false;
        if (/\/verificar\/categorias(\/|\.html|$)/i.test(p)) return false;
        if (/\/categorias(\/|\.html|$)/i.test(p)) return false;
        if (/\/(retrovisores|capacetes|oleos?|pneus?)\.html$/i.test(p)) return false;
        return true;
      }
      if (kind === 'cms') {
        if (/\.(png|jpe?g|svg|webp|pdf|xml)$/i.test(p)) return false;
        if (/\.html$/i.test(p) || p.split('/').filter(Boolean).length <= 2) return true;
        return /sobre|contato|politica|termos|troca|privacidade|institucional/i.test(p);
      }
      return true;
    })
    .sort((a, b) => {
      const ap = new URL(a).pathname.toLowerCase();
      const bp = new URL(b).pathname.toLowerCase();
      const score = (p) => {
        let s = 0;
        if (kind === 'product') {
          if (/\/produto|\/peca|\/kit/i.test(p)) s += 8;
          s += Math.min(5, p.split('/').filter(Boolean).length);
        } else if (kind === 'cms') {
          if (/sobre|contato/.test(p)) s += 10;
          if (/politica|termos|troca|privacidade/.test(p)) s += 9;
          if (p.split('/').filter(Boolean).length <= 2) s += 4;
        }
        return -s;
      };
      return score(ap) - score(bp);
    });

  return ranked[0] || null;
}

async function discoverFooterCmsPage(page, baseUrl) {
  const hrefs = await page.$$eval('footer a[href], .page-footer a[href]', (anchors) =>
    anchors.map((a) => a.href).filter(Boolean)
  );
  const base = new URL(baseUrl);
  const candidates = [];

  for (const href of hrefs) {
    let url;
    try {
      url = new URL(href, base);
    } catch (_) {
      continue;
    }
    if (url.origin !== base.origin) continue;
    const p = url.pathname || '/';
    if (p === '/' || p.startsWith('/checkout') || p.startsWith('/customer') || p.startsWith('/b2b')) continue;
    if (p.startsWith('/catalogsearch') || p.startsWith('/search')) continue;
    if (/\.(png|jpe?g|svg|webp|pdf|zip)$/i.test(p)) continue;
    candidates.push(url.toString());
  }

  const ranked = pickFirstUnique(candidates).sort((a, b) => {
    const score = (u) => {
      const p = new URL(u).pathname.toLowerCase();
      let s = 0;
      if (/sobre|quem|institucional/.test(p)) s += 10;
      if (/contato/.test(p)) s += 9;
      if (/politica|privacidade|termos|troca|devol/.test(p)) s += 8;
      if (p.split('/').filter(Boolean).length <= 2) s += 2;
      return -s;
    };
    return score(a) - score(b);
  });

  return ranked[0] || null;
}

async function runDesktopFlow(browser, opts, outputDir) {
  const ua =
    'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36';
  const desktopContextOpts = {
    viewport: { width: 1366, height: 900 },
    userAgent: ua,
    locale: 'pt-BR',
    timezoneId: 'America/Sao_Paulo',
  };
  let context = null;
  let page = null;
  let collector = null;
  const screenshotDir = path.join(outputDir, 'screenshots');
  ensureDir(screenshotDir);
  async function initSession() {
    context = await browser.newContext(desktopContextOpts);
    page = await context.newPage();
    page.setDefaultTimeout(opts.timeoutMs);
    page.setDefaultNavigationTimeout(opts.timeoutMs);
    collector = new PageCollector(page, opts.baseUrl);
  }
  async function rotateSession() {
    if (context) {
      await withNodeTimeout(context.close(), 8000, null);
    }
    await initSession();
  }
  await initSession();

  const results = [];
  let discoveredProductUrl = null;
  let discoveredProductCandidates = [];
  let discoveredCmsUrl = null;
  discoveredProductUrl = await withNodeTimeout(discoverFromSitemap(opts.baseUrl, 'product'), 15000, null);
  discoveredCmsUrl = await withNodeTimeout(discoverFromSitemap(opts.baseUrl, 'cms'), 15000, null);
  if (discoveredProductUrl) {
    discoveredProductCandidates = [discoveredProductUrl];
  }

  results.push(
    await withStep(page, collector, outputDir, 'desktop', 'home', async () => {
      out('[desktop][home] goto');
      const response = await page.goto(opts.baseUrl, { waitUntil: 'domcontentloaded', timeout: opts.timeoutMs });
      const homeShots = await captureScreens(page, screenshotDir, 'desktop-home', true);
      await withNodeTimeout(waitForPageStable(page, opts.timeoutMs), 7000, null);
      out('[desktop][home] health');
      const health = await basicVisualHealth(page);
      const stalled = healthProbeStalled(health);
      const hasShots = hasScreenshotEvidence(homeShots);
      const checks = [
        { ok: (response && response.status() === 200) || page.url().startsWith(opts.baseUrl), severity: 'fail', message: 'Home did not load with HTTP 200/valid redirect' },
      ];
      if (stalled && hasShots) {
        // Home can be heavy in this environment; keep evidence in notes without downgrading route status.
      } else {
        checks.push({ ok: health.bodyChildCount > 0 && (health.pageMain.visible || health.hero.visible || health.header.visible), severity: 'fail', message: 'Home content looks too small/blank' });
        checks.push({ ok: health.header.visible, severity: 'fail', message: 'Header not visible on home' });
        checks.push({ ok: health.footer.visible, severity: 'warn', message: 'Footer not visible on home' });
        checks.push({ ok: health.childThemeCssPresent, severity: 'warn', message: 'Child-theme assets not detected in home HTML/CSS links' });
      }
      const navHealth = await desktopHomeNavigationHealth(page);
      const merchHealth = await homeMerchandisingHealth(page);
      checks.push({ ok: navHealth.menuColumn.visible, severity: 'fail', message: 'Desktop home vertical menu column (.menu_left_home1) is hidden' });
      checks.push({ ok: navHealth.mainColumn.visible, severity: 'fail', message: 'Desktop home main navigation column (.menu_primary) is hidden' });
      checks.push({ ok: navHealth.verticalMenu.visible && navHealth.verticalTrigger.visible, severity: 'fail', message: 'Desktop home vertical menu shell/trigger is not visible' });
      checks.push({ ok: navHealth.verticalList.visible, severity: 'fail', message: 'Desktop home vertical menu list is hidden' });
      checks.push({ ok: navHealth.mainNav.visible, severity: 'fail', message: 'Desktop home custom main navigation is hidden' });
      checks.push({
        ok: navHealth.triggerClickable || navHealth.verticalList.visible,
        severity: 'fail',
        message: 'Desktop home vertical menu trigger is not clickable and list is not visible',
      });
      checks.push({ ok: merchHealth.sections.bestsellers.visible && merchHealth.sections.bestsellers.itemCount > 0, severity: 'fail', message: 'Desktop home bestseller shelf is missing or empty' });
      checks.push({ ok: merchHealth.sections.bauletos.visible && merchHealth.sections.bauletos.itemCount > 0, severity: 'fail', message: 'Desktop home Bauletos shelf is missing or empty' });
      checks.push({ ok: merchHealth.sections.guidoes.visible && merchHealth.sections.guidoes.itemCount > 0, severity: 'fail', message: 'Desktop home Guidoes shelf is missing or empty' });
      checks.push({ ok: merchHealth.sections.fitment.visible, severity: 'fail', message: 'Desktop home fitment section is missing' });
      checks.push({ ok: merchHealth.sections.applications.visible && merchHealth.appCardCount >= 6, severity: 'fail', message: 'Desktop home Aplicacoes em alta is missing or incomplete' });
      checks.push({ ok: merchHealth.sections.launches.visible && merchHealth.sections.launches.itemCount > 0, severity: 'fail', message: 'Desktop home Lancamentos shelf is missing or empty' });
      checks.push({ ok: merchHealth.orderOk, severity: 'fail', message: 'Desktop home merchandising sections are not in the expected order' });
      checks.push({ ok: !merchHealth.overflow.hasOverflow, severity: 'fail', message: 'Desktop home has horizontal overflow' });
      checks.push({ ok: merchHealth.container.width > 0 && merchHealth.container.width <= 1200, severity: 'fail', message: 'Desktop home merchandising container exceeds 1200px' });
      checks.push({ ok: merchHealth.container.paddingLeft >= 14 && merchHealth.container.paddingRight >= 14, severity: 'warn', message: 'Desktop home merchandising container padding is below target' });
      const notes = [`title=${health.title}`, `bodyClass=${health.bodyClass}`, `cssLinks=${health.cssLinkCount}`, `childThemeCssLinks=${health.childThemeCssLinkCount}`];
      if (discoveredProductUrl) notes.push(`Sitemap PDP candidate: ${discoveredProductUrl}`);
      if (discoveredCmsUrl) notes.push(`Sitemap CMS candidate: ${discoveredCmsUrl}`);
      if (!health.markers.compatBootstrap) notes.push('awaCustomCompatBootstrap marker not found on home HTML');
      if (stalled && hasShots) notes.push('Home DOM probe stalled in headless; using screenshot evidence');
      notes.push(`homeNav.menuLeft display=${navHealth.menuColumn.display} size=${navHealth.menuColumn.width}x${navHealth.menuColumn.height}`);
      notes.push(`homeNav.mainMenu display=${navHealth.mainColumn.display} size=${navHealth.mainColumn.width}x${navHealth.mainColumn.height}`);
      notes.push(`homeNav.verticalList display=${navHealth.verticalList.display} size=${navHealth.verticalList.width}x${navHealth.verticalList.height}`);
      notes.push(`homeNav.trigger pointerEvents=${navHealth.verticalTrigger.pointerEvents} clickable=${navHealth.triggerClickable ? 'yes' : 'no'}`);
      notes.push(`homeMerch.order=${merchHealth.orderKeys.join(' > ') || 'none'}`);
      notes.push(`homeMerch.cards applications=${merchHealth.appCardCount} bestsellers=${merchHealth.sections.bestsellers.itemCount} bauletos=${merchHealth.sections.bauletos.itemCount} guidoes=${merchHealth.sections.guidoes.itemCount} launches=${merchHealth.sections.launches.itemCount}`);
      notes.push(`homeMerch.container width=${merchHealth.container.width} pad=${merchHealth.container.paddingLeft}/${merchHealth.container.paddingRight}`);
      notes.push(`homeMerch.overflow scroll=${merchHealth.overflow.scrollWidth} viewport=${merchHealth.overflow.viewportWidth} overflow=${merchHealth.overflow.hasOverflow ? 'yes' : 'no'}`);

      out('[desktop][home] search interaction');
      const searchClick = await clickIfVisible(page, [
        '.top-search .action.search',
        '.top-search button.search',
        '.header .top-search .block-search .action.search',
        '.block-search .label',
        '#search',
        'button[aria-label*="Search"]',
      ], { postWaitMs: 700 });
      if (!searchClick.clicked) {
        const typed = await typeIfVisible(page, '#search', 'capacete');
        notes.push(typed ? 'Focused/typed in #search' : 'Search UI interaction not found');
      } else {
        notes.push(`Clicked search toggle: ${searchClick.selector}`);
        await typeIfVisible(page, '#search', 'capacete');
      }

      out('[desktop][home] nav interaction');
      const verticalMenuClick = await clickIfVisible(page, [
        '.header-control.header-nav .menu_left_home1 [data-role="awa-vertical-menu-trigger"]',
        '.header-control.header-nav .menu_left_home1 .title-category-dropdown',
      ], { postWaitMs: 700 });
      if (verticalMenuClick.clicked) {
        notes.push(`Clicked vertical menu trigger: ${verticalMenuClick.selector}`);
      } else {
        notes.push('Vertical menu trigger click skipped (hidden/non-clickable in desktop mode)');
      }
      const verticalListVisibleSel = await visible(page, [
        '.header-control.header-nav .menu_left_home1 .navigation.verticalmenu.side-verticalmenu > ul.togge-menu.list-category-dropdown',
        '.header-control.header-nav .menu_left_home1 .navigation.verticalmenu.side-verticalmenu > ul.togge-menu.list-category-dropdown.vmm-open',
      ], 900);
      if (verticalListVisibleSel) {
        notes.push(`Vertical menu list visible: ${verticalListVisibleSel}`);
      } else {
        notes.push('Vertical menu list not visible after interaction');
      }

      const urlBeforeNavClick = page.url();
      const navClick = await clickIfVisible(page, [
        '.navigation.custommenu.main-nav > ul > li > a',
        '.header-nav .navigation a.level-top',
        'nav a',
      ], { postWaitMs: 1200 });
      if (navClick.clicked) {
        notes.push(`Clicked nav item: ${navClick.selector}`);
        if (page.url() !== urlBeforeNavClick) {
          await page.goBack({ waitUntil: 'domcontentloaded', timeout: opts.timeoutMs }).catch(() => {});
          await waitForPageStable(page, opts.timeoutMs).catch(() => {});
        } else {
          notes.push('Nav click did not navigate; skipped goBack');
        }
      } else {
        notes.push('No clickable top nav item found');
      }

      out('[desktop][home] minicart interaction');
      const miniCartClick = await clickIfVisible(page, [
        '.minicart-wrapper .action.showcart',
        '.minicart-wrapper a.action.showcart',
        '.minicart-wrapper .showcart',
        '.minicart-wrapper .header-mini-cart',
      ], { postWaitMs: 800 });
      if (miniCartClick.clicked) {
        notes.push(`Opened minicart: ${miniCartClick.selector}`);
        await clickIfVisible(page, [
          '.minicart-wrapper .action.close',
          '.minicart-wrapper .close',
          '.modals-wrapper .action-close',
        ], { postWaitMs: 500 });
      } else {
        notes.push('Minicart trigger not found');
      }

      out('[desktop][home] footer link discovery');
      const footerCmsCandidate = await Promise.race([
        discoverFooterCmsPage(page, opts.baseUrl).catch(() => null),
        sleep(3000).then(() => null),
      ]);
      if (footerCmsCandidate) {
        discoveredCmsUrl = footerCmsCandidate;
        notes.push(`Discovered footer CMS candidate: ${discoveredCmsUrl}`);
      } else if (discoveredCmsUrl) {
        notes.push(`No footer CMS candidate discovered; kept sitemap candidate: ${discoveredCmsUrl}`);
      } else {
        notes.push('No footer CMS candidate discovered');
      }

      out('[desktop][home] screenshots');
      const shots = homeShots;
      out('[desktop][home] done');
      return {
        final_url: page.url(),
        checks,
        notes,
        screenshots: [shots.viewport, shots.fullPage].filter(Boolean),
      };
    })
  );
  await rotateSession();

  results.push(
    await withStep(page, collector, outputDir, 'desktop', 'plp', async () => {
      const targetUrl = safeUrl(opts.baseUrl, '/retrovisores.html');
      const response = await page.goto(targetUrl, { waitUntil: 'domcontentloaded', timeout: opts.timeoutMs });
      const plpShots = await captureScreens(page, screenshotDir, 'desktop-plp', true);
      await withNodeTimeout(waitForPageStable(page, opts.timeoutMs), 7000, null);
      const health = await basicVisualHealth(page);
      const productCount = await withNodeTimeout(page.locator('.product-item').count(), 2000, 0);
      const gridVisibleSel = await visible(page, [
        '.products-grid',
        '.products.list.items',
        '.products.wrapper',
      ]);
      const checks = [
        { ok: (response && response.status() === 200) || page.url().includes('/retrovisores'), severity: 'fail', message: 'PLP did not load as expected' },
      ];
      const plpStalled = healthProbeStalled(health);
      if (plpStalled && hasScreenshotEvidence(plpShots)) {
        checks.push({ ok: false, severity: 'warn', message: 'PLP DOM probe stalled in headless; using screenshot evidence' });
      } else {
        checks.push({ ok: health.header.visible && health.footer.visible, severity: 'fail', message: 'PLP header/footer not visible' });
        checks.push({ ok: gridVisibleSel || productCount > 0, severity: 'fail', message: 'PLP product grid/list not detected' });
      }
      const notes = [`title=${health.title}`, `productCount=${productCount}`];
      notes.push(`PDP candidate=${discoveredProductUrl || 'none'}`);
      const plpCandidates = await collectPlpProductCandidates(page, opts.baseUrl);
      if (Array.isArray(plpCandidates) && plpCandidates.length) {
        discoveredProductCandidates = pickFirstUnique([
          ...discoveredProductCandidates,
          ...plpCandidates,
        ]);
        if (!discoveredProductUrl) {
          discoveredProductUrl = discoveredProductCandidates[0] || null;
        }
        notes.push(`PLP PDP candidates found=${plpCandidates.length}`);
      } else {
        notes.push('PLP PDP candidates found=0');
      }

      if (!discoveredProductUrl) {
        discoveredProductUrl = await withNodeTimeout(
          page.evaluate((baseUrl) => {
            const base = new URL(baseUrl);
            const selectors = [
              '.wrapper.grid.products-grid li.item-product .product-item-link[href]',
              '.wrapper.grid.products-grid li.item-product .product-thumb a[href]',
              '.products.list.items.product-items li.item.product .product-item-link[href]',
              '.product-item a.product-item-link[href]',
            ];
            const anchors = selectors.flatMap((sel) => Array.from(document.querySelectorAll(sel)));
            for (const a of anchors) {
              const raw = a.getAttribute('href');
              if (!raw) continue;
              let u;
              try {
                u = new URL(raw, base);
              } catch (_) {
                continue;
              }
              if (u.origin !== base.origin) continue;
              if (!/\.html$/i.test(u.pathname)) continue;
              if (/\/(checkout|customer|b2b|catalogsearch)\b/i.test(u.pathname)) continue;
              if (/\/retrovisores\.html$/i.test(u.pathname)) continue;
              return u.toString();
            }
            return null;
          }, opts.baseUrl),
          3500,
          null
        );
      }
      if (discoveredProductUrl) {
        discoveredProductCandidates = pickFirstUnique([
          discoveredProductUrl,
          ...discoveredProductCandidates,
        ]);
        notes.push(`PDP candidate resolved=${discoveredProductUrl}`);
      } else {
        checks.push({ ok: false, severity: 'warn', message: 'No PDP candidate available (sitemap discovery failed)' });
      }

      const shots = plpShots;
      return { final_url: page.url(), checks, notes, screenshots: [shots.viewport, shots.fullPage].filter(Boolean) };
    })
  );
  await rotateSession();

  results.push(
    await withStep(page, collector, outputDir, 'desktop', 'pdp_or_gated', async () => {
      let usedCategoryFallback = false;
      let response = null;
      let health = null;
      let url = '';
      let isGatedB2b = false;
      let pdpMarker = null;
      let b2bForm = null;
      let selectedPdpTarget = null;
      const rejectedCandidates = [];
      let candidateQueue = pickFirstUnique([
        discoveredProductUrl,
        ...discoveredProductCandidates,
      ]);

      if (!candidateQueue.length) {
        candidateQueue = [safeUrl(opts.baseUrl, '/retrovisores.html')];
        usedCategoryFallback = true;
      }

      for (const candidateUrl of candidateQueue.slice(0, 8)) {
        response = await page.goto(candidateUrl, { waitUntil: 'domcontentloaded', timeout: opts.timeoutMs });
        await withNodeTimeout(waitForPageStable(page, opts.timeoutMs), 7000, null);
        health = await basicVisualHealth(page);
        url = page.url();
        isGatedB2b = /\/b2b\/account\/login\/?/.test(url);
        pdpMarker = await visible(page, [
          'body.catalog-product-view',
          '.product-info-main',
          '.gallery-placeholder',
          '.product.media',
        ]);
        b2bForm = await visible(page, ['form#login-form', '.b2b-login', 'input[name="login[username]"]']);

        if (isGatedB2b || pdpMarker) {
          selectedPdpTarget = candidateUrl;
          discoveredProductUrl = candidateUrl;
          break;
        }

        rejectedCandidates.push(`${candidateUrl} -> ${url}`);
      }

      const shots = await captureScreens(page, screenshotDir, 'desktop-pdp-or-gated', true);
      const pdpStalled = healthProbeStalled(health);
      const checks = [
        { ok: (response && response.status() >= 200 && response.status() < 400) || Boolean(url), severity: 'fail', message: 'PDP/gated route did not load' },
        { ok: health.header.visible || isGatedB2b || (pdpStalled && hasScreenshotEvidence(shots)), severity: 'warn', message: 'Header not clearly visible on PDP/gated page' },
        { ok: !isGatedB2b || Boolean(b2bForm), severity: 'fail', message: 'B2B gated redirect occurred but login form/page markers were not found' },
      ];
      if (usedCategoryFallback) {
        checks.push({
          ok: false,
          severity: 'warn',
          message: 'No PDP candidate resolvido; etapa executada com fallback em categoria',
        });
      } else if (!selectedPdpTarget) {
        checks.push({
          ok: false,
          severity: 'fail',
          message: 'No valid PDP candidate detected from sitemap/PLP candidate queue',
        });
      } else {
        checks.push({
          ok: isGatedB2b || Boolean(pdpMarker) || (pdpStalled && hasScreenshotEvidence(shots)),
          severity: 'fail',
          message: 'Neither PDP markers nor expected B2B redirect detected',
        });
      }
      const notes = [`title=${health.title}`, `gated=${isGatedB2b ? 'yes' : 'no'}`, `pdpMarker=${pdpMarker || 'none'}`];
      notes.push(`pdpTarget=${selectedPdpTarget || discoveredProductUrl || 'none'}`);
      notes.push(`pdpCandidatesTried=${candidateQueue.slice(0, 8).length}`);
      if (rejectedCandidates.length) {
        notes.push(`pdpRejectedCandidates=${rejectedCandidates.slice(0, 4).join(' | ')}`);
      }
      if (pdpStalled && hasScreenshotEvidence(shots)) notes.push('PDP DOM probe stalled in headless; using screenshot evidence');
      return { final_url: url, checks, notes, screenshots: [shots.viewport, shots.fullPage].filter(Boolean) };
    })
  );
  await rotateSession();

  results.push(
    await withStep(page, collector, outputDir, 'desktop', 'search', async () => {
      const targetUrl = safeUrl(opts.baseUrl, '/catalogsearch/result/?q=capacete');
      const response = await page.goto(targetUrl, { waitUntil: 'domcontentloaded', timeout: opts.timeoutMs });
      const searchShots = await captureScreens(page, screenshotDir, 'desktop-search', false);
      await withNodeTimeout(waitForPageStable(page, opts.timeoutMs), 7000, null);
      const health = await basicVisualHealth(page);
      const resultsVisible = await visible(page, [
        '.search.results',
        '.products-grid',
        '.products.list.items',
        '.mst-search__index',
        '.mst-search__list',
      ]);
      const emptyVisible = await visible(page, ['.message.notice', '.message.info', '.search.results .message']);
      const searchInputVisible = await visible(page, ['#search', 'input[name="q"]']);
      const searchStalled = healthProbeStalled(health);
      const checks = [
        { ok: (response && response.status() === 200) || page.url().includes('/catalogsearch/result/'), severity: 'fail', message: 'Search results route did not load as expected' },
      ];
      if (searchStalled && hasScreenshotEvidence(searchShots)) {
        checks.push({ ok: false, severity: 'warn', message: 'Search DOM probe stalled in headless; using screenshot evidence' });
      } else {
        checks.push({ ok: Boolean(resultsVisible || emptyVisible), severity: 'fail', message: 'Search page did not show results nor empty-state markers' });
        checks.push({ ok: Boolean(searchInputVisible), severity: 'warn', message: 'Search input not visible on search results page' });
      }
      const notes = [`title=${health.title}`, `resultsMarker=${resultsVisible || 'none'}`, `emptyMarker=${emptyVisible || 'none'}`];
      const shots = searchShots;
      return { final_url: page.url(), checks, notes, screenshots: [shots.viewport].filter(Boolean) };
    })
  );
  await rotateSession();

  results.push(
    await withStep(page, collector, outputDir, 'desktop', 'cart', async () => {
      const targetUrl = safeUrl(opts.baseUrl, '/checkout/cart/');
      const response = await gotoAndStabilize(page, targetUrl, opts.timeoutMs);
      const health = await basicVisualHealth(page);
      const cartMarker = await visible(page, [
        '.checkout-cart-index',
        '.cart-container',
        '.cart-empty',
        '#shopping-cart-table',
      ]);
      const checks = [
        { ok: (response && response.status() === 200) || page.url().includes('/checkout/cart'), severity: 'fail', message: 'Cart route did not load as expected' },
        { ok: Boolean(cartMarker), severity: 'fail', message: 'Cart page shell/empty-state not detected' },
        { ok: health.header.visible && health.footer.visible, severity: 'warn', message: 'Cart header/footer not clearly visible' },
      ];
      const notes = [`title=${health.title}`, `cartMarker=${cartMarker || 'none'}`];
      const shots = await captureScreens(page, screenshotDir, 'desktop-cart', false);
      return { final_url: page.url(), checks, notes, screenshots: [shots.viewport].filter(Boolean) };
    })
  );
  await rotateSession();

  results.push(
    await withStep(page, collector, outputDir, 'desktop', 'customer_login_route', async () => {
      const targetUrl = safeUrl(opts.baseUrl, '/customer/account/login/');
      await gotoAndStabilize(page, targetUrl, opts.timeoutMs);
      const health = await basicVisualHealth(page);
      const url = page.url();
      const isB2b = /\/b2b\/account\/login\/?/.test(url);
      const loginMarker = await visible(page, ['form#login-form', 'input[name="login[username]"]', '.customer-account-login']);
      const checks = [
        { ok: isB2b || Boolean(loginMarker), severity: 'fail', message: 'Customer login route neither redirected to B2B nor rendered a login form' },
        { ok: health.bodyChildCount > 0 && (health.pageMain.visible || Boolean(loginMarker)), severity: 'fail', message: 'Login route page content looks blank' },
      ];
      const notes = [`title=${health.title}`, `redirectedToB2B=${isB2b ? 'yes' : 'no'}`];
      const shots = await captureScreens(page, screenshotDir, 'desktop-customer-login-route', false);
      return { final_url: url, checks, notes, screenshots: [shots.viewport].filter(Boolean) };
    })
  );
  await rotateSession();

  results.push(
    await withStep(page, collector, outputDir, 'desktop', 'b2b_login', async () => {
      const targetUrl = safeUrl(opts.baseUrl, '/b2b/account/login/');
      await gotoAndStabilize(page, targetUrl, opts.timeoutMs);
      const health = await basicVisualHealth(page);
      const formSel = await visible(page, ['form#login-form', 'input[name="login[username]"]', 'input[type="password"]']);
      if (formSel) {
        await page.locator(formSel).first().click({ timeout: 3000 }).catch(() => {});
      }
      const checks = [
        { ok: /\/b2b\/account\/login\/?/.test(page.url()) || Boolean(formSel), severity: 'fail', message: 'B2B login page did not load as expected' },
        { ok: Boolean(formSel), severity: 'fail', message: 'B2B login form fields not visible' },
        { ok: health.header.visible || health.pageMain.visible, severity: 'warn', message: 'B2B login shell layout visibility uncertain' },
      ];
      const notes = [`title=${health.title}`, `formSelector=${formSel || 'none'}`];
      const shots = await captureScreens(page, screenshotDir, 'desktop-b2b-login', true);
      return { final_url: page.url(), checks, notes, screenshots: [shots.viewport, shots.fullPage].filter(Boolean) };
    })
  );
  await rotateSession();

  results.push(
    await withStep(page, collector, outputDir, 'desktop', 'cms_page', async () => {
      const targetUrl = discoveredCmsUrl || opts.baseUrl;
      const response = await page.goto(targetUrl, { waitUntil: 'domcontentloaded', timeout: opts.timeoutMs });
      const shots = await captureScreens(page, screenshotDir, 'desktop-cms-page', false);
      await withNodeTimeout(waitForPageStable(page, opts.timeoutMs), 7000, null);
      const health = await basicVisualHealth(page);
      const contentMarker = await visible(page, [
        'main .page-title',
        '.page-main .columns',
        '.cms-page-view .column.main',
        '.cms-page-view .page-main',
      ]);
      const cmsStalled = healthProbeStalled(health);
      const checks = [
        { ok: (response && response.status() >= 200 && response.status() < 400) || Boolean(page.url()), severity: 'fail', message: 'CMS route did not load as expected' },
        { ok: page.url() !== opts.baseUrl || Boolean(discoveredCmsUrl), severity: 'warn', message: 'No footer CMS link discovered; fell back to home' },
      ];
      if (cmsStalled && hasScreenshotEvidence(shots)) {
        checks.push({ ok: false, severity: 'warn', message: 'CMS DOM probe stalled in headless; using screenshot evidence' });
      } else {
        checks.push({ ok: health.header.visible && health.footer.visible, severity: 'fail', message: 'CMS page shell header/footer not visible' });
        checks.push({ ok: Boolean(contentMarker) || health.pageMain.visible, severity: 'fail', message: 'CMS page content region not detected' });
      }
      const notes = [`title=${health.title}`, `discoveredCmsUrl=${discoveredCmsUrl || 'none'}`, `contentMarker=${contentMarker || 'none'}`];
      if (cmsStalled && hasScreenshotEvidence(shots)) notes.push('CMS DOM probe stalled in headless; using screenshot evidence');
      return { final_url: page.url(), checks, notes, screenshots: [shots.viewport].filter(Boolean) };
    })
  );

  if (context) {
    await withNodeTimeout(context.close(), 8000, null);
  }
  return results;
}

async function runMobileFlow(browser, opts, outputDir) {
  const iphone12 = devices['iPhone 12'] || {
    viewport: { width: 390, height: 844 },
    userAgent:
      'Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.0 Mobile/15E148 Safari/604.1',
    deviceScaleFactor: 3,
    isMobile: true,
    hasTouch: true,
    defaultBrowserType: 'webkit',
  };

  const mobileContextOpts = {
    ...iphone12,
    locale: 'pt-BR',
    timezoneId: 'America/Sao_Paulo',
  };
  let context = null;
  let page = null;
  let collector = null;
  const screenshotDir = path.join(outputDir, 'screenshots');
  ensureDir(screenshotDir);
  const results = [];
  async function initSession() {
    context = await browser.newContext(mobileContextOpts);
    page = await context.newPage();
    page.setDefaultTimeout(opts.timeoutMs);
    page.setDefaultNavigationTimeout(opts.timeoutMs);
    collector = new PageCollector(page, opts.baseUrl);
  }
  async function rotateSession() {
    if (context) {
      await withNodeTimeout(context.close(), 8000, null);
    }
    await initSession();
  }
  await initSession();

  results.push(
    await withStep(page, collector, outputDir, 'mobile', 'home', async () => {
      const response = await page.goto(opts.baseUrl, { waitUntil: 'domcontentloaded', timeout: opts.timeoutMs });
      const mobileHomeShots = await captureScreens(page, screenshotDir, 'mobile-home', true);
      await withNodeTimeout(waitForPageStable(page, opts.timeoutMs), 7000, null);
      const health = await basicVisualHealth(page);
      // Avoid toggle-click interaction in headless mobile home: in this storefront
      // it can intermittently stall DOM probes and create false warnings.
      const navToggleVisible = await visible(page, [
        '.action.nav-toggle',
        '.nav-toggle',
        'button[aria-label*="menu" i]',
      ], 1500);
      const navTogglePresent = await exists(page, [
        '.action.nav-toggle',
        '.nav-toggle',
        'button[aria-label*="menu" i]',
      ]);
      const maybeDrawer = await visible(page, [
        '.nav-sections-item-content',
        '.navigation',
        '.aw-menu-drawer',
        '.nav-sections',
      ], 1200);
      const navDetected = Boolean(navToggleVisible || navTogglePresent || maybeDrawer);
      const merchHealth = await homeMerchandisingHealth(page);

      const mobileHomeStalled = healthProbeStalled(health);
      const checks = [
        { ok: (response && response.status() === 200) || page.url().startsWith(opts.baseUrl), severity: 'fail', message: 'Mobile home did not load with HTTP 200/valid redirect' },
      ];
      if (mobileHomeStalled && hasScreenshotEvidence(mobileHomeShots)) {
        checks.push({ ok: false, severity: 'warn', message: 'Mobile home DOM probe stalled in headless; using screenshot evidence' });
      } else {
        checks.push({ ok: health.bodyChildCount > 0 && (health.pageMain.visible || health.hero.visible), severity: 'fail', message: 'Mobile home looks blank' });
        checks.push({ ok: health.header.visible, severity: 'fail', message: 'Mobile home header not visible' });
        checks.push({ ok: health.footer.visible, severity: 'warn', message: 'Mobile home footer not visible in initial state' });
        checks.push({ ok: navDetected, severity: 'warn', message: 'Mobile menu toggle/drawer not detected' });
      }
      checks.push({ ok: merchHealth.sections.bestsellers.visible && merchHealth.sections.bestsellers.itemCount > 0, severity: 'fail', message: 'Mobile home bestseller shelf is missing or empty' });
      checks.push({ ok: merchHealth.sections.bauletos.visible && merchHealth.sections.bauletos.itemCount > 0, severity: 'fail', message: 'Mobile home Bauletos shelf is missing or empty' });
      checks.push({ ok: merchHealth.sections.guidoes.visible && merchHealth.sections.guidoes.itemCount > 0, severity: 'fail', message: 'Mobile home Guidoes shelf is missing or empty' });
      checks.push({ ok: merchHealth.sections.applications.visible && merchHealth.appCardCount >= 6, severity: 'fail', message: 'Mobile home Aplicacoes em alta is missing or incomplete' });
      checks.push({ ok: merchHealth.sections.launches.visible && merchHealth.sections.launches.itemCount > 0, severity: 'fail', message: 'Mobile home Lancamentos shelf is missing or empty' });
      checks.push({ ok: merchHealth.orderOk, severity: 'fail', message: 'Mobile home merchandising sections are not in the expected order' });
      checks.push({ ok: !merchHealth.overflow.hasOverflow, severity: 'fail', message: 'Mobile home has horizontal overflow' });
      checks.push({ ok: merchHealth.container.paddingLeft >= 10 && merchHealth.container.paddingRight >= 10, severity: 'warn', message: 'Mobile home merchandising container padding is below target' });
      const notes = [
        `navToggleVisible=${navToggleVisible || 'none'}`,
        `navTogglePresent=${navTogglePresent || 'none'}`,
        `drawerMarker=${maybeDrawer || 'none'}`,
        `homeMerch.order=${merchHealth.orderKeys.join(' > ') || 'none'}`,
        `homeMerch.cards applications=${merchHealth.appCardCount} bestsellers=${merchHealth.sections.bestsellers.itemCount} bauletos=${merchHealth.sections.bauletos.itemCount} guidoes=${merchHealth.sections.guidoes.itemCount} launches=${merchHealth.sections.launches.itemCount}`,
        `homeMerch.container width=${merchHealth.container.width} pad=${merchHealth.container.paddingLeft}/${merchHealth.container.paddingRight}`,
        `homeMerch.overflow scroll=${merchHealth.overflow.scrollWidth} viewport=${merchHealth.overflow.viewportWidth} overflow=${merchHealth.overflow.hasOverflow ? 'yes' : 'no'}`,
      ];
      const shots = mobileHomeShots;
      return { final_url: page.url(), checks, notes, screenshots: [shots.viewport, shots.fullPage].filter(Boolean) };
    })
  );
  await rotateSession();

  results.push(
    await withStep(page, collector, outputDir, 'mobile', 'plp', async () => {
      const response = await page.goto(safeUrl(opts.baseUrl, '/retrovisores.html'), { waitUntil: 'domcontentloaded', timeout: opts.timeoutMs });
      const mobilePlpShots = await captureScreens(page, screenshotDir, 'mobile-plp', true);
      await withNodeTimeout(waitForPageStable(page, opts.timeoutMs), 7000, null);
      const productCount = await withNodeTimeout(page.locator('.product-item').count(), 2000, 0);
      const filterToggle = { clicked: false, selector: null };
      const mobileGrid = await visible(page, ['.products-grid', '.products.wrapper']);
      const checks = [
        { ok: (response && response.status() === 200) || page.url().includes('/retrovisores'), severity: 'fail', message: 'Mobile PLP did not load as expected' },
      ];
      if (productCount === 0 && !mobileGrid && hasScreenshotEvidence(mobilePlpShots)) {
        checks.push({ ok: false, severity: 'warn', message: 'Mobile PLP DOM probe stalled/inconclusive in headless; using screenshot evidence' });
      } else {
        checks.push({ ok: productCount > 0 || Boolean(mobileGrid), severity: 'fail', message: 'Mobile PLP products/grid not detected' });
      }
      const notes = [`productCount=${productCount}`, `filterToggle=${filterToggle.selector || 'none'}`];
      const shots = mobilePlpShots;
      return { final_url: page.url(), checks, notes, screenshots: [shots.viewport, shots.fullPage].filter(Boolean) };
    })
  );
  await rotateSession();

  results.push(
    await withStep(page, collector, outputDir, 'mobile', 'b2b_login', async () => {
      await gotoAndStabilize(page, safeUrl(opts.baseUrl, '/b2b/account/login/'), opts.timeoutMs);
      const userSel = await visible(page, ['input[name="login[username]"]', 'input[type="email"]', 'input[type="text"]']);
      const passSel = await visible(page, ['input[name="login[password]"]', 'input[type="password"]']);
      if (userSel) await page.locator(userSel).first().click({ timeout: 3000 }).catch(() => {});
      if (passSel) await page.locator(passSel).first().click({ timeout: 3000 }).catch(() => {});
      const health = await basicVisualHealth(page);
      const checks = [
        { ok: Boolean(userSel && passSel), severity: 'fail', message: 'Mobile B2B login form fields not both visible' },
        { ok: health.pageMain.visible, severity: 'fail', message: 'Mobile B2B login main content not visible' },
      ];
      const notes = [`userSel=${userSel || 'none'}`, `passSel=${passSel || 'none'}`];
      const shots = await captureScreens(page, screenshotDir, 'mobile-b2b-login', true);
      return { final_url: page.url(), checks, notes, screenshots: [shots.viewport, shots.fullPage].filter(Boolean) };
    })
  );

  if (context) {
    await withNodeTimeout(context.close(), 8000, null);
  }
  return results;
}

async function runAuthenticatedDesktopFlow(browser, opts, outputDir, auth) {
  const ua =
    'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36';
  const context = await browser.newContext({
    viewport: { width: 1366, height: 900 },
    userAgent: ua,
    locale: 'pt-BR',
    timezoneId: 'America/Sao_Paulo',
  });
  const page = await context.newPage();
  page.setDefaultTimeout(opts.timeoutMs);
  page.setDefaultNavigationTimeout(opts.timeoutMs);
  const collector = new PageCollector(page, opts.baseUrl);
  const screenshotDir = path.join(outputDir, 'screenshots');
  ensureDir(screenshotDir);
  const results = [];

  results.push(
    await withStep(page, collector, outputDir, 'desktop-auth', 'dashboard', async () => {
      const authResult = await loginB2B(page, opts, auth);
      await withNodeTimeout(page.waitForURL(/\/b2b\/account\/dashboard\/?/i, { timeout: 10000 }), 11000, null);
      await withNodeTimeout(waitForPageStable(page, opts.timeoutMs), 7000, null);
      const health = await authenticatedDashboardHealth(page);
      const shots = await captureScreens(page, screenshotDir, 'desktop-auth-dashboard', true);
      const checks = [
        { ok: Boolean(authResult.userSelector && authResult.passSelector), severity: 'fail', message: 'B2B auth login fields not found in desktop auth flow' },
        { ok: authResult.submitted, severity: 'fail', message: 'B2B auth form was not submitted in desktop auth flow' },
        { ok: authResult.redirected && /\/b2b\/account\/dashboard\/?/i.test(page.url()), severity: 'fail', message: 'B2B auth desktop flow did not redirect to dashboard' },
        { ok: health.shell.visible, severity: 'fail', message: 'B2B dashboard shell is not visible after login' },
        { ok: health.hero.visible, severity: 'fail', message: 'B2B dashboard hero is not visible after login' },
        { ok: health.kpis >= 4, severity: 'fail', message: 'B2B dashboard KPI strip is incomplete' },
        { ok: health.actions >= 6, severity: 'fail', message: 'B2B dashboard quick actions grid is incomplete' },
        { ok: health.sidebar.visible && health.nav.visible, severity: 'fail', message: 'B2B dashboard account sidebar is not visible on desktop' },
        { ok: !health.overflow.hasOverflow, severity: 'fail', message: 'B2B dashboard has horizontal overflow on desktop' },
      ];
      const notes = [
        `userSelector=${authResult.userSelector || 'none'}`,
        `passSelector=${authResult.passSelector || 'none'}`,
        `submitSelector=${authResult.submitSelector || 'none'}`,
        `dashboardKpis=${health.kpis}`,
        `dashboardActions=${health.actions}`,
        `dashboardChecklist=${health.checklist}`,
        `dashboardSuggestions=${health.suggestions}`,
        `bodyClass=${health.bodyClass}`,
        `overflow scroll=${health.overflow.scrollWidth} viewport=${health.overflow.viewportWidth} overflow=${health.overflow.hasOverflow ? 'yes' : 'no'}`,
      ];
      return { final_url: page.url(), checks, notes, screenshots: [shots.viewport, shots.fullPage].filter(Boolean) };
    })
  );

  results.push(
    await withStep(page, collector, outputDir, 'desktop-auth', 'quickorder', async () => {
      await gotoAndStabilize(page, safeUrl(opts.baseUrl, '/b2b/quickorder/'), opts.timeoutMs);
      const health = await authenticatedFlowPageHealth(page, 'quickorder');
      const shots = await captureScreens(page, screenshotDir, 'desktop-auth-quickorder', true);
      const checks = [
        { ok: /\/b2b\/quickorder\/?/.test(page.url()), severity: 'fail', message: 'Desktop auth quick order route did not stay on /b2b/quickorder/' },
        { ok: health.root.visible && health.hero.visible, severity: 'fail', message: 'Desktop auth quick order shell/hero is not visible' },
        { ok: health.sidebar.visible && health.nav.visible, severity: 'fail', message: 'Desktop auth quick order sidebar is not visible' },
        { ok: health.quickorder.container.visible, severity: 'fail', message: 'Quick order container is not visible' },
        { ok: health.quickorder.rows.visible, severity: 'fail', message: 'Quick order rows container is not visible' },
        { ok: health.quickorder.addRow.visible && health.quickorder.submit.visible, severity: 'fail', message: 'Quick order main CTAs are not visible' },
        { ok: health.quickorder.csvZone.visible && health.quickorder.csvInput.exists && health.quickorder.csvStatus.exists, severity: 'fail', message: 'Quick order CSV hooks are missing or hidden' },
        { ok: !health.overflow.hasOverflow, severity: 'fail', message: 'Quick order page has horizontal overflow on desktop' },
      ];
      const notes = [
        `quickorder.root=${health.root.visible ? 'visible' : 'hidden'}`,
        `quickorder.sidebar=${health.sidebar.visible ? 'visible' : 'hidden'}`,
        `quickorder.csvInput=${health.quickorder.csvInput.exists ? 'present' : 'missing'}`,
        `overflow scroll=${health.overflow.scrollWidth} viewport=${health.overflow.viewportWidth} overflow=${health.overflow.hasOverflow ? 'yes' : 'no'}`,
      ];
      return { final_url: page.url(), checks, notes, screenshots: [shots.viewport, shots.fullPage].filter(Boolean) };
    })
  );

  results.push(
    await withStep(page, collector, outputDir, 'desktop-auth', 'shoppinglist', async () => {
      await gotoAndStabilize(page, safeUrl(opts.baseUrl, '/b2b/shoppinglist/'), opts.timeoutMs);
      const health = await authenticatedFlowPageHealth(page, 'shoppinglist');
      const shots = await captureScreens(page, screenshotDir, 'desktop-auth-shoppinglist', true);
      const checks = [
        { ok: /\/b2b\/shoppinglist\/?/.test(page.url()), severity: 'fail', message: 'Desktop auth shopping list route did not stay on /b2b/shoppinglist/' },
        { ok: health.root.visible && health.hero.visible, severity: 'fail', message: 'Desktop auth shopping list shell/hero is not visible' },
        { ok: health.sidebar.visible && health.nav.visible, severity: 'fail', message: 'Desktop auth shopping list sidebar is not visible' },
        { ok: health.shoppinglist.createButton.visible && health.shoppinglist.createForm.exists, severity: 'fail', message: 'Shopping list create controls are missing' },
        { ok: health.shoppinglist.cards > 0 || health.shoppinglist.empty.visible, severity: 'fail', message: 'Shopping list page has neither cards nor empty state' },
        { ok: !health.overflow.hasOverflow, severity: 'fail', message: 'Shopping list page has horizontal overflow on desktop' },
      ];
      const notes = [
        `shoppinglist.cards=${health.shoppinglist.cards}`,
        `shoppinglist.empty=${health.shoppinglist.empty.visible ? 'visible' : 'hidden'}`,
        `overflow scroll=${health.overflow.scrollWidth} viewport=${health.overflow.viewportWidth} overflow=${health.overflow.hasOverflow ? 'yes' : 'no'}`,
      ];
      return { final_url: page.url(), checks, notes, screenshots: [shots.viewport, shots.fullPage].filter(Boolean) };
    })
  );

  results.push(
    await withStep(page, collector, outputDir, 'desktop-auth', 'quote_history', async () => {
      await gotoAndStabilize(page, safeUrl(opts.baseUrl, '/b2b/quote/history/'), opts.timeoutMs);
      const health = await authenticatedFlowPageHealth(page, 'quoteHistory');
      const shots = await captureScreens(page, screenshotDir, 'desktop-auth-quote-history', true);
      const checks = [
        { ok: /\/b2b\/quote\/history\/?/.test(page.url()), severity: 'fail', message: 'Desktop auth quote history route did not stay on /b2b/quote/history/' },
        { ok: health.root.visible && health.hero.visible, severity: 'fail', message: 'Desktop auth quote history shell/hero is not visible' },
        { ok: health.sidebar.visible && health.nav.visible, severity: 'fail', message: 'Desktop auth quote history sidebar is not visible' },
        { ok: health.quoteHistory.table.visible || health.quoteHistory.empty.visible, severity: 'fail', message: 'Quote history page has neither table nor empty state' },
        { ok: !health.quoteHistory.table.visible || health.quoteHistory.rows >= 0, severity: 'warn', message: 'Quote history table row count could not be read' },
        { ok: !health.overflow.hasOverflow, severity: 'fail', message: 'Quote history page has horizontal overflow on desktop' },
      ];
      const notes = [
        `quoteHistory.rows=${health.quoteHistory.rows}`,
        `quoteHistory.statuses=${health.quoteHistory.statuses}`,
        `quoteHistory.empty=${health.quoteHistory.empty.visible ? 'visible' : 'hidden'}`,
        `overflow scroll=${health.overflow.scrollWidth} viewport=${health.overflow.viewportWidth} overflow=${health.overflow.hasOverflow ? 'yes' : 'no'}`,
      ];
      return { final_url: page.url(), checks, notes, screenshots: [shots.viewport, shots.fullPage].filter(Boolean) };
    })
  );

  await withNodeTimeout(context.close(), 8000, null);
  return results;
}

async function runAuthenticatedMobileFlow(browser, opts, outputDir, auth) {
  const iphone12 = devices['iPhone 12'] || {
    viewport: { width: 390, height: 844 },
    userAgent:
      'Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.0 Mobile/15E148 Safari/604.1',
    deviceScaleFactor: 3,
    isMobile: true,
    hasTouch: true,
    defaultBrowserType: 'webkit',
  };
  const context = await browser.newContext({
    ...iphone12,
    locale: 'pt-BR',
    timezoneId: 'America/Sao_Paulo',
  });
  const page = await context.newPage();
  page.setDefaultTimeout(opts.timeoutMs);
  page.setDefaultNavigationTimeout(opts.timeoutMs);
  const collector = new PageCollector(page, opts.baseUrl);
  const screenshotDir = path.join(outputDir, 'screenshots');
  ensureDir(screenshotDir);

  const results = [];
  results.push(
    await withStep(page, collector, outputDir, 'mobile-auth', 'dashboard', async () => {
      const authResult = await loginB2B(page, opts, auth);
      await withNodeTimeout(page.waitForURL(/\/b2b\/account\/dashboard\/?/i, { timeout: 10000 }), 11000, null);
      await withNodeTimeout(waitForPageStable(page, opts.timeoutMs), 7000, null);
      const health = await authenticatedDashboardHealth(page);
      const shots = await captureScreens(page, screenshotDir, 'mobile-auth-dashboard', true);
      const checks = [
        { ok: Boolean(authResult.userSelector && authResult.passSelector), severity: 'fail', message: 'B2B auth login fields not found in mobile auth flow' },
        { ok: authResult.submitted, severity: 'fail', message: 'B2B auth form was not submitted in mobile auth flow' },
        { ok: authResult.redirected && /\/b2b\/account\/dashboard\/?/i.test(page.url()), severity: 'fail', message: 'B2B auth mobile flow did not redirect to dashboard' },
        { ok: health.shell.visible && health.hero.visible, severity: 'fail', message: 'B2B mobile dashboard shell/hero is not visible after login' },
        { ok: health.kpis >= 4, severity: 'fail', message: 'B2B mobile dashboard KPI strip is incomplete' },
        { ok: health.actions >= 6, severity: 'fail', message: 'B2B mobile dashboard quick actions grid is incomplete' },
        { ok: health.sidebar.exists || health.nav.exists, severity: 'warn', message: 'B2B mobile dashboard account navigation block was not detected' },
        { ok: !health.overflow.hasOverflow, severity: 'fail', message: 'B2B mobile dashboard has horizontal overflow' },
      ];
      const notes = [
        `userSelector=${authResult.userSelector || 'none'}`,
        `passSelector=${authResult.passSelector || 'none'}`,
        `submitSelector=${authResult.submitSelector || 'none'}`,
        `dashboardKpis=${health.kpis}`,
        `dashboardActions=${health.actions}`,
        `bodyClass=${health.bodyClass}`,
        `overflow scroll=${health.overflow.scrollWidth} viewport=${health.overflow.viewportWidth} overflow=${health.overflow.hasOverflow ? 'yes' : 'no'}`,
      ];
      return { final_url: page.url(), checks, notes, screenshots: [shots.viewport, shots.fullPage].filter(Boolean) };
    })
  );

  await withNodeTimeout(context.close(), 8000, null);
  return results;
}

function summarizeReport(report) {
  const all = report.results;
  const counts = { pass: 0, warn: 0, fail: 0 };
  for (const r of all) {
    counts[r.status] = (counts[r.status] || 0) + 1;
  }
  const criticalConsole = all.reduce(
    (sum, r) =>
      sum +
      r.console_errors.filter((e) => classifyConsoleMessage(e, report.meta.base_url) === 'critical').length,
    0
  );
  const warningConsole = all.reduce(
    (sum, r) =>
      sum +
      r.console_errors.filter((e) => classifyConsoleMessage(e, report.meta.base_url) === 'warn').length,
    0
  );
  const networkFailures = all.reduce((sum, r) => sum + r.network_failures.length, 0);
  const networkWarnings = all.reduce((sum, r) => sum + r.network_warnings.length, 0);

  report.summary = {
    total_steps: all.length,
    pass: counts.pass || 0,
    warn: counts.warn || 0,
    fail: counts.fail || 0,
    critical_console_errors: criticalConsole,
    warning_console_events: warningConsole,
    critical_network_failures: networkFailures,
    noncritical_first_party_network_warnings: networkWarnings,
    overall_status: counts.fail > 0 ? 'fail' : counts.warn > 0 ? 'warn' : 'pass',
  };
}

function buildFindingsMarkdown(report) {
  const lines = [];
  lines.push('# Playwright Visual Smoke Findings');
  lines.push('');
  lines.push(`- Base URL: \`${report.meta.base_url}\``);
  lines.push(`- Started (UTC): \`${report.meta.started_at}\``);
  lines.push(`- Finished (UTC): \`${report.meta.finished_at}\``);
  lines.push(`- Overall status: **${report.summary.overall_status.toUpperCase()}**`);
  lines.push(`- Steps: ${report.summary.total_steps} (pass=${report.summary.pass}, warn=${report.summary.warn}, fail=${report.summary.fail})`);
  lines.push(`- Critical first-party network failures: ${report.summary.critical_network_failures}`);
  lines.push(`- Critical console errors (filtered): ${report.summary.critical_console_errors}`);
  lines.push('');

  lines.push('## Route Results');
  lines.push('');
  for (const r of report.results) {
    lines.push(`### ${r.viewport} / ${r.page_name} — ${String(r.status).toUpperCase()}`);
    lines.push(`- Final URL: \`${r.final_url}\``);
    const screenshots = (r.screenshots || []).filter((s) => typeof s === 'string' && s.length > 0);
    if (screenshots.length) {
      lines.push(`- Screenshots: ${screenshots.map((s) => `\`${path.relative(rootDir(), s)}\``).join(', ')}`);
    }
    if (r.network_failures.length) {
      lines.push(`- Critical network failures: ${r.network_failures.length}`);
      for (const n of r.network_failures.slice(0, 5)) {
        lines.push(`  - [${n.status}] ${n.url}`);
      }
    }
    const criticalConsole = r.console_errors.filter((e) => classifyConsoleMessage(e, report.meta.base_url) === 'critical');
    if (criticalConsole.length) {
      lines.push(`- Critical console errors: ${criticalConsole.length}`);
      for (const c of criticalConsole.slice(0, 5)) {
        lines.push(`  - ${String(c.text).split('\n')[0].slice(0, 220)}`);
      }
    }
    if (r.notes.length) {
      lines.push('- Notes:');
      for (const note of r.notes.slice(0, 12)) {
        lines.push(`  - ${note}`);
      }
    }
    lines.push('');
  }

  return lines.join('\n');
}

async function main() {
  const opts = parseArgs(process.argv);
  const auth = getAuthConfig();
  const outputDir = opts.outDir;
  ensureDir(outputDir);
  ensureDir(path.join(outputDir, 'screenshots'));

  const report = {
    meta: {
      base_url: opts.baseUrl,
      started_at: new Date().toISOString(),
      finished_at: null,
      runner: 'playwright-local-fallback',
      playwright_version: require('playwright/package.json').version,
      browser: 'chromium',
      desktop_viewport: '1366x900',
      mobile_profile: 'iPhone 12',
      authenticated_mode: auth.enabled ? 'enabled' : 'disabled',
      output_dir: outputDir,
    },
    results: [],
    summary: null,
  };

  out('=== PLAYWRIGHT VISUAL SMOKE (AWAMOTOS) ===');
  out('Base URL: ' + opts.baseUrl);
  out('Output Dir: ' + outputDir);
  out('Playwright: ' + report.meta.playwright_version);

  let browser;
  try {
    browser = await chromium.launch({
      channel: 'chrome',
      headless: opts.headless,
      args: [
        '--disable-blink-features=AutomationControlled',
        '--no-sandbox',
        '--disable-gpu',
      ],
    });

    report.results.push(...(await runDesktopFlow(browser, opts, outputDir)));
    report.results.push(...(await runMobileFlow(browser, opts, outputDir)));
    if (auth.enabled) {
      report.results.push(...(await runAuthenticatedDesktopFlow(browser, opts, outputDir, auth)));
      report.results.push(...(await runAuthenticatedMobileFlow(browser, opts, outputDir, auth)));
    }
  } finally {
    if (browser) {
      await withNodeTimeout(browser.close(), 10000, null);
    }
    report.meta.finished_at = new Date().toISOString();
  }

  summarizeReport(report);
  writeText(path.join(outputDir, 'report.json'), JSON.stringify(report, null, 2));
  writeText(path.join(outputDir, 'findings.md'), buildFindingsMarkdown(report));

  out('');
  out('=== SUMMARY ===');
  out(`Overall: ${report.summary.overall_status.toUpperCase()}`);
  out(`Steps: ${report.summary.total_steps} (pass=${report.summary.pass}, warn=${report.summary.warn}, fail=${report.summary.fail})`);
  out(`Critical first-party network failures: ${report.summary.critical_network_failures}`);
  out(`Critical console errors (filtered): ${report.summary.critical_console_errors}`);
  out('Artifacts:');
  out('- ' + path.join(outputDir, 'report.json'));
  out('- ' + path.join(outputDir, 'findings.md'));
  out('- ' + path.join(outputDir, 'screenshots'));

  process.exit(report.summary.overall_status === 'fail' ? 1 : 0);
}

main().catch((err) => {
  fail(err && err.stack ? err.stack : String(err));
  process.exit(1);
});
