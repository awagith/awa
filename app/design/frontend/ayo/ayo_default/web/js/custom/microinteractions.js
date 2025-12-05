/**
 * Microinterações AYO - Grupo Awamotos
 * Implementado em: 05/12/2025
 * 
 * Este arquivo adiciona animações e interações JavaScript
 * que complementam os estilos CSS para uma UX superior.
 */

define([
    'jquery',
    'domReady!'
], function ($) {
    'use strict';

    return function () {
        
        // ========== CONFIGURAÇÕES ==========
        const config = {
            animationDuration: 300,
            scrollThreshold: 100,
            debounceDelay: 150
        };

        // ========== UTILIDADES ==========
        
        /**
         * Debounce function para otimizar performance
         */
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        /**
         * Throttle function para limitar execuções
         */
        function throttle(func, limit) {
            let inThrottle;
            return function(...args) {
                if (!inThrottle) {
                    func.apply(this, args);
                    inThrottle = true;
                    setTimeout(() => inThrottle = false, limit);
                }
            };
        }

        // ========== SCROLL PROGRESS BAR ==========
        
        function initScrollProgress() {
            const progressBar = document.createElement('div');
            progressBar.className = 'scroll-progress';
            document.body.appendChild(progressBar);

            const updateProgress = throttle(function() {
                const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                const scrollHeight = document.documentElement.scrollHeight - window.innerHeight;
                const progress = (scrollTop / scrollHeight) * 100;
                progressBar.style.width = progress + '%';
            }, 16);

            window.addEventListener('scroll', updateProgress, { passive: true });
        }

        // ========== SCROLL ANIMATIONS (Intersection Observer) ==========
        
        function initScrollAnimations() {
            const animatedElements = document.querySelectorAll('[data-animate]');
            
            if (!animatedElements.length) return;

            const observerOptions = {
                root: null,
                rootMargin: '0px 0px -50px 0px',
                threshold: 0.1
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animated');
                        observer.unobserve(entry.target);
                    }
                });
            }, observerOptions);

            animatedElements.forEach(el => observer.observe(el));
        }

        // ========== BACK TO TOP BUTTON ==========
        
        function initBackToTop() {
            // Criar botão se não existir
            if (!document.getElementById('back-to-top')) {
                const btn = document.createElement('button');
                btn.id = 'back-to-top';
                btn.className = 'back-to-top';
                btn.setAttribute('aria-label', 'Voltar ao topo');
                btn.innerHTML = '<span class="icon">↑</span>';
                document.body.appendChild(btn);

                // Estilo inline para garantir funcionamento
                btn.style.cssText = `
                    position: fixed;
                    bottom: 80px;
                    right: 20px;
                    width: 44px;
                    height: 44px;
                    background: #b73337;
                    color: white;
                    border: none;
                    border-radius: 50%;
                    cursor: pointer;
                    opacity: 0;
                    visibility: hidden;
                    transition: opacity 0.3s, visibility 0.3s, transform 0.3s;
                    z-index: 9999;
                    font-size: 18px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
                `;

                btn.addEventListener('click', function() {
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                });

                btn.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-3px)';
                    this.style.boxShadow = '0 6px 16px rgba(0,0,0,0.3)';
                });

                btn.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = '0 4px 12px rgba(0,0,0,0.2)';
                });
            }

            const backToTopBtn = document.getElementById('back-to-top');
            
            const toggleBackToTop = throttle(function() {
                if (window.pageYOffset > config.scrollThreshold) {
                    backToTopBtn.style.opacity = '1';
                    backToTopBtn.style.visibility = 'visible';
                } else {
                    backToTopBtn.style.opacity = '0';
                    backToTopBtn.style.visibility = 'hidden';
                }
            }, 100);

            window.addEventListener('scroll', toggleBackToTop, { passive: true });
        }

        // ========== ADD TO CART ANIMATION ==========
        
        function initAddToCartAnimation() {
            $(document).on('ajax:addToCart', function(event, data) {
                const minicartCounter = $('.counter.qty');
                
                // Adiciona classe de animação
                minicartCounter.addClass('updated');
                
                // Remove classe após animação
                setTimeout(function() {
                    minicartCounter.removeClass('updated');
                }, 500);

                // Animação de produto voando para o carrinho (opcional)
                if (data && data.productInfo) {
                    const productImage = $('.product-image-photo:visible').first();
                    const minicart = $('.minicart-wrapper');

                    if (productImage.length && minicart.length) {
                        animateFlyToCart(productImage, minicart);
                    }
                }
            });
        }

        /**
         * Anima produto voando para o carrinho
         */
        function animateFlyToCart(productImage, minicart) {
            const clone = productImage.clone();
            const productOffset = productImage.offset();
            const minicartOffset = minicart.offset();

            clone.css({
                position: 'fixed',
                top: productOffset.top - $(window).scrollTop(),
                left: productOffset.left,
                width: productImage.width(),
                height: 'auto',
                zIndex: 99999,
                opacity: 1,
                transition: 'all 0.5s cubic-bezier(0.4, 0, 0.2, 1)',
                borderRadius: '8px',
                boxShadow: '0 4px 20px rgba(0,0,0,0.2)'
            }).appendTo('body');

            setTimeout(function() {
                clone.css({
                    top: minicartOffset.top - $(window).scrollTop(),
                    left: minicartOffset.left,
                    width: 40,
                    opacity: 0,
                    transform: 'rotate(20deg)'
                });
            }, 10);

            setTimeout(function() {
                clone.remove();
            }, 600);
        }

        // ========== WISHLIST HEART ANIMATION ==========
        
        function initWishlistAnimation() {
            $(document).on('click', '.action.towishlist', function() {
                const $this = $(this);
                $this.addClass('added');
                
                setTimeout(function() {
                    $this.removeClass('added');
                }, 500);
            });
        }

        // ========== SMOOTH SCROLL LINKS ==========
        
        function initSmoothScroll() {
            $('a[href^="#"]:not([href="#"])').on('click', function(e) {
                const targetId = $(this).attr('href');
                const $target = $(targetId);

                if ($target.length) {
                    e.preventDefault();
                    const headerHeight = $('.page-header').outerHeight() || 0;
                    
                    $('html, body').animate({
                        scrollTop: $target.offset().top - headerHeight - 20
                    }, 600, 'swing');
                }
            });
        }

        // ========== LOADING BAR PAGE TRANSITIONS ==========
        
        function initPageLoadingBar() {
            // Criar barra de loading
            const loadingBar = document.createElement('div');
            loadingBar.className = 'page-loading-bar';
            document.body.appendChild(loadingBar);

            // Interceptar cliques em links internos
            $(document).on('click', 'a:not([target="_blank"]):not([href^="#"]):not([href^="javascript"]):not(.no-loading)', function(e) {
                const href = $(this).attr('href');
                
                if (href && href.indexOf(window.location.host) !== -1 || href.startsWith('/')) {
                    loadingBar.classList.add('loading');
                }
            });

            // Quando página carregar
            window.addEventListener('load', function() {
                loadingBar.classList.remove('loading');
                loadingBar.classList.add('complete');
                
                setTimeout(function() {
                    loadingBar.classList.remove('complete');
                }, 500);
            });
        }

        // ========== FOOTER ACCORDION MOBILE ==========
        
        function initFooterAccordion() {
            if (window.innerWidth > 768) return;

            $('.page-footer .footer-column .footer-title').on('click', function() {
                const $this = $(this);
                const $content = $this.siblings('.footer-content');

                $this.toggleClass('active');
                $content.toggleClass('active');
            });
        }

        // ========== QUANTITY INPUT BUTTONS ==========
        
        function initQuantityButtons() {
            $(document).on('click', '.qty-increment, .qty-decrement', function() {
                const $btn = $(this);
                const $input = $btn.siblings('.input-qty, input.qty');
                let value = parseInt($input.val()) || 1;
                const min = parseInt($input.attr('min')) || 1;
                const max = parseInt($input.attr('max')) || 9999;

                if ($btn.hasClass('qty-increment') && value < max) {
                    value++;
                } else if ($btn.hasClass('qty-decrement') && value > min) {
                    value--;
                }

                $input.val(value).trigger('change');
                
                // Feedback visual
                $input.addClass('changed');
                setTimeout(function() {
                    $input.removeClass('changed');
                }, 300);
            });
        }

        // ========== IMAGE LAZY LOADING AVANÇADO ==========
        
        function initLazyLoading() {
            // Configurações
            const lazyConfig = {
                rootMargin: '100px 0px', // Carregar 100px antes de aparecer
                threshold: 0.01,
                placeholderColor: '#f5f5f5'
            };

            // Selecionar todas as imagens para lazy loading
            const lazyImages = document.querySelectorAll(
                'img[data-src], img.lazy, .product-image-photo, .product-item-photo img'
            );

            if (!lazyImages.length) return;

            // Criar Intersection Observer
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        loadImage(img);
                        observer.unobserve(img);
                    }
                });
            }, {
                rootMargin: lazyConfig.rootMargin,
                threshold: lazyConfig.threshold
            });

            // Função para carregar imagem
            function loadImage(img) {
                // Pegar src do data-src ou data-original
                const src = img.dataset.src || img.dataset.original;
                
                if (src) {
                    // Criar imagem temporária para preload
                    const tempImage = new Image();
                    
                    tempImage.onload = function() {
                        img.src = src;
                        img.classList.add('lazy-loaded');
                        img.classList.remove('lazy', 'loading');
                        
                        // Trigger evento customizado
                        img.dispatchEvent(new CustomEvent('lazyloaded', { bubbles: true }));
                    };
                    
                    tempImage.onerror = function() {
                        img.classList.add('lazy-error');
                        console.warn('Lazy load error:', src);
                    };
                    
                    // Adicionar classe de loading
                    img.classList.add('loading');
                    
                    // Iniciar carregamento
                    tempImage.src = src;
                } else if (!img.dataset.lazyProcessed) {
                    // Imagem sem data-src, marcar como processada
                    img.dataset.lazyProcessed = 'true';
                    img.classList.add('lazy-loaded');
                }
            }

            // Aplicar a todas as imagens
            lazyImages.forEach(img => {
                // Verificar se já tem src válido
                if (img.src && !img.src.includes('placeholder') && !img.dataset.src) {
                    img.classList.add('lazy-loaded');
                    return;
                }
                
                // Adicionar loading="lazy" nativo como fallback
                if ('loading' in HTMLImageElement.prototype) {
                    img.loading = 'lazy';
                }
                
                // Observar imagem
                imageObserver.observe(img);
            });

            // Background Images Lazy Loading
            initLazyBackgrounds();
            
            // Iframes Lazy Loading (para vídeos embedados)
            initLazyIframes();
        }

        // Lazy loading para background images
        function initLazyBackgrounds() {
            const lazyBackgrounds = document.querySelectorAll('[data-bg]');
            
            if (!lazyBackgrounds.length) return;

            const bgObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const el = entry.target;
                        const bg = el.dataset.bg;
                        
                        if (bg) {
                            el.style.backgroundImage = `url(${bg})`;
                            el.classList.add('bg-loaded');
                        }
                        
                        observer.unobserve(el);
                    }
                });
            }, { rootMargin: '100px 0px' });

            lazyBackgrounds.forEach(el => bgObserver.observe(el));
        }

        // Lazy loading para iframes (YouTube, Vimeo, etc.)
        function initLazyIframes() {
            const lazyIframes = document.querySelectorAll('iframe[data-src]');
            
            if (!lazyIframes.length) return;

            const iframeObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const iframe = entry.target;
                        const src = iframe.dataset.src;
                        
                        if (src) {
                            iframe.src = src;
                            iframe.classList.add('iframe-loaded');
                        }
                        
                        observer.unobserve(iframe);
                    }
                });
            }, { rootMargin: '200px 0px' });

            lazyIframes.forEach(iframe => iframeObserver.observe(iframe));
        }

        // ========== TOOLTIPS ==========
        
        function initTooltips() {
            $('[data-tooltip]').each(function() {
                const $el = $(this);
                
                $el.on('mouseenter focus', function() {
                    $el.addClass('tooltip-visible');
                }).on('mouseleave blur', function() {
                    $el.removeClass('tooltip-visible');
                });
            });
        }

        // ========== FORM VALIDATION ANIMATION ==========
        
        function initFormValidation() {
            $(document).on('invalid', 'input, textarea, select', function() {
                $(this).addClass('validation-failed');
            });

            $(document).on('input change', '.validation-failed', function() {
                if (this.checkValidity()) {
                    $(this).removeClass('validation-failed');
                }
            });
        }

        // ========== STICKY HEADER ==========
        
        function initStickyHeader() {
            const header = document.querySelector('.page-header');
            if (!header) return;

            let lastScrollTop = 0;
            const headerHeight = header.offsetHeight;

            const handleScroll = throttle(function() {
                const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

                if (scrollTop > headerHeight) {
                    header.classList.add('sticky');
                    
                    // Hide on scroll down, show on scroll up
                    if (scrollTop > lastScrollTop && scrollTop > headerHeight * 2) {
                        header.classList.add('header-hidden');
                    } else {
                        header.classList.remove('header-hidden');
                    }
                } else {
                    header.classList.remove('sticky', 'header-hidden');
                }

                lastScrollTop = scrollTop;
            }, 16);

            window.addEventListener('scroll', handleScroll, { passive: true });
        }

        // ========== PRELOADER ==========
        
        function hidePreloader() {
            const preloader = document.querySelector('.page-preloader, .loading-mask');
            if (preloader) {
                preloader.classList.add('loaded');
                setTimeout(function() {
                    preloader.style.display = 'none';
                }, 500);
            }
        }

        // ========== INICIALIZAÇÃO ==========
        
        function init() {
            // Inicializar todas as funcionalidades
            initScrollProgress();
            initScrollAnimations();
            initBackToTop();
            initAddToCartAnimation();
            initWishlistAnimation();
            initSmoothScroll();
            initPageLoadingBar();
            initFooterAccordion();
            initQuantityButtons();
            initLazyLoading();
            initTooltips();
            initFormValidation();
            initStickyHeader();
            hidePreloader();

            // Log de sucesso
            console.log('✅ AYO Microinteractions initialized');
        }

        // Executar quando DOM estiver pronto
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }

        // API pública
        return {
            init: init,
            debounce: debounce,
            throttle: throttle
        };
    };
});
