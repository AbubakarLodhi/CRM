@extends('landing.layout')

@section('content')
    @php
        $noticeKey = request()->string('notice')->toString();
        $noticeMessages = config('demo.notices', []);
        $alertMessage = session('error') ?? session('demo_expired') ?? session('status');
        $alertType = session('error') ? 'error' : (session('demo_expired') ? 'expired' : 'status');

        if (! $alertMessage && $noticeKey !== '' && isset($noticeMessages[$noticeKey])) {
            $alertMessage = $noticeMessages[$noticeKey];
            $alertType = $noticeKey === 'demo_expired' ? 'expired' : 'status';
        }
    @endphp

    @if ($alertMessage)
        <div
            class="landing-alert landing-alert--{{ $alertType }}"
            role="alert"
            data-landing-alert
            data-notice="{{ $noticeKey }}"
        >
            <p>{{ $alertMessage }}</p>
            <button type="button" class="landing-alert__dismiss" data-landing-alert-dismiss aria-label="Dismiss message">
                &times;
            </button>
        </div>
    @endif

    <header class="landing-header" id="top">
        <div class="landing-container landing-header__inner">
            <a href="{{ route('landing') }}" class="landing-logo">
                <img
                    src="{{ asset('images/flowdesk-logo-dark.svg') }}"
                    alt="{{ config('branding.name') }}"
                    width="120"
                    height="40"
                    data-landing-logo
                    data-logo-dark="{{ asset('images/flowdesk-logo-dark.svg') }}"
                    data-logo-light="{{ asset('images/flowdesk-logo.svg') }}"
                >
            </a>
            <script>
                (() => {
                    const theme = document.documentElement.getAttribute('data-landing-theme') === 'light' ? 'light' : 'dark';
                    document.querySelectorAll('[data-landing-logo]').forEach((logo) => {
                        const src = theme === 'light'
                            ? logo.getAttribute('data-logo-light')
                            : logo.getAttribute('data-logo-dark');

                        if (src) {
                            logo.setAttribute('src', src);
                        }
                    });
                })();
            </script>
            <nav class="landing-nav" aria-label="Primary">
                @foreach ($content['nav'] as $item)
                    <a href="{{ $item['href'] }}">{{ $item['label'] }}</a>
                @endforeach
            </nav>
            <div class="landing-header__actions">
                <button
                    type="button"
                    class="landing-theme-toggle"
                    data-landing-theme-toggle
                    aria-label="Switch to light theme"
                    aria-pressed="false"
                >
                    <span class="landing-theme-toggle__track" aria-hidden="true">
                        <span class="landing-theme-toggle__icon landing-theme-toggle__icon--sun">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="4" />
                                <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41" />
                            </svg>
                        </span>
                        <span class="landing-theme-toggle__icon landing-theme-toggle__icon--moon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 14.5A8.5 8.5 0 1 1 9.5 3a7 7 0 0 0 11.5 11.5z" />
                            </svg>
                        </span>
                        <span class="landing-theme-toggle__knob"></span>
                    </span>
                    <span class="landing-theme-toggle__sr">Theme</span>
                </button>
                <a href="{{ route('demo.login') }}" class="btn btn--ghost">Try Demo</a>
                <a href="#pricing" class="btn btn--primary">Get Started</a>
            </div>
            <button class="landing-menu-toggle" type="button" aria-label="Open menu" data-menu-toggle>
                <span></span><span></span><span></span>
            </button>
        </div>
        <div class="landing-mobile-nav" data-mobile-nav hidden>
            @foreach ($content['nav'] as $item)
                <a href="{{ $item['href'] }}">{{ $item['label'] }}</a>
            @endforeach
            <button
                type="button"
                class="landing-theme-toggle landing-theme-toggle--mobile"
                data-landing-theme-toggle
                aria-label="Switch to light theme"
                aria-pressed="false"
            >
                <span class="landing-theme-toggle__track" aria-hidden="true">
                    <span class="landing-theme-toggle__icon landing-theme-toggle__icon--sun">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="4" />
                            <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41" />
                        </svg>
                    </span>
                    <span class="landing-theme-toggle__icon landing-theme-toggle__icon--moon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 14.5A8.5 8.5 0 1 1 9.5 3a7 7 0 0 0 11.5 11.5z" />
                        </svg>
                    </span>
                    <span class="landing-theme-toggle__knob"></span>
                </span>
                <span class="landing-theme-toggle__text" data-landing-theme-label>Light mode</span>
            </button>
            <a href="{{ route('demo.login') }}" class="btn btn--primary">Try Demo</a>
        </div>
    </header>

    <main>
        <section class="landing-hero" id="hero">
            <div class="landing-hero__bg" aria-hidden="true"></div>
            <div class="landing-container landing-hero__grid">
                <div class="landing-hero__copy" data-reveal="left">
                    <p class="landing-eyebrow">Laravel-powered CRM platform</p>
                    <h1>{{ $content['hero']['headline'] }}</h1>
                    <p class="landing-lead">{{ $content['hero']['tagline'] }}</p>
                    <div class="landing-hero__ctas">
                        <a href="#pricing" class="btn btn--primary btn--lg">Start Free Trial</a>
                        <a href="{{ route('demo.login') }}" class="btn btn--secondary btn--lg">Try Demo Account</a>
                    </div>
                    <div class="landing-metrics" data-reveal-stagger>
                        @foreach ($content['hero']['metrics'] as $metric)
                            <div class="landing-metric">
                                <strong data-counter="{{ $metric['value'] }}" data-suffix="{{ $metric['suffix'] }}">0</strong>
                                <span>{{ $metric['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="landing-hero__visual" data-reveal="right">
                    <figure class="landing-hero-video">
                        <div class="landing-hero-video__frame">
                            <video
                                class="landing-hero-video__player landing-autoplay-video"
                                src="{{ asset($content['hero']['video']['src']) }}"
                                poster="{{ asset($content['hero']['video']['poster']) }}"
                                autoplay
                                muted
                                loop
                                playsinline
                                preload="auto"
                            ></video>
                        </div>
                        <figcaption>{{ $content['hero']['video']['caption'] }}</figcaption>
                    </figure>
                </div>
            </div>
        </section>

        <section class="landing-section landing-why" id="why">
            <div class="landing-container">
                <div class="landing-section__head" data-reveal>
                    <h2>Why choose {{ config('branding.name') }}?</h2>
                    <p>Everything your team needs to sell, stock, report, and scale — without switching tools.</p>
                </div>
                <div class="landing-grid landing-grid--3" data-reveal-stagger>
                    @foreach ($content['why'] as $item)
                        <article class="landing-card landing-card--hover">
                            <div class="landing-card__icon landing-card__icon--{{ $item['icon'] }}" aria-hidden="true">
                                @switch($item['icon'])
                                    @case('layers')
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M12 2 2 7l10 5 10-5-10-5Z" />
                                            <path d="m2 12 10 5 10-5" />
                                            <path d="m2 17 10 5 10-5" />
                                        </svg>
                                        @break
                                    @case('zap')
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M13 2 3 14h8l-1 8 10-12h-8l1-8Z" />
                                        </svg>
                                        @break
                                    @case('shield')
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z" />
                                            <path d="m9 12 2 2 4-4" />
                                        </svg>
                                        @break
                                @endswitch
                            </div>
                            <h3>{{ $item['title'] }}</h3>
                            <p>{{ $item['description'] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="landing-section landing-features" id="features">
            <div class="landing-container">
                <div class="landing-section__head" data-reveal>
                    <h2>Features built for real operations</h2>
                    <p>Explore the modules that power your daily workflows.</p>
                </div>
                <div class="landing-tabs" data-feature-tabs>
                    <div class="landing-tabs__nav" role="tablist" data-reveal>
                        @foreach ($content['feature_categories'] as $key => $category)
                            <button type="button" role="tab" class="landing-tab {{ $loop->first ? 'is-active' : '' }}" data-tab="{{ $key }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                                {{ $category['label'] }}
                            </button>
                        @endforeach
                    </div>
                    @foreach ($content['feature_categories'] as $key => $category)
                        <div class="landing-tab-panel {{ $loop->first ? 'is-active' : '' }}" data-panel="{{ $key }}" role="tabpanel">
                            @foreach ($category['features'] as $feature)
                                <article class="landing-feature" data-reveal>
                                    <div class="landing-feature__media {{ str_ends_with($feature['image'], '.png') ? 'landing-screenshot-scroll' : '' }}">
                                        <img src="{{ asset($feature['image']) }}" alt="{{ $feature['name'] }} screenshot" loading="lazy" width="800" height="500">
                                    </div>
                                    <div class="landing-feature__body">
                                        <h3>{{ $feature['name'] }}</h3>
                                        <p class="landing-feature__summary">{{ $feature['summary'] }}</p>
                                        <ul>
                                            @foreach ($feature['bullets'] as $bullet)
                                                <li>{{ $bullet }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="landing-section landing-demo" id="demo">
            <div class="landing-container landing-demo__grid">
                <div data-reveal="left">
                    <p class="landing-eyebrow">Live demo</p>
                    <h2>{{ $content['demo']['headline'] }}</h2>
                    <p class="landing-lead">{{ $content['demo']['description'] }}</p>
                    <ul class="landing-demo-list">
                        @foreach ($content['demo']['bullets'] as $bullet)
                            <li>{{ $bullet }}</li>
                        @endforeach
                    </ul>
                    <a href="{{ route('demo.login') }}" class="btn btn--primary btn--lg">Launch Demo Now</a>
                </div>
                <div class="landing-demo__visual" data-reveal="right">
                    <div class="landing-demo__frame">
                        <video
                            class="landing-demo__video landing-autoplay-video"
                            src="{{ asset($content['demo']['video']['src']) }}"
                            poster="{{ asset($content['demo']['video']['poster']) }}"
                            autoplay
                            muted
                            loop
                            playsinline
                            preload="auto"
                        ></video>
                    </div>
                </div>
            </div>
        </section>

        <section class="landing-section landing-use-cases" id="use-cases">
            <div class="landing-container">
                <div class="landing-section__head" data-reveal>
                    <h2>{{ $content['use_cases']['headline'] }}</h2>
                    <p>{{ $content['use_cases']['description'] }}</p>
                </div>
                <div class="landing-grid landing-grid--3 landing-use-cases__grid">
                    @foreach ($content['use_cases']['items'] as $case)
                        <article class="landing-use-case" data-reveal data-reveal-delay="{{ $loop->index }}">
                            <div class="landing-use-case__glow" aria-hidden="true"></div>
                            <div class="landing-use-case__icon landing-use-case__icon--{{ $case['icon'] }}" aria-hidden="true">
                                @switch($case['icon'])
                                    @case('retail')
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 9.5 5 4h14l2 5.5"/><path d="M5 9.5h14v10H5z"/><path d="M9 14h6"/></svg>
                                        @break
                                    @case('wholesale')
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 7h11v10H3z"/><path d="M14 10h4l3 4v3h-7z"/><circle cx="7.5" cy="18" r="1.5"/><circle cx="17.5" cy="18" r="1.5"/></svg>
                                        @break
                                    @case('branches')
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 21V5a1 1 0 0 1 1-1h5v17"/><path d="M14 21V9a1 1 0 0 1 1-1h5v13"/><path d="M9 9h1"/><path d="M9 13h1"/><path d="M19 13h1"/></svg>
                                        @break
                                @endswitch
                            </div>
                            <span class="landing-pill">{{ $case['tag'] }}</span>
                            <h3>{{ $case['title'] }}</h3>
                            <p class="landing-use-case__summary">{{ $case['description'] }}</p>
                            <div class="landing-use-case__transform">
                                <div class="landing-use-case__state landing-use-case__state--before">
                                    <span>Before</span>
                                    <p>{{ $case['before'] }}</p>
                                </div>
                                <div class="landing-use-case__arrow" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
                                </div>
                                <div class="landing-use-case__state landing-use-case__state--after">
                                    <span>With Flowdesk</span>
                                    <p>{{ $case['after'] }}</p>
                                </div>
                            </div>
                            <ul class="landing-use-case__highlights">
                                @foreach ($case['highlights'] as $highlight)
                                    <li>{{ $highlight }}</li>
                                @endforeach
                            </ul>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="landing-section" id="pricing">
            <div class="landing-container">
                <div class="landing-section__head" data-reveal>
                    <h2>Simple, transparent pricing</h2>
                    <p>We're putting the finishing touches on our plans.</p>
                </div>
                <div class="landing-pricing-soon" data-reveal="scale">
                    <span class="landing-pill">Coming soon</span>
                    <h3>Pricing launches shortly</h3>
                    <p>Explore the full platform with our demo account while we finalize simple, transparent plans for every business size.</p>
                    <a href="{{ route('demo.login') }}" class="btn btn--primary btn--lg">Try Demo Account</a>
                </div>
            </div>
        </section>

        <section class="landing-section" id="testimonials">
            <div class="landing-container">
                <div class="landing-section__head" data-reveal>
                    <h2>Loved by operators</h2>
                    <p>Teams use {{ config('branding.name') }} to simplify daily work.</p>
                </div>
                <div class="swiper landing-testimonial-slider" data-reveal>
                    <div class="swiper-wrapper">
                        @foreach ($content['testimonials'] as $item)
                            <div class="swiper-slide">
                                <blockquote class="landing-testimonial">
                                    <div class="landing-stars" aria-label="{{ $item['stars'] }} stars">
                                        {{ str_repeat('★', $item['stars']) }}
                                    </div>
                                    <p>"{{ $item['quote'] }}"</p>
                                    <footer><strong>{{ $item['author'] }}</strong> — {{ $item['role'] }}</footer>
                                </blockquote>
                            </div>
                        @endforeach
                    </div>
                    <div class="swiper-pagination"></div>
                </div>
            </div>
        </section>

        <section class="landing-section" id="faq">
            <div class="landing-container landing-faq__grid">
                <div data-reveal="left">
                    <h2>Frequently asked questions</h2>
                    <p class="landing-lead">Quick answers about the product and demo.</p>
                </div>
                <div data-reveal="right" data-reveal-stagger>
                    @foreach ($content['faq'] as $category => $items)
                        <h3 class="landing-accordion__category">{{ $category }}</h3>
                        @foreach ($items as $item)
                            <details class="landing-accordion__item">
                                <summary>{{ $item['q'] }}</summary>
                                <p>{{ $item['a'] }}</p>
                            </details>
                        @endforeach
                    @endforeach
                </div>
            </div>
        </section>

        <section class="landing-section landing-integrations">
            <div class="landing-container">
                <div class="landing-section__head" data-reveal>
                    <h2>Integrations & capabilities</h2>
                </div>
                <div class="landing-integration-grid" data-reveal-stagger>
                    @foreach ($content['integrations'] as $integration)
                        <div class="landing-integration">{{ $integration }}</div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="landing-section landing-security">
            <div class="landing-container landing-security__grid" data-reveal-stagger>
                @foreach ($content['security'] as $item)
                    <article class="landing-card landing-card--hover">
                        <h3>{{ $item['title'] }}</h3>
                        <p>{{ $item['description'] }}</p>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="landing-section landing-cta">
            <div class="landing-container">
                <div class="landing-cta__inner" data-reveal="scale">
                    <h2>Ready to see it in action?</h2>
                    <p>Launch the demo in one click — explore every module with realistic sample data.</p>
                    <a href="{{ route('demo.login') }}" class="btn btn--primary btn--lg">Try Demo Account</a>
                </div>
            </div>
        </section>

        <section class="landing-section" id="contact">
            <div class="landing-container landing-contact">
                <div data-reveal="left">
                    <h2>Contact us</h2>
                    <p class="landing-lead">Questions about pricing, onboarding, or enterprise plans?</p>
                    <p><strong>Email:</strong> <a href="mailto:{{ $content['contact']['email'] }}">{{ $content['contact']['email'] }}</a></p>
                    <p><strong>Website:</strong> <a href="{{ $content['contact']['website'] }}" target="_blank" rel="noopener">{{ $content['contact']['website'] }}</a></p>
                </div>
                <form
                    class="landing-form"
                    data-landing-form
                    data-reveal="right"
                    data-web3forms-key="{{ $content['contact']['web3forms_access_key'] }}"
                    data-form-subject="{{ $content['contact']['form_subject'] }}"
                >
                    <div class="landing-form__grid">
                        <label>Name<input type="text" name="name" required autocomplete="name"></label>
                        <label>Email<input type="email" name="email" required autocomplete="email"></label>
                    </div>
                    <label>Message<textarea name="message" rows="4" required></textarea></label>
                    <button type="submit" class="btn btn--primary" data-form-submit>Send message</button>
                    <p class="landing-form__feedback" data-form-feedback hidden></p>
                </form>
            </div>
        </section>
    </main>

    <footer class="landing-footer">
        <div class="landing-container landing-footer__grid">
            <div data-reveal>
                <a href="{{ route('landing') }}" class="landing-logo">
                    <img src="{{ asset('images/flowdesk-logo-dark.svg') }}" alt="{{ config('branding.name') }}" width="120" height="40">
                </a>
                <p>{{ $content['meta']['description'] }}</p>
            </div>
            @foreach ($content['footer']['columns'] as $column)
                <div data-reveal data-reveal-delay="{{ $loop->index + 1 }}">
                    <h4>{{ $column['title'] }}</h4>
                    @foreach ($column['links'] as $link)
                        <a href="{{ isset($link['route']) ? route($link['route']) : $link['href'] }}">{{ $link['label'] }}</a>
                    @endforeach
                </div>
            @endforeach
        </div>
        <div class="landing-container landing-footer__bottom">
            &copy; {{ date('Y') }} {{ config('branding.name') }}. All rights reserved.
        </div>
    </footer>
@endsection
