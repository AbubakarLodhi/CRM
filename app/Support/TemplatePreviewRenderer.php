<?php
namespace App\Support;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;

class TemplatePreviewRenderer
{
    public static function render(string $template, mixed $data = []): string
    {
        // 🔐 Normalize data FIRST
        if (! is_array($data)) {
            $data = [];
        }

        $safeData = array_merge([
            'order_id'            => '',
            'total_amount'        => 0,
            'merchant_logo_url'   => '',
            'recipient_name'      => '',
        ], $data);

        try {
            $compiled = Blade::compileString($template);

            return self::evaluate($compiled, $safeData);
        } catch (\Throwable $e) {
            return <<<HTML
                <div style="padding:16px;color:#b91c1c;background:#fee2e2;">
                    <strong>Template Error:</strong><br>
                    {$e->getMessage()}
                </div>
            HTML;
        }
    }

    protected static function evaluate(string $__php, array $__data): string
    {
        ob_start();
        extract($__data, EXTR_SKIP);

        try {
            eval('?>' . $__php);
        } finally {
            return ob_get_clean();
        }
    }
}
