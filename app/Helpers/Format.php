<?php

declare(strict_types=1);

namespace App\Helpers;

class Format
{
    public static function currency(float $value, ?array $settings = null): string
    {
        $currencyCode = strtoupper((string)($settings['currency_code'] ?? 'JPY'));
        $currencySymbol = (string)($settings['currency_symbol'] ?? '¥');

        if (class_exists(\NumberFormatter::class)) {
            $locale = $currencyCode === 'JPY' ? 'ja_JP' : 'pt_BR';
            $formatter = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);
            $formatter->setTextAttribute(\NumberFormatter::CURRENCY_CODE, $currencyCode);
            $result = $formatter->formatCurrency($value, $currencyCode);
            if ($result !== false) {
                return $result;
            }
        }

        $decimals = $currencyCode === 'JPY' ? 0 : 2;
        $formatted = number_format($value, $decimals, ',', '.');
        return trim($currencySymbol) . ' ' . $formatted;
    }
}
