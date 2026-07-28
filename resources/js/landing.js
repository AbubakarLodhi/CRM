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

    const getLandingTheme = () => {
        const attr = document.documentElement.getAttribute('data-landing-theme');

        return attr === 'light' ? 'light' : 'dark';
    };

    const syncLandingThemeUi = (theme) => {
        const nextTheme = theme === 'dark' ? 'light' : 'dark';
        const isLight = theme === 'light';

        document.querySelectorAll('[data-landing-theme-toggle]').forEach((button) => {
            button.setAttribute(
                'aria-label',
                nextTheme === 'light' ? 'Switch to light theme' : 'Switch to dark theme'
            );
            button.setAttribute('aria-pressed', isLight ? 'true' : 'false');
        });

        document.querySelectorAll('[data-landing-theme-label]').forEach((label) => {
            label.textContent = isLight ? 'Dark mode' : 'Light mode';
        });

        document.querySelectorAll('[data-landing-logo]').forEach((logo) => {
            const nextSrc = theme === 'light'
                ? logo.getAttribute('data-logo-light')
                : logo.getAttribute('data-logo-dark');

            if (nextSrc) {
                logo.setAttribute('src', nextSrc);
            }
        });

        const themeColor = document.querySelector('meta[name="theme-color"]');

        if (themeColor) {
            themeColor.setAttribute('content', theme === 'light' ? '#f4f7fb' : '#6366f1');
        }
    };

    const setLandingTheme = (theme, sourceButton = null) => {
        const nextTheme = theme === 'light' ? 'light' : 'dark';

        if (sourceButton) {
            sourceButton.classList.remove('is-animating');
            // Force reflow so the animation can restart on rapid clicks.
            void sourceButton.offsetWidth;
            sourceButton.classList.add('is-animating');

            window.setTimeout(() => {
                sourceButton.classList.remove('is-animating');
            }, 560);
        }

        document.documentElement.setAttribute('data-landing-theme', nextTheme);
        localStorage.setItem('landing-theme', nextTheme);
        syncLandingThemeUi(nextTheme);
    };

    document.querySelectorAll('[data-landing-theme-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            setLandingTheme(getLandingTheme() === 'dark' ? 'light' : 'dark', button);
        });
    });

    syncLandingThemeUi(getLandingTheme());

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
        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const feedback = form.querySelector('[data-form-feedback]');
            const submitButton = form.querySelector('[data-form-submit]');
            const accessKey = form.getAttribute('data-web3forms-key') || '';

            const showFeedback = (message, isError = false) => {
                if (! feedback) {
                    return;
                }

                feedback.hidden = false;
                feedback.textContent = message;
                feedback.classList.toggle('landing-form__feedback--error', isError);
            };

            if (accessKey === '') {
                showFeedback('Contact form is not configured yet. Please email us directly.', true);

                return;
            }

            const formData = new FormData(form);

            submitButton?.setAttribute('disabled', 'disabled');
            showFeedback('Sending...', false);

            try {
                const response = await fetch('https://api.web3forms.com/submit', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                    },
                    body: JSON.stringify({
                        access_key: accessKey,
                        subject: form.getAttribute('data-form-subject') || 'New contact form message',
                        name: formData.get('name'),
                        email: formData.get('email'),
                        message: formData.get('message'),
                    }),
                });

                const result = await response.json();

                if (! response.ok || ! result.success) {
                    throw new Error(result.message || 'Submission failed');
                }

                form.reset();
                showFeedback('Thanks! We will get back to you shortly.', false);
            } catch (error) {
                const message = error instanceof Error && error.message !== ''
                    ? error.message
                    : 'Something went wrong. Please try again or email us directly.';

                showFeedback(message, true);
            } finally {
                submitButton?.removeAttribute('disabled');
            }
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
