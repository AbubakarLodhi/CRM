@php
    use App\Support\TemplatePreviewRenderer;

    $payload = [];

    if (is_array($data)) {
        $payload = $data;
    } elseif (is_string($data)) {
        $decoded = json_decode($data, true);
        $payload = is_array($decoded) ? $decoded : [];
    }

    $rendered = TemplatePreviewRenderer::render(
        $template ?? '',
        $payload
    );
@endphp

<div
    x-data="{ mode: 'desktop' }"
    :key="'preview-' + @js($previewKey)"
    class="space-y-3"
>
    <!-- Toolbar -->
    <div class="flex gap-2">
        <button
            @click="mode = 'desktop'"
            class="px-3 py-1 rounded text-sm"
            :class="mode === 'desktop'
                ? 'bg-primary-600 text-white'
                : 'bg-gray-200'"
        >
            Desktop
        </button>

        <button
            @click="mode = 'mobile'"
            class="px-3 py-1 rounded text-sm"
            :class="mode === 'mobile'
                ? 'bg-primary-600 text-white'
                : 'bg-gray-200'"
        >
            Mobile
        </button>
    </div>

    <!-- Preview -->
    <div
        class="border rounded-xl bg-gray-100 overflow-hidden flex justify-center p-4"
        x-effect="
            if ($refs.frame) {
                const doc = $refs.frame.contentDocument;
                doc.open();
                doc.write(@js($rendered));
                doc.close();
            }
        "
    >
        <iframe
            x-ref="frame"
            class="bg-white border shadow"
            sandbox="allow-same-origin"
            :style="mode === 'mobile'
                ? 'width:375px;height:667px'
                : 'width:100%;height:700px'"
        ></iframe>
    </div>
</div>
