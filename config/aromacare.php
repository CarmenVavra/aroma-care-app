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

];
