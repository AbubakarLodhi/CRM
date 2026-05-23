<?php

namespace App\Support;

class HtmlToPlainText
{
    public static function convert(string $html): string
    {
        $text = $html;

        $text = preg_replace('/<br\s*\/?>/i', "\n", $text) ?? $text;
        $text = preg_replace('/<\/(p|div|tr|h[1-6])>/i', "\n", $text) ?? $text;
        $text = preg_replace('/<\/(td|th)>/i', ' | ', $text) ?? $text;
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/[ \t]+/", ' ', $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }
}
