<style>
    /* LIGHT MODE */
    :root[data-filament-panel="merchant"] {
        --fi-color-primary:   {{ $settings->primary_color_light }};
        --fi-color-secondary: {{ $settings->secondary_color_light }};
        --fi-color-warning:   {{ $settings->warning_color_light }};
        --fi-color-danger:    {{ $settings->danger_color_light }};
        --fi-color-success:   {{ $settings->success_color_light }};
    }

    /* DARK MODE */
    html.dark :root[data-filament-panel="merchant"] {
        --fi-color-primary:   {{ $settings->primary_color_dark }};
        --fi-color-secondary: {{ $settings->secondary_color_dark }};
        --fi-color-warning:   {{ $settings->warning_color_dark }};
        --fi-color-danger:    {{ $settings->danger_color_dark }};
        --fi-color-success:   {{ $settings->success_color_dark }};
    }
</style>
