@php
    use App\Support\DemoAccount;

    $visitorSession = DemoAccount::currentVisitorSession();
    $remainingSeconds = $visitorSession?->remainingSeconds() ?? 0;
    $expiresAt = $visitorSession?->expires_at?->toIso8601String();
@endphp

@if (DemoAccount::isDemoMerchant() && $visitorSession && $remainingSeconds > 0)
    <div
        class="fi-demo-banner"
        role="status"
        data-demo-banner
        data-demo-expires-at="{{ $expiresAt }}"
        data-demo-remaining="{{ $remainingSeconds }}"
    >
        <strong>Demo mode</strong>
        <span>Pre-loaded sample data — explore sales, stock, purchases, and reports. Time left:</span>
        <span class="fi-demo-banner__timer" data-demo-timer aria-live="polite">--:--</span>
        <a href="{{ route('demo.exit') }}">Exit demo</a>
    </div>

    <script>
        (() => {
            const banner = document.querySelector('[data-demo-banner]');

            if (! banner) {
                return;
            }

            const timerNode = banner.querySelector('[data-demo-timer]');
            const expiresAt = Date.parse(banner.dataset.demoExpiresAt || '');
            let remaining = parseInt(banner.dataset.demoRemaining || '0', 10);

            const formatTime = (totalSeconds) => {
                const minutes = Math.floor(totalSeconds / 60);
                const seconds = totalSeconds % 60;

                return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
            };

            const tick = () => {
                if (! Number.isNaN(expiresAt)) {
                    remaining = Math.max(0, Math.floor((expiresAt - Date.now()) / 1000));
                } else {
                    remaining = Math.max(0, remaining - 1);
                }

                if (! timerNode) {
                    return;
                }

                timerNode.textContent = formatTime(remaining);

                if (remaining <= 0) {
                    window.location.href = @json(route('demo.exit'));
                }
            };

            tick();
            window.setInterval(tick, 1000);
        })();
    </script>
@endif
