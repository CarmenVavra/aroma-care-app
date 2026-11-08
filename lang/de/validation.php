<?php

/*
 * Minimal German validation messages for the rules used in this app.
 * Field-specific texts live in the components' messages() methods.
 */
return [
    'required' => 'Bitte fülle das Feld „:attribute“ aus.',
    'email' => 'Bitte gib eine gültige E-Mail-Adresse ein.',
    'unique' => '„:attribute“ ist bereits vergeben.',
    'confirmed' => 'Die Bestätigung von „:attribute“ stimmt nicht überein.',
    'current_password' => 'Das aktuelle Passwort ist nicht korrekt.',
    'string' => '„:attribute“ muss ein Text sein.',
    'numeric' => '„:attribute“ muss eine Zahl sein.',
    'integer' => '„:attribute“ muss eine ganze Zahl sein.',
    'array' => '„:attribute“ ist ungültig.',
    'in' => 'Der gewählte Wert für „:attribute“ ist ungültig.',
    'enum' => 'Der gewählte Wert für „:attribute“ ist ungültig.',
    'regex' => 'Das Format von „:attribute“ ist ungültig.',
    'gt' => ['numeric' => '„:attribute“ muss größer als :value sein.'],
    'between' => ['numeric' => '„:attribute“ muss zwischen :min und :max liegen.'],
    'max' => [
        'string' => '„:attribute“ darf höchstens :max Zeichen haben.',
        'numeric' => '„:attribute“ darf höchstens :max sein.',
        'array' => '„:attribute“ darf höchstens :max Einträge haben.',
    ],
    'min' => [
        'string' => '„:attribute“ muss mindestens :min Zeichen haben.',
    ],
    'password' => [
        'letters' => '„:attribute“ muss mindestens einen Buchstaben enthalten.',
        'mixed' => '„:attribute“ muss Groß- und Kleinbuchstaben enthalten.',
        'numbers' => '„:attribute“ muss mindestens eine Zahl enthalten.',
        'symbols' => '„:attribute“ muss mindestens ein Sonderzeichen enthalten.',
        'uncompromised' => 'Dieses Passwort ist in einem Datenleck aufgetaucht. Bitte wähle ein anderes.',
    ],

    'attributes' => [
        'name' => 'Name',
        'email' => 'E-Mail',
        'password' => 'Passwort',
        'current_password' => 'Aktuelles Passwort',
        'password_confirmation' => 'Passwort-Bestätigung',
        'quantity' => 'Menge',
        'unit' => 'Einheit',
        'brand' => 'Marke',
        'barcode' => 'Barcode',
    ],
];
