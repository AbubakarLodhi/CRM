document.addEventListener('DOMContentLoaded', () => {
    const menuToggle = document.querySelector('[data-menu-toggle]');
    const mobileNav = document.querySelector('[data-mobile-nav]');

    if (menuToggle && mobileNav) {
        menuToggle.addEventListener('click', () => {
            const isHidden = mobileNav.hasAttribute('hidden');
            if (isHidden) {
                mobileNav.removeAttribute('hidden');
            } else {
                mobileNav.setAttribute('hidden', '');
            }
        });
    }

    const initSwiper = () => {
        if (typeof Swiper === 'undefined') {
            return;
        }

        document.querySelectorAll('.landing-hero-slider').forEach((element) => {
            new Swiper(element, {
                loop: false,
                autoplay: {
                    delay: 4500,
                    disableOnInteraction: false,
                    pauseOnMouseEnter: true,
                },
                pagination: { el: element.querySelector('.swiper-pagination'), clickable: true },
            });
        });

        document.querySelectorAll('.landing-testimonial-slider').forEach((element) => {
            new Swiper(element, {
                loop: false,
                autoplay: { delay: 5000 },
                pagination: { el: element.querySelector('.swiper-pagination'), clickable: true },
                spaceBetween: 24,
            });
        });
    };

    if (typeof Swiper !== 'undefined') {
        initSwiper();
    } else {
        window.addEventListener('load', initSwiper, { once: true });
    }

    document.querySelectorAll('.landing-autoplay-video').forEach((video) => {
        video.play().catch(() => {});
    });

    document.querySelectorAll('[data-reveal-stagger]').forEach((container) => {
        container.querySelectorAll(':scope > *').forEach((child, index) => {
            if (! child.hasAttribute('data-reveal')) {
                child.setAttribute('data-reveal', '');
            }

            if (! child.hasAttribute('data-reveal-delay')) {
                child.setAttribute('data-reveal-delay', String(Math.min(index, 7)));
            }
        });
    });

    const revealObserver = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (! entry.isIntersecting) {
                return;
            }

            entry.target.classList.add('is-visible');
            revealObserver.unobserve(entry.target);
        });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

    document.querySelectorAll('[data-reveal]').forEach((element) => revealObserver.observe(element));

    document.querySelectorAll('.landing-hero [data-reveal]').forEach((element) => {
        requestAnimationFrame(() => element.classList.add('is-visible'));
    });

    const header = document.querySelector('.landing-header');

    if (header) {
        const onScroll = () => header.classList.toggle('is-scrolled', window.scrollY > 12);
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
    }

    const revealFeaturesInPanel = (panel) => {
        panel?.querySelectorAll('[data-reveal]').forEach((feature) => {
            feature.classList.add('is-visible');
        });
    };

    document.querySelectorAll('[data-feature-tabs]').forEach((root) => {
        const tabs = root.querySelectorAll('[data-tab]');
        const panels = root.querySelectorAll('[data-panel]');

        tabs.forEach((tab) => {
            tab.addEventListener('click', () => {
                const key = tab.getAttribute('data-tab');

                tabs.forEach((item) => {
                    item.classList.toggle('is-active', item === tab);
                    item.setAttribute('aria-selected', item === tab ? 'true' : 'false');
                });

                panels.forEach((panel) => {
                    const isActive = panel.getAttribute('data-panel') === key;
                    panel.classList.toggle('is-active', isActive);

                    if (isActive) {
                        revealFeaturesInPanel(panel);
                    }
                });
            });
        });

        revealFeaturesInPanel(root.querySelector('.landing-tab-panel.is-active'));
    });

    const animateCounter = (element) => {
        const target = parseFloat(element.getAttribute('data-counter') || '0');
        const suffix = element.getAttribute('data-suffix') || '';
        const duration = 1200;
        const start = performance.now();

        const tick = (now) => {
            const progress = Math.min((now - start) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            const current = target % 1 === 0
                ? Math.round(target * eased)
                : (target * eased).toFixed(1);

            element.textContent = `${current}${suffix}`;

            if (progress < 1) {
                requestAnimationFrame(tick);
            }
        };

        requestAnimationFrame(tick);
    };

    const counterObserver = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (! entry.isIntersecting) {
                return;
            }

            const counter = entry.target.querySelector('[data-counter]');

            if (counter && ! counter.dataset.animated) {
                counter.dataset.animated = '1';
                animateCounter(counter);
            }
        });
    }, { threshold: 0.2 });

    document.querySelectorAll('.landing-metric').forEach((metric) => counterObserver.observe(metric));

    document.querySelectorAll('[data-landing-form]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            event.preventDefault();

            const feedback = form.querySelector('[data-form-feedback]');

            if (feedback) {
                feedback.hidden = false;
                feedback.textContent = 'Thanks! We will get back to you shortly.';
            }

            form.reset();
        });
    });

    document.querySelectorAll('[data-landing-alert]').forEach((alert) => {
        const dismiss = alert.querySelector('[data-landing-alert-dismiss]');

        dismiss?.addEventListener('click', () => {
            alert.classList.add('is-hidden');

            const url = new URL(window.location.href);

            if (url.searchParams.has('notice')) {
                url.searchParams.delete('notice');
                window.history.replaceState({}, '', url);
            }
        });
    });
});
