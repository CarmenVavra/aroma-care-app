<?php

/*
|--------------------------------------------------------------------------
| Legal details (Impressum / Datenschutzerklärung)
|--------------------------------------------------------------------------
|
| All values come from the LEGAL_* variables in .env. The operator is a
| private person: no street address and no phone number are published.
|
| LEGAL_COUNTRY_CODE controls the legal references:
|   DE = Germany (§ 5 DDG, § 25 TDDDG, Landes-Datenschutzbehörde)
|   AT = Austria (§ 5 ECG, § 25 MedienG, § 165 TKG 2021, Datenschutzbehörde)
|
*/

return [

    'name' => env('LEGAL_NAME', 'Vorname Nachname'),

    'postal_code' => env('LEGAL_POSTAL_CODE', '00000'),

    'city' => env('LEGAL_CITY', 'Ort'),

    'country' => env('LEGAL_COUNTRY', 'Deutschland'),

    'country_code' => strtoupper((string) env('LEGAL_COUNTRY_CODE', 'DE')),

    'email' => env('LEGAL_EMAIL', 'kontakt@example.com'),

    // Optional: leave empty if not applicable (private person).
    'vat_id' => env('LEGAL_VAT_ID'),

    // Person responsible for content (defaults to the operator).
    'content_responsible' => env('LEGAL_CONTENT_RESPONSIBLE', env('LEGAL_NAME', 'Vorname Nachname')),

    'hoster' => [
        'name' => env('LEGAL_HOSTER_NAME', 'netcup GmbH'),
        'address' => env('LEGAL_HOSTER_ADDRESS', 'Daimlerstraße 25, 76185 Karlsruhe, Deutschland'),
    ],

    // Date shown as "Stand" in the privacy policy.
    'privacy_updated_at' => env('LEGAL_PRIVACY_UPDATED_AT', '11/2026'),

];
