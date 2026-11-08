<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Product form options
    |--------------------------------------------------------------------------
    |
    | Selectable values used by the product form. Kept in config so they can
    | be extended without touching the Livewire component.
    |
    */

    'units' => ['ml', 'g', 'Stk.'],

    'usage_types' => ['Verdampfen', 'Hautpflege', 'Badezusatz', 'Inhalieren', 'Massage'],

    'use_areas' => ['Gesicht', 'Massage', 'Haare', 'Körper'],

    'per_page' => 15,

    /*
    |--------------------------------------------------------------------------
    | Barcode lookup
    |--------------------------------------------------------------------------
    |
    | After scanning an unknown barcode, product data is looked up in public
    | databases (server-side, only the barcode number is sent). Sources are
    | asked in this order: open_beauty_facts, open_food_facts,
    | open_products_facts, upcitemdb.
    |
    */

    'lookup' => [
        'enabled' => (bool) env('PRODUCT_LOOKUP_ENABLED', true),
        'timeout' => (int) env('PRODUCT_LOOKUP_TIMEOUT', 4),
        'sources' => array_values(array_filter(array_map('trim', explode(',', (string) env(
            'PRODUCT_LOOKUP_SOURCES',
            'open_beauty_facts,open_food_facts,open_products_facts,upcitemdb'
        ))))),
    ],

];
