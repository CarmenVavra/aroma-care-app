<?php

namespace App\Support;

/**
 * Normalises what a scanner delivers into a product code.
 *
 * Handles plain EAN/UPC codes, GS1 element strings from DataMatrix / GS1
 * DataBar ("(01)04012345678901…" or "]d2010401…"), GS1 Digital Link QR codes
 * ("https://id.example.com/01/04012345678901") and GTIN-14 with a leading 0.
 */
final class Barcode
{
    private const ALLOWED = '/^[A-Za-z0-9\-\.\/: ]{1,64}$/';

    /**
     * Normalised code, or null if the input is not a usable product code.
     */
    public static function normalize(string $raw): ?string
    {
        // Strip symbology identifiers (e.g. "]C1", "]d2", "]Q3") and surrounding whitespace.
        $code = trim((string) preg_replace('/^\][A-Za-z]\d/', '', trim($raw)));

        if ($code === '') {
            return null;
        }

        if (self::isUrl($code)) {
            return self::gtinFromDigitalLink($code);
        }

        if ($gtin = self::gtinFromElementString($code)) {
            return $gtin;
        }

        if (ctype_digit($code) && strlen($code) === 14) {
            return self::shortenGtin($code);
        }

        // Other codes (Code 39/128 article numbers …) are kept as they are.
        $code = (string) preg_replace('/[\x00-\x1F\x7F]/', '', $code);

        return preg_match(self::ALLOWED, $code) ? $code : null;
    }

    public static function isUrl(string $raw): bool
    {
        return (bool) preg_match('#^https?://#i', trim($raw));
    }

    /**
     * Equivalent spellings for lookups: UPC-A (12 digits) is EAN-13 with a leading 0.
     *
     * @return list<string>
     */
    public static function variants(string $code): array
    {
        if (! ctype_digit($code)) {
            return [$code];
        }

        return match (strlen($code)) {
            12 => [$code, '0'.$code],
            13 => str_starts_with($code, '0') ? [$code, substr($code, 1)] : [$code],
            default => [$code],
        };
    }

    private static function gtinFromDigitalLink(string $url): ?string
    {
        $path = (string) parse_url($url, PHP_URL_PATH);

        return preg_match('#/(?:01|gtin)/(\d{8,14})(?:/|$)#', $path, $m) ? self::shortenGtin($m[1]) : null;
    }

    private static function gtinFromElementString(string $code): ?string
    {
        if (preg_match('/^\(01\)(\d{14})/', $code, $m)) {
            return self::shortenGtin($m[1]);
        }

        // Unbracketed element string: AI 01 + 14 digits, followed by further AIs or a group separator.
        if (strlen($code) > 16 && preg_match('/^01(\d{14})(?:\x1D|\d|$)/', $code, $m)) {
            return self::shortenGtin($m[1]);
        }

        return null;
    }

    /**
     * GTIN-14 with leading zeros → EAN-13 / UPC-A, the form printed under the barcode.
     */
    private static function shortenGtin(string $digits): string
    {
        return strlen($digits) === 14 && str_starts_with($digits, '0') ? substr($digits, 1) : $digits;
    }
}
