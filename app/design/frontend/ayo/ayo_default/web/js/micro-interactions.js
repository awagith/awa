/**
 * Micro-interactions e Animações Avançadas
 * Melhora a experiência visual com transições suaves
 */

// ========================================
// 1. SMOOTH SCROLL
// ========================================
document.addEventListener('DOMContentLoaded', function() {
    // Smooth scroll para links âncora
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (href !== '#' && href.length > 1) {
                const target = document.querySelector(href);
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        });
    });

    // ========================================
    // 2. PARALLAX SUAVE NOS BANNERS
    // ========================================
    const parallaxElements = document.querySelectorAll('.banner-parallax, .slide-banner');
    
    if (parallaxElements.length > 0) {
        let ticking = false;
        
        window.addEventListener('scroll', function() {
            if (!ticking) {
                window.requestAnimationFrame(function() {
                    const scrolled = window.pageYOffset;
                    
                    parallaxElements.forEach(function(element) {
                        const speed = element.dataset.speed || 0.5;
                        const yPos = -(scrolled * speed);
                        element.style.transform = 'translate3d(0, ' + yPos + 'px, 0)';
                    });
                    
                    ticking = false;
                });
                
                ticking = true;
            }
        });
    }

    // ========================================
    // 3. HOVER ELEVATION CARDS
    // ========================================
    const cards = document.querySelectorAll('.item-product, .banner-item, .cms-block');
    
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transition = 'transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.3s ease';
            this.style.transform = 'translateY(-8px)';
            this.style.boxShadow = '0 12px 24px rgba(0,0,0,0.15)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '';
        });
    });

    // ========================================
    // 4. BUTTON RIPPLE EFFECT
    // ========================================
    function createRipple(event) {
        const button = event.currentTarget;
        const circle = document.createElement('span');
        const diameter = Math.max(button.clientWidth, button.clientHeight);
        const radius = diameter / 2;

        circle.style.width = circle.style.height = `${diameter}px`;
        circle.style.left = `${event.clientX - button.offsetLeft - radius}px`;
        circle.style.top = `${event.clientY - button.offsetTop - radius}px`;
        circle.classList.add('ripple');

        const ripple = button.getElementsByClassName('ripple')[0];
        if (ripple) {
            ripple.remove();
        }

        button.appendChild(circle);
    }

    const buttons = document.querySelectorAll('.btn-primary, .action.primary, .tocart');
    buttons.forEach(button => {
        button.addEventListener('click', createRipple);
        if (!button.style.position || button.style.position === 'static') {
            button.style.position = 'relative';
            button.style.overflow = 'hidden';
        }
    });

    // ========================================
    // 5. FADE IN ON SCROLL (Intersection Observer)
    // ========================================
    const fadeElements = document.querySelectorAll('.fade-in-on-scroll, .block-content, .product-item');
    
    const fadeObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in-visible');
                fadeObserver.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    });

    fadeElements.forEach(element => {
        element.classList.add('fade-in-on-scroll');
        fadeObserver.observe(element);
    });

    // ========================================
    // 6. COUNT UP ANIMATION (NÚMEROS)
    // ========================================
    function animateValue(element, start, end, duration) {
        let startTimestamp = null;
        const step = (timestamp) => {
            if (!startTimestamp) startTimestamp = timestamp;
            const progress = Math.min((timestamp - startTimestamp) / duration, 1);
            const value = Math.floor(progress * (end - start) + start);
            element.textContent = value.toLocaleString('pt-BR');
            if (progress < 1) {
                window.requestAnimationFrame(step);
            }
        };
        window.requestAnimationFrame(step);
    }

    const countElements = document.querySelectorAll('.count-up');
    const countObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting && !entry.target.classList.contains('counted')) {
                entry.target.classList.add('counted');
                const endValue = parseInt(entry.target.dataset.count || entry.target.textContent);
                animateValue(entry.target, 0, endValue, 2000);
                countObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.5 });

    countElements.forEach(element => countObserver.observe(element));

    // ========================================
    // 7. BACK TO TOP BUTTON
    // ========================================
    const backToTop = document.createElement('button');
    backToTop.innerHTML = '<i class="fa fa-chevron-up"></i>';
    backToTop.className = 'back-to-top';
    backToTop.setAttribute('aria-label', 'Voltar ao topo');
    document.body.appendChild(backToTop);

    window.addEventListener('scroll', () => {
        if (window.pageYOffset > 300) {
            backToTop.classList.add('visible');
        } else {
            backToTop.classList.remove('visible');
        }
    });

    backToTop.addEventListener('click', () => {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });

    // ========================================
    // 8. LAZY LOAD ENHANCEMENT
    // ========================================
    if ('loading' in HTMLImageElement.prototype) {
        const images = document.querySelectorAll('img[loading="lazy"]');
        images.forEach(img => {
            img.addEventListener('load', function() {
                this.classList.add('loaded');
            });
        });
    } else {
        // Fallback para navegadores sem suporte
        const script = document.createElement('script');
        script.src = 'https://cdnjs.cloudflare.com/ajax/libs/lazysizes/5.3.2/lazysizes.min.js';
        document.body.appendChild(script);
    }

    // ========================================
    // 9. PRODUCT QUICK VIEW ANIMATION
    // ========================================
    const quickViewButtons = document.querySelectorAll('.quickview-product');
    quickViewButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const modal = document.querySelector('.modal-popup');
            if (modal) {
                modal.style.animation = 'modalFadeIn 0.3s ease-out';
            }
        });
    });

    // ========================================
    // 10. SHAKE ON ERROR (VALIDAÇÃO)
    // ========================================
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('invalid', function(e) {
            e.target.classList.add('shake-error');
            setTimeout(() => {
                e.target.classList.remove('shake-error');
            }, 500);
        }, true);
    });

    // ========================================
    // 11. TOOLTIP ENHANCEMENT
    // ========================================
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    tooltipElements.forEach(element => {
        const tooltip = document.createElement('div');
        tooltip.className = 'custom-tooltip';
        tooltip.textContent = element.dataset.tooltip;
        
        element.addEventListener('mouseenter', function() {
            document.body.appendChild(tooltip);
            const rect = this.getBoundingClientRect();
            tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
            tooltip.style.top = rect.top - tooltip.offsetHeight - 10 + 'px';
            tooltip.classList.add('visible');
        });
        
        element.addEventListener('mouseleave', function() {
            tooltip.classList.remove('visible');
            setTimeout(() => tooltip.remove(), 200);
        });
    });

    // ========================================
    // 12. PROGRESS BAR ON SCROLL
    // ========================================
    const progressBar = document.createElement('div');
    progressBar.className = 'scroll-progress-bar';
    document.body.appendChild(progressBar);

    window.addEventListener('scroll', () => {
        const windowHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
        const scrolled = (window.pageYOffset / windowHeight) * 100;
        progressBar.style.width = scrolled + '%';
    });

    console.log('✅ Micro-interactions carregadas com sucesso!');
});
