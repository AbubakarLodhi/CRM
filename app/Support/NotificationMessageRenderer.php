<?php

namespace App\Support;

use App\Models\NotificationTemplate;
use Illuminate\Support\Facades\Blade;

class NotificationMessageRenderer
{
    /**
     * @param  array<string, mixed>  $variables
     */
    public static function renderSubject(NotificationTemplate $template, array $variables, ?string $fallback = null): string
    {
        if (! filled($template->subject)) {
            return $fallback ?? 'Notification';
        }

        return trim(Blade::render($template->subject, $variables));
    }

    /**
     * @param  array<string, mixed>  $variables
     */
    public static function renderHtmlBody(NotificationTemplate $template, array $variables): string
    {
        return Blade::render($template->content, $variables);
    }

    /**
     * @param  array<string, mixed>  $variables
     */
    public static function renderWhatsAppBody(NotificationTemplate $template, array $variables): string
    {
        $html = self::renderHtmlBody($template, $variables);
        $text = HtmlToPlainText::convert($html);

        if (strlen($text) > 4000) {
            $text = substr($text, 0, 3990) . '…';
        }

        return $text;
    }
}
