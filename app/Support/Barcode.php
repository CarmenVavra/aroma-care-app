<?php

namespace App\Support;

/**
 * Normalises what a scanner delivers into a product code.
 *
 * - EAN/UPC codes stay as they are; GTIN-14 with a leading 0 becomes EAN-13.
 * - GS1 element strings from DataMatrix / GS1 DataBar ("(01)04012345678901…",
 *   "]d2010401…") and GS1 Digital Link QR codes ("https://…/01/0401…") are
 *   reduced to the GTIN printed under the barcode.
 * - Any other content (e.g. a QR code with a link or text) is kept as the
 *   product's code, so scanning the same bottle again finds it.
 */
final class Barcode
{
    public const MAX_LENGTH = 512;

    /**
     * Normalised code, or null if nothing usable was scanned.
     */
    public static function normalize(string $raw): ?string
    {
        // Strip symbology identifiers (e.g. "]C1", "]d2", "]Q3") and surrounding whitespace.
        $code = trim((string) preg_replace('/^\][A-Za-z]\d/', '', trim($raw)));

        if (self::isUrl($code) && ($gtin = self::gtinFromDigitalLink($code))) {
            return $gtin;
        }

        if ($gtin = self::gtinFromElementString($code)) {
            return $gtin;
        }

        if (ctype_digit($code) && strlen($code) === 14) {
            return self::shortenGtin($code);
        }

        // Free content: no control characters, single spaces only.
        $code = trim((string) preg_replace(['/[\x00-\x1F\x7F]+/u', '/\s{2,}/u'], [' ', ' '], $code));

        return $code !== '' && mb_strlen($code) <= self::MAX_LENGTH ? $code : null;
    }

    public static function isUrl(string $raw): bool
    {
        return (bool) preg_match('#^(https?://|www\.)#i', trim($raw));
    }

    /**
     * EAN-8, UPC-A, EAN-13 or GTIN-14: only these are looked up in product databases.
     */
    public static function isGtin(string $code): bool
    {
        return ctype_digit($code) && in_array(strlen($code), [8, 12, 13, 14], true);
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
        $path = (string) parse_url(preg_match('#^https?://#i', $url) ? $url : 'https://'.$url, PHP_URL_PATH);

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
     * GTIN-14 with leading zero → EAN-13 / UPC-A, the form printed under the barcode.
     */
    private static function shortenGtin(string $digits): string
    {
        return strlen($digits) === 14 && str_starts_with($digits, '0') ? substr($digits, 1) : $digits;
    }
}
