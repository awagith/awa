/**
 * ===========================================
 * AWA MOTOS - JAVASCRIPT MASTER FIX (v2 — Consolidated)
 * Tema: AYO/Rokanthemes
 *
 * VERSÃO LIMPA: Removidas funções cujas causas-raiz
 * agora são tratadas por CSS consolidado (awa-core/layout/components/fixes).
 *
 * REMOVIDOS:
 *   - hideSoldInfo()          → CSS: .sold-qty, .vendor-name { display:none }
 *   - fixButtonPosition()     → CSS: flex order em product cards
 *   - fixDuplicatedProductNames() → CSS: product name display
 *   - fixPlaceholderHoverImages() → CSS: FIX-03 em awa-fixes.css
 *   - hideEmptyHeadings()     → CSS: FIX-05 em awa-fixes.css
 *   - fixProductCardHeight()  → CSS: grid layout em awa-components.css
 *   - initLazyLoad()          → delegado ao Rokanthemes theme.js
 *
 * MANTIDOS (comportamento de runtime não-substituível por CSS):
 *   - fixPrices()             → R$ 0,01 → "Consulte" (lógica de negócio)
 *   - translateTexts()        → pt_BR fallback (remover ao instalar i18n pack)
 *   - hideMagentoCode()       → Esconde {{block}} leaks em runtime
 *   - addInputMasks()         → Máscaras de telefone/CEP/CPF
 *   - initBackToTop()         → Cria botão + scroll listener
 *   - initWhatsAppButton()    → Cria botão WhatsApp flutuante
 *   - initStickyHeaderSpacer()→ MutationObserver p/ --awa-header-height
 *   - initMobileNavClose()    → Overlay + close + ESC handler
 *   - deduplicateHorizontalNav() → Remove itens duplicados por href
 *   - fixVerticalMenuToggles()→ A11y: role, tabindex, aria-expanded
 *   - fixSocialShareAlts()    → A11y: alt text para share images
 *   - fixNavToggleLabel()     → A11y: aria-label para nav toggle
 *   - fixReviewCount()        → Texto: "0 Avaliação" → "Seja o primeiro"
 *   - addSkipToMain()         → A11y: skip-to-main link
 *   - fixSliderAltText()      → A11y: alt text para slider
 *   - hideEmptyImages()       → Esconde img[src=""] carregadas dinamicamente
 *   - fixAyoModuleAlignment() → Fix inline-styles do Magento widgets
 *   - sanitizeEscapedProductImageCssText() → Remove CSS text leaked no DOM
 *   - OWL Tab Carousel fixes  → normalizeOwlItemClasses + refreshHomeTabCarousels
 *
 * INSTALAÇÃO:
 * Copiar para: app/design/frontend/ayo/ayo_home5/web/js/awa-master-fix.js
 * ===========================================
 */

(function () {
    'use strict';

    var AWA_CONFIG = {
        debug: false,
        hidePrice001: true,
        translateTexts: true,  // TEMPORÁRIO — remover ao instalar pt_BR pack
        hideMagentoCode: true,
        deduplicateMenu: true, // Workaround: AYO pode gerar itens duplicados no menu horizontal
        whatsappNumber: '5516997367588', /* R11-03: centralizado */
        whatsappMessage: 'Olá! Vim pelo site AWA Motos e gostaria de mais informações.',
        // Layout — lidos via CSS custom properties quando disponíveis
        containerWidth: 1200,
        breakpoints: {
            desktopXL: 1400,        // AYO: ≥1400px (5 cols, container 1600px)
            desktop: 1200,
            desktopSmall: 992,
            tabletLandscape: 1199,  // AYO: ≤1199px
            tablet: 768,
            mobile: 480,
            mobileXS: 375,
            mobileXXS: 320
        },
        owlItems: {
            desktopXL: 5,   // AYO XL: 5 produtos
            desktop: 4,
            desktopSmall: 3,
            tablet: 2,
            mobile: 1
        },
        /* R17-11: timing constants — evita magic numbers */
        timings: {
            mutationDebounce: 350,
            resizeDebounce: 250,
            stickyResizeDebounce: 150,
            focusDelay: 300,
            tabRefreshDelay: 120,
            popupFocusDelay: 150,
            owlRetryInterval: 300
        }
    };

    /* R17-09: cache do .page-wrapper para evitar 15+ lookups redundantes */
    var _pageWrapper = null;
    function getPageWrapper() {
        if (!_pageWrapper || !_pageWrapper.isConnected) {
            _pageWrapper = document.querySelector('.page-wrapper');
        }
        return _pageWrapper || document.body;
    }

    /**
     * Lê --awa-container do CSS (fonte única de verdade).
     * CSS já faz o escalonamento via @media (1600px em XL, 1200px em desktop).
     * Fallback: AWA_CONFIG.containerWidth.
     */
    function getContainerWidth() {
        var cssVal = getComputedStyle(document.documentElement)
            .getPropertyValue('--awa-container');
        if (cssVal) {
            var trimmed = cssVal.trim();
            /* R17-01: se o valor é percentual (ex: "100%" em ≤1199px), usar viewport */
            if (trimmed.indexOf('%') !== -1) {
                return window.innerWidth;
            }
            var parsed = parseInt(trimmed, 10);
            if (parsed > 0) return parsed;
        }
        // Fallback dinâmico: 1600 em XL, 1200 caso contrário
        if (window.innerWidth >= AWA_CONFIG.breakpoints.desktopXL) {
            return 1600;
        }
        return AWA_CONFIG.containerWidth;
    }

    function log(msg) {
        if (AWA_CONFIG.debug) {
            console.log('[AWA Fix]', msg);
        }
    }

    /* R10-01: Translations hoisted to module scope (avoids re-allocation per call) */
    var AWA_TRANSLATIONS = {
        'Add to Cart': 'Adicionar ao Carrinho',
        'ADD TO CART': 'ADICIONAR AO CARRINHO',
        'Add to Wishlist': 'Adicionar \u00e0 Lista de Desejos',
        'Add to Compare': 'Comparar',
        'SSL Secure Connection': 'Conex\u00e3o SSL Segura',
        'Google Safe Browsing': 'Navega\u00e7\u00e3o Segura Google',
        'Out of stock': 'Esgotado',
        'OUT OF STOCK': 'ESGOTADO',
        'In stock': 'Em estoque',
        'IN STOCK': 'EM ESTOQUE',
        'Load more': 'Carregar mais',
        'LOAD MORE': 'CARREGAR MAIS',
        'Quick View': 'Ver Detalhes',
        'QUICK VIEW': 'VER DETALHES',
        'Review': 'Avalia\u00e7\u00e3o',
        'Reviews': 'Avalia\u00e7\u00f5es',
        'No reviews': 'Sem avalia\u00e7\u00f5es',
        'Be the first to review': 'Seja o primeiro a avaliar',
        'Qty': 'Qtd',
        'Quantity': 'Quantidade',
        'Subtotal': 'Subtotal',
        'View Cart': 'Ver Carrinho',
        'VIEW CART': 'VER CARRINHO',
        'Checkout': 'Finalizar Compra',
        'CHECKOUT': 'FINALIZAR COMPRA',
        'Proceed to Checkout': 'Finalizar Compra',
        'Continue Shopping': 'Continuar Comprando',
        'Apply': 'Aplicar',
        'Remove': 'Remover',
        'Update': 'Atualizar',
        'Clear All': 'Limpar Tudo',
        'Compare Products': 'Comparar Produtos',
        'My Wishlist': 'Minha Lista de Desejos',
        'My Cart': 'Meu Carrinho',
        'Search': 'Buscar',
        'Sign In': 'Entrar',
        'Sign Out': 'Sair',
        'Create an Account': 'Criar Conta',
        'Forgot Password': 'Esqueci minha senha',
        'Email Address': 'E-mail',
        'Password': 'Senha',
        'Confirm Password': 'Confirmar Senha',
        'First Name': 'Nome',
        'Last Name': 'Sobrenome',
        'Subscribe': 'Inscrever-se',
        'SUBSCRIBE': 'INSCREVER-SE',
        'Newsletter': 'Newsletter',
        'Sort By': 'Ordenar por',
        'Show': 'Mostrar',
        'per page': 'por p\u00e1gina',
        'Items': 'Itens',
        'Item': 'Item',
        'of': 'de',
        'Page': 'P\u00e1gina',
        'Next': 'Pr\u00f3ximo',
        'Previous': 'Anterior',
        'Home': 'In\u00edcio',
        'Shop': 'Loja',
        'Contact': 'Contato',
        'About Us': 'Sobre N\u00f3s',
        'Categories': 'Categorias',
        'All Categories': 'Todas as Categorias',
        'Customer Service': 'Atendimento ao Cliente',
        'Help': 'Ajuda',
        'FAQ': 'Perguntas Frequentes',
        'Shipping': 'Frete',
        'Returns': 'Devolu\u00e7\u00f5es',
        'Privacy Policy': 'Pol\u00edtica de Privacidade',
        'Terms of Service': 'Termos de Servi\u00e7o',
        'Order Status': 'Status do Pedido',
        'Track Order': 'Rastrear Pedido',
        'Entering your email also subscribe you to the latest Netro shop news and offers.':
            'Ao cadastrar seu e-mail, voc\u00ea receber\u00e1 novidades e ofertas exclusivas.',
        'Do not shop this popup again': 'N\u00e3o mostrar novamente',
        'Do not show this popup again': 'N\u00e3o mostrar novamente',
        'Alternar Nav': 'Menu',
        'Toggle Nav': 'Menu'
    };

    /* R10-09: Regex hoisted to module scope (avoids re-compilation per call) */
    var RE_CSS_LEAK = /^\s*\.product-image-container-\d+\s*\{[^}]*width:\s*\d+px/i;

    // ===========================================
    // 1. CORRIGIR PREÇOS R$ 0,01 → "Consulte"
    // Lógica de negócio: produtos sem preço real exibem 0,01.
    // ===========================================
    function fixPrices(roots) {
        if (!AWA_CONFIG.hidePrice001) return;

        var searchRoots = roots && roots.length
            ? roots
            : [getPageWrapper()];

        searchRoots.forEach(function (root) {
            if (!root || !root.querySelectorAll) return;
            root.querySelectorAll('.price').forEach(function (el) {
                var text = el.textContent.trim();
                if (text === 'R$ 0,01' || text === 'R$0,01' || text === 'R$ 0.01') {
                    el.textContent = 'Consulte';
                    el.setAttribute('aria-label', 'Preço sob consulta');
                    el.classList.add('awa-price-consulte');
                    log('Fixed price: ' + text);
                }
            });
        });
    }

    // ===========================================
    // 2. TRADUZIR TEXTOS EM INGLÊS
    // TEMPORÁRIO — substituir por pacote i18n pt_BR oficial.
    // ===========================================
    function translateTexts(roots) {
        if (!AWA_CONFIG.translateTexts) return;

        var translations = AWA_TRANSLATIONS; /* R10-01: referencia constante do módulo */

        /* Percorre apenas os roots fornecidos (MutationObserver) ou page-wrapper inteiro (init) */
        var searchRoots = roots && roots.length
            ? roots
            : [getPageWrapper()];

        searchRoots.forEach(function (root) {
            /* Ignora nós de texto soltos (não-Element) passados como root */
            if (!root || !root.querySelectorAll) return;

            var walker = document.createTreeWalker(
                root,
                NodeFilter.SHOW_TEXT,
                /* R18-01: skip text inside <script>/<style>/<textarea> to prevent corruption */
                { acceptNode: function (n) {
                    var t = n.parentElement ? n.parentElement.tagName : '';
                    return (t === 'SCRIPT' || t === 'STYLE' || t === 'TEXTAREA')
                        ? NodeFilter.FILTER_REJECT : NodeFilter.FILTER_ACCEPT;
                }},
                false
            );

            var node;
            while ((node = walker.nextNode())) {
                var text = node.textContent.trim();
                if (!text || text.length < 2) continue; /* R15-10: skip whitespace/single-char nodes */
                if (translations[text]) {
                    node.textContent = node.textContent.replace(text, translations[text]);
                }
            }

            root.querySelectorAll('input[placeholder], textarea[placeholder]').forEach(function (el) {
                var placeholder = el.getAttribute('placeholder');
                if (translations[placeholder]) {
                    el.setAttribute('placeholder', translations[placeholder]);
                }
            });

            /* R9-18: Restrict title translation to interactive elements only */
            root.querySelectorAll('a[title], button[title], .action[title], [role="button"][title], input[title]').forEach(function (el) {
                var title = el.getAttribute('title');
                if (translations[title]) {
                    el.setAttribute('title', translations[title]);
                }
            });
        });
    }

    // ===========================================
    // 3. ESCONDER CÓDIGO MAGENTO EXPOSTO
    // ===========================================
    function hideMagentoCode(roots) {
        if (!AWA_CONFIG.hideMagentoCode) return;

        // Patterns específicos de diretivas Magento — 'template="' removido
        // por alto risco de falso positivo em conteúdo legítimo
        var patterns = ['{{block', '{{widget', '{{store', '{{media', 'class="Magento'];

        /* R9-01: Aceita roots do MutationObserver para evitar full-DOM scan */
        var searchRoots;
        if (roots && roots.length) {
            searchRoots = roots;
        } else {
            /* R17-09: seletores já incluem .page-wrapper — query deve ser no document */
            searchRoots = document.querySelectorAll(
                '.page-wrapper .widget, .page-wrapper .block-cms-link, ' +
                '.page-wrapper .cms-page-view, .page-wrapper .homebuilder-section, ' +
                '.page-wrapper .block-static-block'
            );
            if (searchRoots.length === 0) {
                searchRoots = [getPageWrapper()];
            }
        }

        searchRoots.forEach(function (root) {
            if (!root || !root.querySelectorAll) return;
            var walker = document.createTreeWalker(
                root,
                NodeFilter.SHOW_TEXT,
                /* R18-01: skip text inside <script>/<style>/<textarea> to prevent corruption */
                { acceptNode: function (n) {
                    var t = n.parentElement ? n.parentElement.tagName : '';
                    return (t === 'SCRIPT' || t === 'STYLE' || t === 'TEXTAREA')
                        ? NodeFilter.FILTER_REJECT : NodeFilter.FILTER_ACCEPT;
                }},
                false
            );

            var node;
            while ((node = walker.nextNode())) {
                var text = node.textContent;
                if (!text || text.length < 5) continue;
                for (var i = 0; i < patterns.length; i++) {
                    if (text.indexOf(patterns[i]) !== -1) {
                        var parent = node.parentElement;
                        if (parent && !parent.classList.contains('awa-hidden-leak')) {
                            parent.classList.add('awa-hidden-leak');
                            log('Hidden Magento leak: ' + patterns[i]);
                        }
                        break;
                    }
                }
            }
        });
    }

    // ===========================================
    // 4. MÁSCARAS DE INPUT (Telefone, CEP, CPF/CNPJ)
    // ===========================================
    function addInputMasks(roots) {
        var searchRoots = roots && roots.length
            ? roots
            : [getPageWrapper()];

        searchRoots.forEach(function (root) {
            if (!root || !root.querySelectorAll) return;

            root.querySelectorAll('input[name="telephone"], input[type="tel"]').forEach(function (input) {
            if (input.dataset.awaMask) return;
            input.dataset.awaMask = '1';
            function maskTel(e) {
                var value = e.target.value.replace(/\D/g, '');
                if (value.length === 0) { e.target.value = ''; return; }
                if (value.length <= 2) {
                    value = '(' + value;
                } else if (value.length <= 7) {
                    value = '(' + value.substring(0, 2) + ') ' + value.substring(2);
                } else {
                    value = '(' + value.substring(0, 2) + ') ' + value.substring(2, 7) + '-' + value.substring(7, 11);
                }
                e.target.value = value;
            }
            input.addEventListener('input', maskTel);
            input.addEventListener('paste', function () {
                setTimeout(function () { maskTel({ target: input }); }, 0);
            });
        });

        root.querySelectorAll('input[name="postcode"], input[name*="cep"]').forEach(function (input) {
            if (input.dataset.awaMask) return;
            input.dataset.awaMask = '1';
            input.addEventListener('input', function (e) {
                var value = e.target.value.replace(/\D/g, '');
                if (value.length > 5) {
                    value = value.substring(0, 5) + '-' + value.substring(5, 8);
                }
                e.target.value = value;
            });
        });

        root.querySelectorAll('input[name*="cpf"], input[name*="cnpj"], input[name*="taxvat"]').forEach(function (input) {
            if (input.dataset.awaMask) return;
            input.dataset.awaMask = '1';
            input.addEventListener('input', function (e) {
                var value = e.target.value.replace(/\D/g, '');
                if (value.length <= 11) {
                    if (value.length > 9) {
                        value = value.substring(0, 3) + '.' + value.substring(3, 6) + '.' + value.substring(6, 9) + '-' + value.substring(9, 11);
                    } else if (value.length > 6) {
                        value = value.substring(0, 3) + '.' + value.substring(3, 6) + '.' + value.substring(6);
                    } else if (value.length > 3) {
                        value = value.substring(0, 3) + '.' + value.substring(3);
                    }
                } else {
                    value = value.substring(0, 2) + '.' + value.substring(2, 5) + '.' + value.substring(5, 8) + '/' + value.substring(8, 12) + '-' + value.substring(12, 14);
                }
                e.target.value = value;
            });
        });
        }); /* end searchRoots.forEach */

        log('Input masks initialized');
    }

    // ===========================================
    // 5. BACK TO TOP BUTTON
    // ===========================================
    function initBackToTop() {
        var btn = document.getElementById('awaBackToTop');
        var legacyBtn = document.getElementById('back-top');
        var fixedRightTopTriggers = document.querySelectorAll('.fixed-right .scroll-top');

        if (!btn && legacyBtn) {
            btn = legacyBtn;
            btn.classList.add('awa-backtotop-legacy');
            if (!btn.getAttribute('aria-label')) {
                btn.setAttribute('aria-label', 'Voltar ao topo');
            }
        }

        if (!btn) {
            btn = document.createElement('button');
            btn.id = 'awaBackToTop';
            btn.innerHTML = '&#8593;';
            btn.setAttribute('aria-label', 'Voltar ao topo');
            document.body.appendChild(btn);
        }

        function scrollToTop() {
            var motionOk = !window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            window.scrollTo({ top: 0, behavior: motionOk ? 'smooth' : 'auto' });
        }

        if (btn && btn.dataset.awaBackTopBound !== '1') {
            btn.dataset.awaBackTopBound = '1';
            btn.addEventListener('click', function (event) {
                event.preventDefault();
                scrollToTop();
            });
        }

        fixedRightTopTriggers.forEach(function (trigger) {
            if (trigger.dataset.awaBackTopBound === '1') return;
            trigger.dataset.awaBackTopBound = '1';
            if (!trigger.getAttribute('role')) {
                trigger.setAttribute('role', 'button');
            }
            if (!trigger.getAttribute('tabindex')) {
                trigger.setAttribute('tabindex', '0');
            }
            if (!trigger.getAttribute('aria-label')) {
                trigger.setAttribute('aria-label', 'Voltar ao topo');
            }
            trigger.addEventListener('click', function (event) {
                event.preventDefault();
                scrollToTop();
            });
            trigger.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    scrollToTop();
                }
            });
        });

        if (document.body.dataset.awaBackTopScrollBound !== '1') {
            document.body.dataset.awaBackTopScrollBound = '1';
            var scrollTicking = false;
            window.addEventListener('scroll', function () {
                if (!scrollTicking) {
                    window.requestAnimationFrame(function () {
                        if (btn) {
                            if (window.scrollY > 300) {
                                btn.classList.add('visible');
                            } else {
                                btn.classList.remove('visible');
                            }
                        }
                        scrollTicking = false;
                    });
                    scrollTicking = true;
                }
            }, { passive: true });
        }

        log('Back to top initialized');
    }

    // ===========================================
    // 6. WHATSAPP FLUTUANTE
    // ===========================================
    function initWhatsAppButton() {
        if (document.querySelector('.awa-whatsapp-float')) return;

        var whatsappNumber = AWA_CONFIG.whatsappNumber; /* R11-03: de AWA_CONFIG */
        var whatsappMessage = encodeURIComponent(AWA_CONFIG.whatsappMessage);

        var btn = document.createElement('a');
        btn.className = 'awa-whatsapp-float';
        btn.href = 'https://wa.me/' + whatsappNumber + '?text=' + whatsappMessage;
        btn.target = '_blank';
        btn.rel = 'noopener noreferrer';
        btn.setAttribute('aria-label', 'Contato via WhatsApp');
        btn.innerHTML = '<svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>';
        // Estilos geridos por CSS (.awa-whatsapp-float em awa-core.css)
        // Hover gerido por CSS (:hover) — sem mouseenter/mouseleave

        document.body.appendChild(btn);
        log('WhatsApp button initialized');
    }

    // ===========================================
    // 7. STICKY HEADER SPACER
    // ===========================================
    /* R11-09: lógica DRY com função extraída */
    function initStickyHeaderSpacer() {
        var stickyWrapper = document.querySelector('.header-wrapper-sticky');
        var header = document.querySelector('.page-header');
        var target = stickyWrapper || header;
        if (!target) return;

        function updateStickyHeight() {
            var isSticky = (stickyWrapper && stickyWrapper.classList.contains('enable-sticky')) ||
                           (header && (header.classList.contains('sticky') || header.classList.contains('fixed')));
            if (isSticky) {
                var h = (header || stickyWrapper).offsetHeight;
                document.documentElement.style.setProperty('--awa-header-height', h + 'px');
                document.body.classList.add('sticky-header-active'); /* BUG-08: ativa padding-top no body */
            } else {
                document.documentElement.style.setProperty('--awa-header-height', '0px');
                document.body.classList.remove('sticky-header-active'); /* BUG-08: remove padding-top */
            }
        }

        var headerObserver = new MutationObserver(updateStickyHeight);
        headerObserver.observe(target, { attributes: true, attributeFilter: ['class'] });

        var stickyResizeTimer;
        window.addEventListener('resize', function () {
            clearTimeout(stickyResizeTimer);
            stickyResizeTimer = setTimeout(updateStickyHeight, AWA_CONFIG.timings.stickyResizeDebounce);
        }, { passive: true });

        log('Sticky header spacer initialized');
    }

    // ===========================================
    // 8. MOBILE NAV OVERLAY + CLOSE + ESC
    // ===========================================
    function initMobileNavClose() {
        if (!document.querySelector('.awa-nav-overlay')) {
            var overlay = document.createElement('div');
            overlay.className = 'awa-nav-overlay';
            overlay.setAttribute('aria-hidden', 'true');
            document.body.appendChild(overlay);
            overlay.addEventListener('click', closeMobileNav);
        }

        var navSections = document.querySelector('.nav-sections');
        if (navSections && !navSections.querySelector('.awa-nav-close')) {
            var closeBtn = document.createElement('button');
            closeBtn.className = 'awa-nav-close';
            closeBtn.innerHTML = '&#10005;';
            closeBtn.setAttribute('aria-label', 'Fechar menu');
            navSections.insertBefore(closeBtn, navSections.firstChild);
            closeBtn.addEventListener('click', closeMobileNav);
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' &&
                (document.body.classList.contains('nav-open') ||
                 document.documentElement.classList.contains('nav-open'))) {
                closeMobileNav();
            }
            // Focus trap: Tab dentro do drawer
            if (e.key === 'Tab' &&
                (document.body.classList.contains('nav-open') ||
                 document.documentElement.classList.contains('nav-open'))) {
                trapFocusInNav(e);
            }
        });

        // Ao clicar no nav-toggle para abrir, foca o primeiro item
        var navToggle = document.querySelector('.nav-toggle, .action.nav-toggle');
        if (navToggle) {
            navToggle.addEventListener('click', function () {
                this.setAttribute('aria-expanded', 'true');
                var nav = document.querySelector('.nav-sections');
                if (nav) nav.setAttribute('aria-hidden', 'false');
                // Aguarda transição do drawer abrir
                setTimeout(focusFirstNavItem, AWA_CONFIG.timings.focusDelay);
            });
        }

        log('Mobile nav close + focus trap initialized');
    }

    /**
     * Prende o foco dentro de .nav-sections enquanto o drawer está aberto.
     * Ao abrir, foco vai para o primeiro elemento focável.
     */
    /* R10-03: Cache focusable elements — refreshed on nav open/close */
    var _navFocusableCache = null;

    function trapFocusInNav(e) {
        var navSections = document.querySelector('.nav-sections');
        if (!navSections) return;

        if (!_navFocusableCache || !_navFocusableCache.length) {
            _navFocusableCache = navSections.querySelectorAll(
                'a[href], button:not([disabled]), [tabindex="0"]'
            );
        }
        var focusable = _navFocusableCache;
        if (!focusable.length) return;

        var first = focusable[0];
        var last = focusable[focusable.length - 1];

        if (e.shiftKey) {
            if (document.activeElement === first || !navSections.contains(document.activeElement)) {
                e.preventDefault();
                last.focus();
            }
        } else {
            if (document.activeElement === last || !navSections.contains(document.activeElement)) {
                e.preventDefault();
                first.focus();
            }
        }
    }

    function focusFirstNavItem() {
        var navSections = document.querySelector('.nav-sections');
        if (!navSections) return;
        var first = navSections.querySelector('a[href], button:not([disabled]), [tabindex="0"]');
        if (first) first.focus();
    }

    function closeMobileNav() {
        document.documentElement.classList.remove('nav-open', 'nav-before-open');
        document.body.classList.remove('nav-open', 'nav-before-open');
        var navSections = document.querySelector('.nav-sections');
        if (navSections) {
            navSections.classList.remove('active');
            navSections.setAttribute('aria-hidden', 'true');
        }
        _navFocusableCache = null; /* R10-03: invalidate cache on close */
        var toggle = document.querySelector('.nav-toggle, .action.nav-toggle');
        if (toggle) {
            toggle.setAttribute('aria-expanded', 'false');
            /* R10-13: Only return focus if user was inside the drawer */
            if (navSections && navSections.contains(document.activeElement)) {
                toggle.focus();
            }
        }
    }

    // ===========================================
    // 9. ACCESSIBILITY FIXES
    // ===========================================
    function deduplicateHorizontalNav() {
        if (!AWA_CONFIG.deduplicateMenu) return;

        var nav = document.querySelector('.custommenu > ul, .navigation.custommenu > ul');
        if (!nav) return;
        var items = nav.querySelectorAll(':scope > li');
        var seenHrefs = {};
        items.forEach(function (li) {
            var a = li.querySelector(':scope > a');
            if (!a) return;
            var rawHref = a.getAttribute('href') || '';
            // Normaliza: remove protocolo+domínio, trailing slash, extensão .html
            var href;
            try {
                href = new URL(rawHref, location.origin).pathname;
            } catch (e) {
                href = rawHref;
            }
            href = href.replace(/\/+$/, '').replace(/\.html$/i, '').toLowerCase();
            if (!href) return; // ignora links vazios
            if (seenHrefs[href]) {
                li.classList.add('awa-hidden-leak');
                log('Dedup nav: hidden duplicate for ' + href);
            } else {
                seenHrefs[href] = true;
            }
        });
    }

    /**
     * AF-04: Deduplica seções de produto na homepage.
     *
     * Se a CMS page renderizar widgets que duplicam seções já presentes
     * em top-home.phtml (e.g., "Novidades"/"New Products"), a segunda
     * instância é ocultada. Identificação por classe CSS + heading text.
     */
    function deduplicateHomeSections() {
        if (!document.body.classList.contains('cms-index-index') &&
            !document.body.classList.contains('cms-home')) {
            return;
        }

        var sectionSelectors = [
            '.home-new-product',
            '.rokan-newproduct',
            '.ayo-home5-product-grid',
            '.home-bestseller',
            '.onsale_product'
        ];

        sectionSelectors.forEach(function (sel) {
            var els = document.querySelectorAll(sel);
            if (els.length <= 1) return;

            // Keep the first visible one, hide duplicates
            var kept = false;
            els.forEach(function (el) {
                if (!kept) {
                    kept = true;
                    return;
                }
                el.style.display = 'none';
                el.setAttribute('aria-hidden', 'true');
                el.classList.add('awa-hidden-duplicate');
                log('Dedup section: hidden duplicate ' + sel);
            });
        });
    }

    function fixVerticalMenuToggles(roots) {
        /* R13: aceita roots para processar novos toggles injetados via AJAX */
        var searchRoots = roots && roots.length
            ? roots
            : [getPageWrapper()];

        searchRoots.forEach(function (root) {
            if (!root || !root.querySelectorAll) return;
            root.querySelectorAll('.verticalmenu .open-children-toggle').forEach(function (div) {
                if (div.dataset.awaVtoggle) return; /* R13: guard — já processado */
                div.dataset.awaVtoggle = '1';
                div.setAttribute('role', 'button');
                div.setAttribute('tabindex', '0');
                div.setAttribute('aria-expanded', 'false');
                div.setAttribute('aria-label', 'Expandir subcategorias');
                div.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        div.click();
                    }
                });
                div.addEventListener('click', function () {
                    var expanded = div.getAttribute('aria-expanded') === 'true';
                    div.setAttribute('aria-expanded', expanded ? 'false' : 'true');
                });
            });
        });

        var expandLink = document.querySelector('.expand-category-link a');
        if (expandLink) {
            expandLink.setAttribute('role', 'button');
            expandLink.setAttribute('aria-expanded', 'false');
            expandLink.setAttribute('href', '#');
            /* cursor gerido por CSS */
            expandLink.addEventListener('click', function (e) {
                e.preventDefault();
                var expanded = expandLink.getAttribute('aria-expanded') === 'true';
                expandLink.setAttribute('aria-expanded', expanded ? 'false' : 'true');
            });
            expandLink.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    expandLink.click();
                }
            });
        }
    }

    /* R12-10: escopado a roots */
    function fixSocialShareAlts(roots) {
        var searchRoots = roots && roots.length
            ? roots
            : [getPageWrapper()];

        var socialMap = [
            ['a[href*="facebook.com/sharer"] img[alt=""]', 'Compartilhar no Facebook'],
            ['a[href*="twitter.com"] img[alt=""], a[href*="x.com"] img[alt=""]', 'Compartilhar no Twitter'],
            ['a[href*="pinterest.com"] img[alt=""]', 'Compartilhar no Pinterest'],
            ['a[href*="whatsapp"] img[alt=""]', 'Compartilhar no WhatsApp'],
            ['a[href*="linkedin.com"] img[alt=""]', 'Compartilhar no LinkedIn'],
            ['a[href*="instagram.com"] img[alt=""]', 'Ver no Instagram'],
            ['a[href*="t.me"] img[alt=""], a[href*="telegram"] img[alt=""]', 'Compartilhar no Telegram']
        ];

        searchRoots.forEach(function (root) {
            if (!root || !root.querySelectorAll) return;
            socialMap.forEach(function (pair) {
                root.querySelectorAll(pair[0]).forEach(function (img) {
                    img.alt = pair[1];
                });
            });
        });
    }

    /* R12-09: escopado a roots */
    function hideEmptyImages(roots) {
        var searchRoots = roots && roots.length
            ? roots
            : [getPageWrapper()];

        searchRoots.forEach(function (root) {
            if (!root || !root.querySelectorAll) return;
            root.querySelectorAll('img[src=""], img:not([src])').forEach(function (img) {
                var link = img.closest('a');
                if (link && (link.getAttribute('href') === '#' && !link.textContent.trim())) {
                    link.classList.add('awa-hidden-leak');
                } else {
                    img.classList.add('awa-hidden-leak');
                }
            });
        });
    }

    function fixNavToggleLabel() {
        var toggle = document.querySelector('.nav-toggle, .action.nav-toggle');
        if (toggle) {
            toggle.setAttribute('aria-label', 'Abrir menu de navegação');
            var span = toggle.querySelector('span');
            if (span && (span.textContent.trim() === 'Alternar Nav' || span.textContent.trim() === 'Toggle Nav')) {
                span.textContent = 'Menu';
            }
        }
    }

    /* R15-02: armazena referência para desconectar em re-renders AJAX */
    var _minicartA11yObserver = null;

    function fixMinicartA11y() {
        var counter = document.querySelector('.minicart-wrapper .counter.qty');
        if (!counter) return;
        /* R15-02: desconectar observer anterior se existir (AJAX re-render) */
        if (_minicartA11yObserver) {
            _minicartA11yObserver.disconnect();
            _minicartA11yObserver = null;
        }
        if (!counter.getAttribute('aria-live')) {
            counter.setAttribute('aria-live', 'polite');
            counter.setAttribute('role', 'status');
        }
        /* R9-14: Contextual label for screen readers */
        function updateCounterLabel() {
            var q = (counter.textContent || '').trim();
            if (q) {
                counter.setAttribute('aria-label', q + ' ' + (q === '1' ? 'item' : 'itens') + ' no carrinho');
            }
        }
        updateCounterLabel();
        /* Update label when content changes */
        try {
            _minicartA11yObserver = new MutationObserver(updateCounterLabel);
            _minicartA11yObserver.observe(counter, { characterData: true, childList: true, subtree: true });
        } catch (e) {
            log('fixMinicartA11y MO error: ' + e.message);
        }
    }

    /* R11-15: escopado a roots */
    function fixReviewCount(roots) {
        var searchRoots = roots && roots.length
            ? roots
            : [getPageWrapper()];

        searchRoots.forEach(function (root) {
            if (!root || !root.querySelectorAll) return;
            root.querySelectorAll('.reviews-actions .action.add, .reviews-actions a').forEach(function (el) {
                var text = el.textContent.trim();
                if (/^0\s+Avalia/.test(text)) {
                    el.textContent = 'Seja o primeiro a avaliar';
                } else {
                    var match = text.match(/^(\d+)\s+Avaliação$/);
                    if (match) {
                        var n = parseInt(match[1], 10);
                        el.textContent = n === 1 ? '1 Avaliação' : n + ' Avaliações';
                    }
                }
            });
        });
    }

    function addSkipToMain() {
        if (document.querySelector('a.skip-to-main, .action.skip, .awa-skip-to-main, a[href="#maincontent"]')) return;

        var target = document.getElementById('maincontent');
        if (target && !target.hasAttribute('tabindex')) {
            target.setAttribute('tabindex', '-1');
        }

        var skip = document.createElement('a');
        skip.href = '#maincontent';
        skip.className = 'awa-skip-to-main';
        skip.textContent = 'Pular para conteúdo principal';
        /* Estilos geridos por CSS (.awa-skip-to-main / :focus em awa-core.css) */

        document.body.insertBefore(skip, document.body.firstChild);
    }

    function fixSliderAltText(roots) {
        /* R13: aceita roots do MutationObserver para evitar full-DOM scan */
        var searchRoots = roots && roots.length
            ? roots
            : [getPageWrapper()];

        searchRoots.forEach(function (root) {
            if (!root || !root.querySelectorAll) return;
            root.querySelectorAll('img[alt="Homepage 5 Slider"], a[title="Homepage 5 Slider"]').forEach(function (el) {
                if (el.tagName === 'IMG') {
                    el.setAttribute('alt', 'Banner Promoção AWA Motos');
                }
                if (el.getAttribute('title') === 'Homepage 5 Slider') {
                    el.setAttribute('title', 'Banner Promoção AWA Motos');
                }
            });

            root.querySelectorAll('.banner-slider, .wrapper_slider, .banner_item').forEach(function (container) {
                var walker = document.createTreeWalker(container, NodeFilter.SHOW_TEXT, null, false);
                var node;
                while ((node = walker.nextNode())) {
                    if (node.textContent.trim() === 'Homepage 5 Slider') {
                        node.textContent = '';
                    }
                }
            });
        });
    }

    // ===========================================
    // 10. AYO MODULE ALIGNMENT (inline styles)
    // ===========================================
    function fixAyoModuleAlignment() {
        var containerWidth = getContainerWidth();
        var moduleSelectors = [
            '.homebuilder-section',
            '.home-bestseller',
            '.home-new-product',
            '.onsale_product',
            '.rokan-onsale',
            '.rokan-onsaleproduct',
            '.featured-products',
            '.tab_product',
            '.list-tab-product',
            '.categorytab-container'
        ].join(', ');
        var moduleRoots = document.querySelectorAll(moduleSelectors);

        moduleRoots.forEach(function (root) {
            // Passo 1: Clamp inline max-width excessivos ao container
            root.querySelectorAll('[style*="max-width"]').forEach(function (el) {
                if (el.classList.contains('product-image-container')) return;
                if (el.classList.contains('product-image-wrapper')) return;

                var inlineMax = parseInt(el.style.maxWidth, 10);
                if (inlineMax && inlineMax > containerWidth && inlineMax < 9999) {
                    el.style.maxWidth = containerWidth + 'px';
                    el.style.marginLeft = 'auto';
                    el.style.marginRight = 'auto';
                }
            });

            // Passo 2/3 REMOVIDOS — OWL flex/float agora é gerido 100% por CSS
            // (awa-components.css: .owl-stage display:flex !important, .owl-item float:none !important)
        });
    }

    // ===========================================
    // 11. SANITIZE ESCAPED CSS TEXT IN PRODUCT CARDS
    // ===========================================
    /* R12-08: escopado a roots */
    function sanitizeEscapedProductImageCssText(roots) {
        var cssLeak = RE_CSS_LEAK;
        var cleaned = 0;

        var searchRoots = roots && roots.length
            ? roots
            : [getPageWrapper()];

        searchRoots.forEach(function (root) {
            if (!root || !root.querySelectorAll) return;

            root.querySelectorAll('.product-item a, .item-product a').forEach(function (a) {
                var txt = (a.textContent || '').trim();
                if (cssLeak.test(txt)) {
                    a.textContent = '';
                    a.style.display = 'none';
                    cleaned++;
                }
            });

            root.querySelectorAll('.product-item, .item-product').forEach(function (card) {
                var walker = document.createTreeWalker(card, NodeFilter.SHOW_TEXT, null, false);
                var node;
                while ((node = walker.nextNode())) {
                    var text = (node.textContent || '').trim();
                    if (!cssLeak.test(text)) continue;
                    var parent = node.parentElement;
                    if (parent) {
                        parent.textContent = '';
                        parent.style.display = 'none';
                    } else {
                        node.textContent = '';
                    }
                    cleaned++;
                }
            });
        });

        if (cleaned > 0) {
            log('Sanitized escaped CSS text: ' + cleaned);
        }
    }

    // ===========================================
    // 12. OWL TAB CAROUSEL SELF-HEAL
    // ===========================================
    function normalizeOwlItemClasses(roots) {
        /* R14: aceita roots para evitar full-DOM scan */
        var sel = '.tab_product .owl-carousel .owl-item.grid, ' +
            '.categorytab-container .owl-carousel .owl-item.grid, ' +
            '.list-tab-product .owl-carousel .owl-item.grid';
        var searchRoots = roots && roots.length
            ? roots
            : [getPageWrapper()];

        searchRoots.forEach(function (root) {
            if (!root || !root.querySelectorAll) return;
            root.querySelectorAll(sel).forEach(function (item) {
                item.classList.remove('grid');
                if (item.style.display === 'grid') {
                    item.style.display = '';
                }
                if (item.closest('.owl-wrapper') && item.style.float === 'none') {
                    item.style.float = 'left';
                }
            });
        });
    }

    function getOwlItemsForViewport() {
        if (window.innerWidth >= AWA_CONFIG.breakpoints.desktopXL) return AWA_CONFIG.owlItems.desktopXL;
        if (window.innerWidth >= AWA_CONFIG.breakpoints.desktop) return AWA_CONFIG.owlItems.desktop;
        if (window.innerWidth >= AWA_CONFIG.breakpoints.desktopSmall) return AWA_CONFIG.owlItems.desktopSmall;
        if (window.innerWidth >= AWA_CONFIG.breakpoints.tablet) return AWA_CONFIG.owlItems.tablet;
        return AWA_CONFIG.owlItems.mobile;
    }

    function healBrokenOwlV1Layout(el) {
        if (!el || !el.querySelector) return;

        var wrapper = el.querySelector('.owl-wrapper');
        if (!wrapper) return;

        var rawChildren = wrapper.children || [];
        var items = [];
        for (var i = 0; i < rawChildren.length; i++) {
            if (rawChildren[i].classList && rawChildren[i].classList.contains('owl-item')) {
                items.push(rawChildren[i]);
            }
        }
        if (items.length < 2) return;

        var first = items[0];
        var firstRect = first.getBoundingClientRect();
        var wrapperRect = wrapper.getBoundingClientRect();

        var firstTop = first.offsetTop;
        var stacked = false;
        for (var j = 1; j < Math.min(items.length, 4); j++) {
            if (items[j].offsetTop > firstTop + 1) {
                stacked = true;
                break;
            }
        }

        var floatBroken = false;
        try {
            floatBroken = getComputedStyle(first).float === 'none';
        } catch (e) {
            floatBroken = false;
        }

        var collapsedWrapper = !!(firstRect.width && wrapperRect.width && wrapperRect.width <= (firstRect.width * 1.1));
        if (!stacked && !floatBroken && !collapsedWrapper) return;

        var visibleItems = Math.max(1, getOwlItemsForViewport());
        var carouselWidth = Math.round(el.getBoundingClientRect().width || 0);
        if (!carouselWidth) return;

        var itemWidth = Math.max(1, Math.floor(carouselWidth / visibleItems));
        wrapper.style.display = 'block';
        wrapper.style.width = (itemWidth * items.length) + 'px';

        items.forEach(function (item) {
            item.style.width = itemWidth + 'px';
            item.style.float = 'left';
            item.style.clear = 'none';
            item.style.display = 'block';
        });
    }

    function refreshOwlCarousel(el) {
        if (!el) return;

        var refreshed = false;
        if (window.jQuery) {
            var $ = window.jQuery;
            var $el = $(el);
            var owlV1 = $el.data('owlCarousel');
            if (owlV1) {
                if (window.innerWidth >= AWA_CONFIG.breakpoints.desktopSmall) {
                    var isTabModule = el.closest('.tab_product, .categorytab-container, .list-tab-product');
                    if (isTabModule && owlV1.options && owlV1.options.items < 2) {
                        // XL: 5 itens (padrão AYO)
                        var itemsForScreen = (window.innerWidth >= AWA_CONFIG.breakpoints.desktopXL)
                            ? AWA_CONFIG.owlItems.desktopXL
                            : AWA_CONFIG.owlItems.desktop;
                        owlV1.options.items = itemsForScreen;
                        owlV1.options.itemsDesktop = [AWA_CONFIG.breakpoints.desktop, AWA_CONFIG.owlItems.desktop];
                        owlV1.options.itemsDesktopSmall = [AWA_CONFIG.breakpoints.desktopSmall, AWA_CONFIG.owlItems.desktopSmall];
                        owlV1.options.itemsTablet = [AWA_CONFIG.breakpoints.tablet, AWA_CONFIG.owlItems.tablet];
                        owlV1.options.itemsMobile = [AWA_CONFIG.breakpoints.mobile, AWA_CONFIG.owlItems.mobile];
                    }
                }

                if (typeof owlV1.reinit === 'function') {
                    owlV1.reinit();
                    refreshed = true;
                } else if (typeof owlV1.updateVars === 'function') {
                    owlV1.updateVars();
                    refreshed = true;
                }
            }

            if ($el.data('owl.carousel')) {
                $el.trigger('refresh.owl.carousel');
                refreshed = true;
            }
        }

        if (!refreshed) {
            /* R9-04: Target only the carousel element, not global resize
               which triggers all Magento/AYO/3rd-party resize listeners */
            try {
                el.style.display = 'none';
                void el.offsetHeight; /* force reflow */
                el.style.display = '';
            } catch (e) { /* fallback silencioso */ }
        }

        // OWL v1 às vezes mantém wrapper/item com largura inválida em tabs/carouséis ocultos.
        healBrokenOwlV1Layout(el);
    }

    function refreshHomeTabCarousels(roots) {
        if (!document.querySelector('.tab_product, .categorytab-container, .list-tab-product')) return;
        normalizeOwlItemClasses(roots);

        document.querySelectorAll(
            '.tab_product .owl-carousel, ' +
            '.categorytab-container .owl-carousel, ' +
            '.list-tab-product .owl-carousel'
        ).forEach(function (carousel) {
            refreshOwlCarousel(carousel);
        });
    }

    function bindTabCarouselRefresh() {
        if (!document.querySelector('.tab_product, .categorytab-container, .list-tab-product')) return;
        if (document.body.getAttribute('data-awa-tab-carousel-bind') === '1') return;
        document.body.setAttribute('data-awa-tab-carousel-bind', '1');

        document.addEventListener('click', function (e) {
            var trigger = e.target.closest(
                '.list-tab-product .tabs li, ' +
                '.categorytab-container .tabs li, ' +
                '.tab_product .tabs li, ' +
                '.list-tab-product .tab-title, ' +
                '.categorytab-container .tab-title'
            );
            if (!trigger) return;

            setTimeout(function () {
                refreshHomeTabCarousels();
            }, AWA_CONFIG.timings.tabRefreshDelay);
        });
    }

    // ===========================================
    // 13. FALLBACK :has() PARA WebViews ANTIGOS
    // CSS usa :has() para ocultar box_language vazio e expandir menu_primary.
    // Se o browser não suporta :has(), aplica a lógica via JS.
    // ===========================================
    /* R15-11: Cache do resultado de CSS.supports para evitar reavaliação a cada resize */
    var _hasSelectorSupported = null;

    function applyHasFallback() {
        // Detecta suporte a :has() (cacheado)
        if (_hasSelectorSupported === null) {
            try {
                _hasSelectorSupported = CSS && CSS.supports && CSS.supports('selector(:has(*))');
            } catch (e) {
                _hasSelectorSupported = false;
            }
        }
        if (_hasSelectorSupported) return;

        // Fallback 1: box_language + menu_primary (awa-layout.css:L608-L622)
        var topHeaders = document.querySelectorAll('.header-nav .box_language .top-header');
        topHeaders.forEach(function (th) {
            if (th.textContent.trim() === '' && th.children.length === 0) {
                var boxLang = th.closest('.box_language');
                if (boxLang) {
                    boxLang.style.display = 'none';
                    // Encontra menu_primary irmão
                    var menuPrimary = boxLang.parentElement
                        ? boxLang.parentElement.querySelector('.menu_primary')
                        : null;
                    if (menuPrimary && window.innerWidth >= 992) {
                        menuPrimary.style.width = '83.33333%';
                        menuPrimary.style.maxWidth = '83.33333%';
                    }
                }
            }
        });

        // Fallback 2: placeholder hover (awa-fixes.css:L93)
        document.querySelectorAll('.product-item .second-thumb img[src*="placeholder/placeholder"]').forEach(function (img) {
            var secondThumb = img.closest('.second-thumb');
            if (secondThumb) {
                var firstThumb = secondThumb.previousElementSibling;
                if (firstThumb && firstThumb.classList.contains('first-thumb')) {
                    firstThumb.style.opacity = '1';
                }
            }
        });

        log(':has() fallback applied');
    }

    // ===========================================
    // FOOTER ACCORDION (mobile ≤767px)
    // AYO hides .velaContent by default and toggles via JS.
    // We hook into .velaFooterTitle clicks to add .active.
    // ===========================================
    function initFooterAccordion() {
        if (window.innerWidth > 767) return;

        var titles = document.querySelectorAll('.page-footer .velaFooterTitle');
        titles.forEach(function (title, idx) {
            if (title.dataset.awaAccordion) return;
            title.dataset.awaAccordion = '1';

            /* aria-controls: vincula título ao painel */
            var content = title.nextElementSibling;
            if (content) {
                var contentId = content.id || ('awa-footer-panel-' + idx);
                content.id = contentId;
                content.setAttribute('role', 'region');
                title.setAttribute('aria-controls', contentId);
                /* R9-10: Bi-directional aria link panel↔title */
                var titleId = title.id || ('awa-footer-title-' + idx);
                title.id = titleId;
                content.setAttribute('aria-labelledby', titleId);
            }

            /* cursor gerido por CSS (.velaFooterTitle[role="button"]) */
            title.setAttribute('role', 'button');
            title.setAttribute('aria-expanded', 'false');

            function toggleAccordion() {
                var panel = this.nextElementSibling;
                if (!panel) return;
                var isOpen = panel.classList.contains('active');
                if (isOpen) {
                    panel.classList.remove('active');
                    this.setAttribute('aria-expanded', 'false');
                } else {
                    panel.classList.add('active');
                    this.setAttribute('aria-expanded', 'true');
                }
            }

            title.setAttribute('tabindex', '0');
            title.addEventListener('click', toggleAccordion);
            title.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    toggleAccordion.call(this);
                }
            });
        });

        log('Footer accordion initialized (with keyboard support)');
    }

    // ===========================================
    // CATEGORY FILTER TOGGLE (mobile ≤991px)
    // Cria botão "Filtrar" que mostra/esconde sidebar
    // ===========================================
    function initCategoryFilterToggle() {
        var existing = document.querySelector('.awa-filter-toggle');
        if (window.innerWidth > 991) {
            /* Desktop: remove toggle button and reset sidebar visibility */
            if (existing) {
                existing.remove();
                document.body.classList.remove('awa-filter-visible');
            }
            return;
        }
        if (existing) return;

        var sidebar = document.querySelector('.sidebar-main');
        if (!sidebar) return;

        var toolbar = document.querySelector('.toolbar-products');
        if (!toolbar) return;

        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'awa-filter-toggle';
        btn.textContent = 'Filtrar';
        btn.setAttribute('aria-expanded', 'false');
        btn.setAttribute('aria-controls', 'awa-sidebar-filter');
        sidebar.id = 'awa-sidebar-filter';

        btn.addEventListener('click', function () {
            var isOpen = document.body.classList.toggle('awa-filter-visible');
            btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            btn.textContent = isOpen ? 'Fechar Filtros' : 'Filtrar';
        });

        toolbar.parentNode.insertBefore(btn, toolbar);
        log('Category filter toggle created');
    }

    // ===========================================
    // OWL CAROUSEL A11Y LABELS
    // Adiciona aria-label para OWL nav buttons
    // ===========================================
    function fixOwlNavA11y(roots) {
        /* R14: aceita roots do MutationObserver para evitar full-DOM scan */
        var searchRoots = roots && roots.length
            ? roots
            : [getPageWrapper()];

        searchRoots.forEach(function (root) {
            if (!root || !root.querySelectorAll) return;

            root.querySelectorAll('.owl-carousel').forEach(function (carousel, i) {
                if (carousel.getAttribute('role')) return;
                carousel.setAttribute('role', 'region');
                carousel.setAttribute('aria-roledescription', 'carrossel');
                var section = carousel.closest('.homebuilder-section, .home-bestseller, .home-new-product, .home-category, .onsale_product, .hot-deal-section, .featured-products, .testimonials-home, .the_blog, .tab_product, .list-tab-product, .banner-slider, .categorytab-container');
                var titleEl = section ? section.querySelector('.block-title, .section-title, .title-category, .box-title') : null;
                var label = titleEl ? titleEl.textContent.trim() : ('Carrossel ' + (i + 1));
                carousel.setAttribute('aria-label', label);
            });

            root.querySelectorAll('.owl-carousel .owl-nav button.owl-prev').forEach(function (btn) {
                if (!btn.getAttribute('aria-label')) btn.setAttribute('aria-label', 'Anterior');
            });
            root.querySelectorAll('.owl-carousel .owl-nav button.owl-next').forEach(function (btn) {
                if (!btn.getAttribute('aria-label')) btn.setAttribute('aria-label', 'Próximo');
            });
            root.querySelectorAll('.owl-carousel .owl-dots button.owl-dot').forEach(function (dot, i) {
                if (!dot.getAttribute('aria-label')) dot.setAttribute('aria-label', 'Slide ' + (i + 1));
                dot.setAttribute('role', 'tab');
                dot.setAttribute('aria-selected', dot.classList.contains('active') ? 'true' : 'false');
            });
            root.querySelectorAll('.owl-carousel .owl-dots').forEach(function (dots) {
                if (dots.getAttribute('role')) return;
                dots.setAttribute('role', 'tablist');
                dots.setAttribute('aria-label', 'Navegação de slides');
            });
        });

        /* jQuery event binding permanece global — uma vez por carousel */
        if (window.jQuery) {
            window.jQuery('.owl-carousel').each(function () {
                var $carousel = window.jQuery(this);
                if ($carousel.data('awa-dot-a11y')) return;
                $carousel.data('awa-dot-a11y', true);
                $carousel.on('changed.owl.carousel translated.owl.carousel', function () {
                    $carousel.find('.owl-dot').each(function () {
                        this.setAttribute('aria-selected', this.classList.contains('active') ? 'true' : 'false');
                    });
                });
            });
        }

        log('OWL carousel a11y labels applied');
    }

    // ===========================================
    // R13: SEARCH AUTOCOMPLETE A11Y
    // Adiciona roles ARIA para screen readers
    // ===========================================
    function fixSearchAutocompleteA11y() {
        var autocomplete = document.querySelector('.search-autocomplete');
        var input = document.querySelector('#search');
        if (!autocomplete || !input) return;
        if (autocomplete.getAttribute('role')) return;
        autocomplete.setAttribute('role', 'listbox');
        autocomplete.id = autocomplete.id || 'awa-search-autocomplete';
        input.setAttribute('aria-controls', autocomplete.id);
        input.setAttribute('aria-autocomplete', 'list');

        /* R15-03: Marcar itens individuais com role="option" para screen readers */
        function tagAutocompleteItems() {
            autocomplete.querySelectorAll('li:not([role])').forEach(function (li, i) {
                li.setAttribute('role', 'option');
                li.id = li.id || ('awa-ac-option-' + i);
            });
        }
        tagAutocompleteItems();
        /* MutationObserver para itens inseridos dinamicamente pelo Magento */
        try {
            var acObserver = new MutationObserver(function () {
                tagAutocompleteItems();
            });
            acObserver.observe(autocomplete, { childList: true, subtree: true });
        } catch (e) {
            log('Search autocomplete MO error: ' + e.message);
        }

        log('Search autocomplete a11y applied');
    }

    // ===========================================
    // DEBOUNCED RESIZE LISTENER
    // Re-runs breakpoint-aware functions on viewport change
    // ===========================================
    var resizeTimer = null;
    function initResizeListener() {
        window.addEventListener('resize', function () {
            if (AWA_CONFIG._suppressResizeRefresh) return;
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function () {
                fixAyoModuleAlignment();
                refreshHomeTabCarousels();
                initFooterAccordion();
                initCategoryFilterToggle();
                /* R12-17: fixOwlNavA11y removido — carousels não mudam em resize */
                applyHasFallback(); /* R11-16: recalcula em resize */
                log('Resize handler executed');
            }, AWA_CONFIG.timings.resizeDebounce);
        });
        log('Resize listener initialized');
    }

    // ===========================================
    // LAZY IMAGE FADE-IN (complementa CSS)
    // Marca imagens lazy como .awa-loaded quando carregam
    // para evitar flash de opacity:0 em imgs cacheadas
    // ===========================================
    function initLazyImageFade(roots) {
        /* R10-14: Accept roots from MutationObserver to avoid full-document scan */
        var searchRoots = roots && roots.length
            ? roots
            : [getPageWrapper()];

        searchRoots.forEach(function (root) {
            if (!root || !root.querySelectorAll) return;
            root.querySelectorAll('img[loading="lazy"]:not(.awa-loaded):not(.awa-load-error)').forEach(function (img) {
                if (img.complete) {
                    /* R9-11: Only mark loaded if image actually decoded */
                    if (img.naturalWidth > 0) {
                        img.classList.add('awa-loaded');
                    } else {
                        img.classList.add('awa-load-error');
                    }
                } else {
                    img.addEventListener('load', function () {
                        this.classList.add('awa-loaded');
                    }, { once: true });
                    img.addEventListener('error', function () {
                        this.classList.add('awa-load-error');
                        this.classList.add('awa-loaded'); /* still show via fallback opacity */
                    }, { once: true });
                }
            });
        });
    }
    // ===========================================
    // R16-09: FORM VALIDATION A11Y
    // Adiciona role="alert" para anúncio em screen readers
    // ===========================================
    function fixFormValidationA11y(roots) {
        var searchRoots = roots && roots.length
            ? roots
            : [getPageWrapper()];

        searchRoots.forEach(function (root) {
            if (!root || !root.querySelectorAll) return;
            root.querySelectorAll('div.mage-error:not([role])').forEach(function (el) {
                el.setAttribute('role', 'alert');
            });
        });
    }

    // ===========================================
    // R16-08: NEWSLETTER POPUP A11Y
    // Dialog role + Escape key + focus on open
    // ===========================================
    function initNewsletterPopupA11y() {
        var popup = document.querySelector('.newsletterpopup');
        if (!popup || popup.dataset.awaPopupA11y) return;
        popup.dataset.awaPopupA11y = '1';

        popup.setAttribute('role', 'dialog');
        popup.setAttribute('aria-modal', 'true');
        popup.setAttribute('aria-label', 'Newsletter');

        /* Focus primeiro campo quando popup visível */
        var popupObserver = new MutationObserver(function () {
            var isVisible = popup.offsetParent !== null &&
                getComputedStyle(popup).display !== 'none' &&
                getComputedStyle(popup).visibility !== 'hidden';
            if (isVisible) {
                var firstInput = popup.querySelector('input[type="email"], input, button, a[href]');
                if (firstInput) setTimeout(function () { firstInput.focus(); }, AWA_CONFIG.timings.popupFocusDelay);
            }
        });
        popupObserver.observe(popup, { attributes: true, attributeFilter: ['style', 'class'] });

        /* Escape para fechar */
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && popup.offsetParent !== null) {
                var closeBtn = popup.querySelector('.close-popup, .btn-close, .action-close, [data-dismiss]');
                if (closeBtn) closeBtn.click();
            }
        });

        log('Newsletter popup a11y initialized');
    }

    // ===========================================
    // R16-07: Helper para execução segura (error boundary)
    // ===========================================
    function safeRun(fn, name) {
        try { fn(); } catch (e) { log('Error in ' + (name || fn.name || 'anonymous') + ': ' + e.message); }
    }

    // ===========================================
    // INICIALIZAÇÃO
    // ===========================================
    function init() {
        log('Initializing AWA Master Fix v2...');

        // Core fixes — R16-07: safeRun impede cascata de falhas
        safeRun(fixPrices, 'fixPrices');
        safeRun(translateTexts, 'translateTexts');
        safeRun(hideMagentoCode, 'hideMagentoCode');
        safeRun(addInputMasks, 'addInputMasks');

        // UI elements
        safeRun(initBackToTop, 'initBackToTop');
        safeRun(initWhatsAppButton, 'initWhatsAppButton');
        safeRun(initStickyHeaderSpacer, 'initStickyHeaderSpacer');
        safeRun(initMobileNavClose, 'initMobileNavClose');

        // Accessibility
        safeRun(deduplicateHorizontalNav, 'deduplicateHorizontalNav');
        safeRun(deduplicateHomeSections, 'deduplicateHomeSections'); /* AF-04 */
        safeRun(fixVerticalMenuToggles, 'fixVerticalMenuToggles');
        safeRun(fixSocialShareAlts, 'fixSocialShareAlts');
        safeRun(hideEmptyImages, 'hideEmptyImages');
        safeRun(fixNavToggleLabel, 'fixNavToggleLabel');
        safeRun(fixReviewCount, 'fixReviewCount');
        safeRun(addSkipToMain, 'addSkipToMain');
        safeRun(fixSliderAltText, 'fixSliderAltText');
        safeRun(fixMinicartA11y, 'fixMinicartA11y');
        safeRun(fixFormValidationA11y, 'fixFormValidationA11y'); /* R16-09 */

        // Layout alignment
        safeRun(fixAyoModuleAlignment, 'fixAyoModuleAlignment');
        safeRun(sanitizeEscapedProductImageCssText, 'sanitizeEscapedProductImageCssText');
        safeRun(bindTabCarouselRefresh, 'bindTabCarouselRefresh');
        safeRun(applyHasFallback, 'applyHasFallback');
        safeRun(fixOwlNavA11y, 'fixOwlNavA11y');
        safeRun(initLazyImageFade, 'initLazyImageFade');

        // R16-08: Defer non-critical UI inits to idle time
        var deferInit = typeof requestIdleCallback === 'function'
            ? requestIdleCallback
            : function (fn) { return setTimeout(fn, 200); };
        deferInit(function () {
            safeRun(initFooterAccordion, 'initFooterAccordion');
            safeRun(initCategoryFilterToggle, 'initCategoryFilterToggle');
            safeRun(fixSearchAutocompleteA11y, 'fixSearchAutocompleteA11y');
            safeRun(initNewsletterPopupA11y, 'initNewsletterPopupA11y');
            safeRun(initResizeListener, 'initResizeListener');
        });

        // OWL retry inteligente: suporta OWL v2 (.owl-stage) e v1 (.owl-wrapper)
        (function waitForOwlAndRefresh(retries) {
            if (retries <= 0) return;
            var hasOwlV2 = document.querySelector(
                '.tab_product .owl-carousel .owl-stage, ' +
                '.categorytab-container .owl-carousel .owl-stage, ' +
                '.list-tab-product .owl-carousel .owl-stage'
            );
            var hasOwlV1 = document.querySelector(
                '.tab_product .owl-carousel .owl-wrapper .owl-item, ' +
                '.categorytab-container .owl-carousel .owl-wrapper .owl-item, ' +
                '.list-tab-product .owl-carousel .owl-wrapper .owl-item'
            );

            if (hasOwlV2 || hasOwlV1) {
                refreshHomeTabCarousels();
                sanitizeEscapedProductImageCssText();
            } else {
                setTimeout(function () { waitForOwlAndRefresh(retries - 1); }, AWA_CONFIG.timings.owlRetryInterval);
            }
        })(5);

        // Extra pass after full load to recalculate widths in initially hidden tabs.
        window.addEventListener('load', function () {
            safeRun(refreshHomeTabCarousels, 'refreshHomeTabCarousels.onload');
        }, { once: true });

        log('AWA Master Fix v2 loaded successfully!');
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // MutationObserver para conteúdo AJAX
    // Escopo: .page-wrapper (não document.body) com debounce 350ms
    // Coleta addedNodes para processar apenas nós novos quando possível
    var debounceTimer = null;
    var pendingNodes = [];
    var scheduleCallback = typeof requestIdleCallback === 'function'
        ? requestIdleCallback
        : function (fn) { return setTimeout(fn, 350); };

    var observer = new MutationObserver(function (mutations) {
        var hasNewNodes = false;
        for (var i = 0; i < mutations.length; i++) {
            if (mutations[i].addedNodes.length > 0) {
                for (var j = 0; j < mutations[i].addedNodes.length; j++) {
                    var node = mutations[i].addedNodes[j];
                    if (node.nodeType === 1 && node.tagName !== 'SCRIPT' && node.tagName !== 'STYLE') {
                        pendingNodes.push(node);
                        hasNewNodes = true;
                    }
                }
            }
        }

        if (hasNewNodes) {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function () {
                var nodes = pendingNodes.slice();
                pendingNodes = [];

                scheduleCallback(function () {
                    /* R17-10: use safeRun() instead of repetitive try-catch */
                    safeRun(function () { translateTexts(nodes); }, 'translateTexts');
                    safeRun(function () { fixPrices(nodes); }, 'fixPrices');
                    safeRun(function () { addInputMasks(nodes); }, 'addInputMasks');

                    /* Detectar conteúdo relevante nos nós novos */
                    var hasProducts = false, hasImages = false, hasOwl = false, hasModules = false;
                    for (var k = 0; k < nodes.length; k++) {
                        var n = nodes[k];
                        if (!n.querySelector) continue;
                        if (!hasProducts && n.querySelector('.price, .product-item, .item-product, .reviews-actions')) hasProducts = true;
                        if (!hasImages && n.querySelector('img')) hasImages = true;
                        if (!hasOwl && n.querySelector('.owl-carousel')) hasOwl = true;
                        if (!hasModules && n.querySelector('.homebuilder-section, .tab_product, .categorytab-container, .list-tab-product')) hasModules = true;
                        if (hasProducts && hasImages && hasOwl && hasModules) break;
                    }

                    if (hasProducts) {
                        safeRun(function () { fixReviewCount(nodes); }, 'fixReviewCount');
                        safeRun(function () { sanitizeEscapedProductImageCssText(nodes); }, 'sanitizeEscapedProductImageCssText');
                    }
                    if (hasImages) {
                        safeRun(function () { hideEmptyImages(nodes); }, 'hideEmptyImages');
                        safeRun(function () { fixSocialShareAlts(nodes); }, 'fixSocialShareAlts');
                        safeRun(function () { initLazyImageFade(nodes); }, 'initLazyImageFade');
                        safeRun(function () { fixSliderAltText(nodes); }, 'fixSliderAltText');
                    }
                    if (hasOwl) {
                        safeRun(bindTabCarouselRefresh, 'bindTabCarouselRefresh');
                        safeRun(function () { refreshHomeTabCarousels(nodes); }, 'refreshHomeTabCarousels');
                        safeRun(function () { fixOwlNavA11y(nodes); }, 'fixOwlNavA11y');
                    }
                    safeRun(function () { fixVerticalMenuToggles(nodes); }, 'fixVerticalMenuToggles');
                    safeRun(function () { hideMagentoCode(nodes); }, 'hideMagentoCode');
                    if (hasModules) {
                        safeRun(fixAyoModuleAlignment, 'fixAyoModuleAlignment');
                    }
                    safeRun(initFooterAccordion, 'initFooterAccordion');
                    safeRun(initCategoryFilterToggle, 'initCategoryFilterToggle');
                    safeRun(function () { fixFormValidationA11y(nodes); }, 'fixFormValidationA11y');
                });
            }, AWA_CONFIG.timings.mutationDebounce);
        }
    });

    var observeTarget = getPageWrapper();
    observer.observe(observeTarget, {
        childList: true,
        subtree: true
    });

})();
