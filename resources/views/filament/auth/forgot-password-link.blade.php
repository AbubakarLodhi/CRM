@if (filament()->hasPasswordReset())
    <div class="fi-simple-layout-forgot-password-link mt-4 text-center">
        <x-filament::link :href="filament()->getRequestPasswordResetUrl()">
            {{ __('filament-panels::auth/pages/login.actions.request_password_reset.label') }}
        </x-filament::link>
    </div>
@endif
