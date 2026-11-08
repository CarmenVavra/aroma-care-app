<?php

/*
|--------------------------------------------------------------------------
| Startrezepte für den Rezept-Finder
|--------------------------------------------------------------------------
|
| Wird von RecipeSeeder (bei jedem Deployment) per Slug abgeglichen: Änderungen
| hier landen beim nächsten ./deploy.sh in der Datenbank.
|
| Die Rezepte sind allgemeine Anregungen mit bewusst niedrigen Dosierungen für
| Erwachsene (Gesicht ≤ 1 %, Körper ≤ 2 %; 1 Tropfen ≈ 0,05 ml). Sie sind
| NICHT fachlich geprüft und sollten vor dem Livegang von einer Fachperson
| (z. B. Aromatherapeut:in) kontrolliert werden.
|
| Zutat: [Name, Kategorie, Menge, Einheit, Suchbegriffe, optional?]
|   Kategorie: essential_oil | carrier_oil | wax | additive
|   Suchbegriffe (klein geschrieben) werden in Produktname und botanischem
|   Namen gesucht. Leere Liste = jedes Produkt der Kategorie passt.
|
*/

$lavender = ['lavendel', 'lavandula angustifolia', 'lavandula officinalis'];
$peppermint = ['pfefferminz', 'mentha piperita', 'mentha x piperita', 'mentha × piperita'];
$teaTree = ['teebaum', 'melaleuca alternifolia'];
$lemon = ['zitrone', 'citrus limon'];
$orange = ['orange', 'citrus sinensis'];
$eucalyptus = ['eukalyptus', 'eucalyptus'];
$rosemary = ['rosmarin', 'rosmarinus officinalis', 'salvia rosmarinus'];
$frankincense = ['weihrauch', 'boswellia'];
$cedarwood = ['zedernholz', 'zeder', 'cedrus'];
$geranium = ['geranie', 'pelargonium'];
$ylang = ['ylang', 'cananga odorata'];

$jojoba = ['jojoba', 'simmondsia'];
$almond = ['mandel', 'prunus dulcis', 'prunus amygdalus'];
$argan = ['argan'];
$shea = ['shea', 'karité', 'karite', 'butyrospermum', 'vitellaria'];
$beeswax = ['bienenwachs', 'cera alba'];
$emulsifier = ['honig', 'sahne', 'milch', 'emulgator', 'salz'];
$salt = ['salz'];

$diffuserSteps = [
    'Wassertank des Diffusers nach Herstellerangabe füllen.',
    'Die ätherischen Öle hineintropfen.',
    'Höchstens 30–60 Minuten laufen lassen, danach lüften.',
];

return [
    // ── Schlaf & Entspannung ────────────────────────────────────────────────
    [
        'slug' => 'schlaf-roll-on',
        'title' => 'Schlaf-Roll-on',
        'purpose' => 'Zum Abschalten vor dem Schlafengehen',
        'category' => 'sleep',
        'method' => 'roll_on',
        'yield' => '10 ml',
        'ingredients' => [
            ['Jojobaöl', 'carrier_oil', 10, 'ml', $jojoba],
            ['Lavendel', 'essential_oil', 2, 'Tr.', $lavender],
            ['Zedernholz', 'essential_oil', 1, 'Tr.', $cedarwood],
        ],
        'steps' => [
            'Die ätherischen Öle in eine 10-ml-Roll-on-Flasche tropfen.',
            'Mit Jojobaöl auffüllen, verschließen und sanft schwenken.',
            'Abends auf Handgelenke und Nacken auftragen.',
        ],
    ],
    [
        'slug' => 'abendruhe-diffuser',
        'title' => 'Abendruhe',
        'purpose' => 'Ruhiger Duft für den Feierabend',
        'category' => 'sleep',
        'method' => 'diffuser',
        'yield' => '1 Füllung',
        'ingredients' => [
            ['Lavendel', 'essential_oil', 3, 'Tr.', $lavender],
            ['Orange süß', 'essential_oil', 2, 'Tr.', $orange],
        ],
        'steps' => $diffuserSteps,
    ],
    [
        'slug' => 'entspannungsbad',
        'title' => 'Entspannungsbad',
        'purpose' => 'Warmes Bad zum Loslassen',
        'category' => 'sleep',
        'method' => 'bath',
        'yield' => '1 Vollbad',
        'ingredients' => [
            ['Lavendel', 'essential_oil', 4, 'Tr.', $lavender],
            ['Emulgator (Sahne, Honig oder Meersalz)', 'additive', 2, 'EL', $emulsifier],
        ],
        'steps' => [
            'Die ätherischen Öle gründlich mit dem Emulgator verrühren.',
            'Erst dann ins einlaufende Badewasser (ca. 37 °C) geben.',
            '15–20 Minuten baden, danach warm halten und ruhen.',
        ],
        'safety' => 'Ätherische Öle nie pur ins Badewasser geben: Sie mischen sich nicht mit Wasser und können die Haut reizen.',
    ],

    // ── Atemwege ────────────────────────────────────────────────────────────
    [
        'slug' => 'erkaeltungs-inhalation',
        'title' => 'Dampf-Inhalation',
        'purpose' => 'Bei verstopfter Nase und Erkältung',
        'category' => 'respiratory',
        'method' => 'inhalation',
        'yield' => '1 Anwendung',
        'ingredients' => [
            ['Eukalyptus', 'essential_oil', 1, 'Tr.', $eucalyptus],
            ['Lavendel', 'essential_oil', 1, 'Tr.', $lavender],
        ],
        'steps' => [
            'Eine Schüssel mit heißem, nicht mehr kochendem Wasser füllen.',
            'Die ätherischen Öle hineingeben.',
            'Mit geschlossenen Augen und einem Handtuch über dem Kopf 5–10 Minuten ruhig einatmen.',
        ],
        'safety' => 'Nicht für Kinder unter 10 Jahren und nicht bei Asthma. Augen geschlossen halten.',
    ],
    [
        'slug' => 'brust-balsam',
        'title' => 'Brust-Balsam',
        'purpose' => 'Wohltuend bei Erkältung zum Einreiben',
        'category' => 'respiratory',
        'method' => 'balm',
        'yield' => '≈ 30 g',
        'ingredients' => [
            ['Basisöl nach Wahl (z. B. Mandelöl)', 'carrier_oil', 25, 'ml', []],
            ['Bienenwachs', 'wax', 5, 'g', $beeswax],
            ['Eukalyptus', 'essential_oil', 6, 'Tr.', $eucalyptus],
        ],
        'steps' => [
            'Basisöl und Bienenwachs im Wasserbad schmelzen.',
            'Vom Herd nehmen und kurz abkühlen lassen.',
            'Eukalyptus einrühren, in einen sauberen Tiegel füllen und fest werden lassen.',
            'Bei Bedarf dünn auf Brust und Rücken einreiben.',
        ],
        'safety' => 'Nicht für Kinder unter 10 Jahren und nicht bei Asthma. Nicht im Gesicht anwenden.',
    ],
    [
        'slug' => 'durchatmen-diffuser',
        'title' => 'Durchatmen',
        'purpose' => 'Klarer, frischer Duft für die Erkältungszeit',
        'category' => 'respiratory',
        'method' => 'diffuser',
        'yield' => '1 Füllung',
        'ingredients' => [
            ['Eukalyptus', 'essential_oil', 2, 'Tr.', $eucalyptus],
            ['Zitrone', 'essential_oil', 2, 'Tr.', $lemon],
            ['Pfefferminze', 'essential_oil', 1, 'Tr.', $peppermint],
        ],
        'steps' => $diffuserSteps,
        'safety' => 'Nicht in Räumen verdampfen, in denen sich Kleinkinder aufhalten (Eukalyptus, Pfefferminze).',
    ],

    // ── Konzentration ───────────────────────────────────────────────────────
    [
        'slug' => 'fokus-diffuser',
        'title' => 'Fokus',
        'purpose' => 'Wach und konzentriert am Schreibtisch',
        'category' => 'focus',
        'method' => 'diffuser',
        'yield' => '1 Füllung',
        'ingredients' => [
            ['Rosmarin', 'essential_oil', 2, 'Tr.', $rosemary],
            ['Zitrone', 'essential_oil', 3, 'Tr.', $lemon],
        ],
        'steps' => $diffuserSteps,
    ],
    [
        'slug' => 'guten-morgen-diffuser',
        'title' => 'Guten Morgen',
        'purpose' => 'Fröhlicher Start in den Tag',
        'category' => 'focus',
        'method' => 'diffuser',
        'yield' => '1 Füllung',
        'ingredients' => [
            ['Orange süß', 'essential_oil', 3, 'Tr.', $orange],
            ['Pfefferminze', 'essential_oil', 1, 'Tr.', $peppermint],
        ],
        'steps' => $diffuserSteps,
    ],
    [
        'slug' => 'lern-roll-on',
        'title' => 'Lern-Roll-on',
        'purpose' => 'Für klare Gedanken unterwegs',
        'category' => 'focus',
        'method' => 'roll_on',
        'yield' => '10 ml',
        'ingredients' => [
            ['Jojobaöl', 'carrier_oil', 10, 'ml', $jojoba],
            ['Rosmarin', 'essential_oil', 1, 'Tr.', $rosemary],
            ['Pfefferminze', 'essential_oil', 1, 'Tr.', $peppermint],
        ],
        'steps' => [
            'Die ätherischen Öle in eine 10-ml-Roll-on-Flasche tropfen.',
            'Mit Jojobaöl auffüllen und schwenken.',
            'Bei Bedarf auf Nacken und Handgelenke auftragen und den Duft einatmen.',
        ],
        'safety' => 'Pfefferminze nicht bei Kindern unter 6 Jahren. Rosmarin nicht bei Epilepsie oder Bluthochdruck. Nicht in Augennähe.',
    ],

    // ── Kopf & Muskeln ──────────────────────────────────────────────────────
    [
        'slug' => 'kopf-frei-roll-on',
        'title' => 'Kopf-frei-Roll-on',
        'purpose' => 'Kühlend für Schläfen und Nacken',
        'category' => 'relief',
        'method' => 'roll_on',
        'yield' => '10 ml',
        'ingredients' => [
            ['Jojobaöl', 'carrier_oil', 10, 'ml', $jojoba],
            ['Pfefferminze', 'essential_oil', 2, 'Tr.', $peppermint],
            ['Lavendel', 'essential_oil', 1, 'Tr.', $lavender],
        ],
        'steps' => [
            'Die ätherischen Öle in eine 10-ml-Roll-on-Flasche tropfen.',
            'Mit Jojobaöl auffüllen und schwenken.',
            'Sparsam auf Schläfen und Nacken auftragen, Augen aussparen.',
        ],
        'safety' => 'Nicht bei Kindern unter 6 Jahren. Nicht in Augennähe auftragen. Bei häufigen oder starken Kopfschmerzen ärztlich abklären.',
    ],
    [
        'slug' => 'muskel-massageoel',
        'title' => 'Muskel-Massageöl',
        'purpose' => 'Nach Sport oder langem Sitzen',
        'category' => 'relief',
        'method' => 'care_oil',
        'yield' => '50 ml',
        'ingredients' => [
            ['Basisöl nach Wahl (z. B. Mandelöl)', 'carrier_oil', 50, 'ml', []],
            ['Rosmarin', 'essential_oil', 6, 'Tr.', $rosemary],
            ['Lavendel', 'essential_oil', 6, 'Tr.', $lavender],
        ],
        'steps' => [
            'Die ätherischen Öle in eine 50-ml-Flasche (am besten Braunglas) tropfen.',
            'Mit dem Basisöl auffüllen und schütteln.',
            'Auf verspannte Stellen einmassieren.',
        ],
        'safety' => 'Rosmarin nicht bei Epilepsie oder Bluthochdruck und nicht in der Schwangerschaft.',
    ],
    [
        'slug' => 'wohlfuehl-massageoel',
        'title' => 'Wohlfühl-Massageöl',
        'purpose' => 'Sanfte Massage zum Entspannen',
        'category' => 'relief',
        'method' => 'care_oil',
        'yield' => '50 ml',
        'ingredients' => [
            ['Süßmandelöl', 'carrier_oil', 50, 'ml', $almond],
            ['Lavendel', 'essential_oil', 4, 'Tr.', $lavender],
            ['Orange süß', 'essential_oil', 4, 'Tr.', $orange],
            ['Ylang-Ylang', 'essential_oil', 2, 'Tr.', $ylang],
        ],
        'steps' => [
            'Die ätherischen Öle in eine 50-ml-Flasche tropfen.',
            'Mit Süßmandelöl auffüllen und schütteln.',
            'Handwarm einmassieren.',
        ],
        'safety' => 'Ylang-Ylang sparsam dosieren: In höherer Menge kann der Duft Kopfschmerzen auslösen.',
    ],

    // ── Haut & Pflege ───────────────────────────────────────────────────────
    [
        'slug' => 'beruhigendes-gesichtsoel',
        'title' => 'Beruhigendes Gesichtsöl',
        'purpose' => 'Für trockene und gereizte Haut',
        'category' => 'skin',
        'method' => 'care_oil',
        'yield' => '30 ml',
        'ingredients' => [
            ['Jojobaöl', 'carrier_oil', 20, 'ml', $jojoba],
            ['Arganöl', 'carrier_oil', 10, 'ml', $argan],
            ['Lavendel', 'essential_oil', 2, 'Tr.', $lavender],
            ['Weihrauch', 'essential_oil', 1, 'Tr.', $frankincense],
        ],
        'steps' => [
            'Jojoba- und Arganöl in einer 30-ml-Pipettenflasche mischen.',
            'Die ätherischen Öle zugeben und schwenken.',
            'Abends 2–3 Tropfen auf der noch leicht feuchten Haut einarbeiten.',
        ],
    ],
    [
        'slug' => 'pickel-punktpflege',
        'title' => 'Pickel-Punktpflege',
        'purpose' => 'Gezielt für einzelne Unreinheiten',
        'category' => 'skin',
        'method' => 'care_oil',
        'yield' => '10 ml',
        'ingredients' => [
            ['Jojobaöl', 'carrier_oil', 10, 'ml', $jojoba],
            ['Teebaum', 'essential_oil', 4, 'Tr.', $teaTree],
        ],
        'steps' => [
            'Teebaum in eine 10-ml-Flasche tropfen und mit Jojobaöl auffüllen.',
            'Mit einem Wattestäbchen nur auf einzelne Stellen tupfen.',
        ],
        'safety' => 'Nur punktuell anwenden, nicht großflächig. Altes, oxidiertes Teebaumöl kann Allergien auslösen: frisch verwenden und kühl lagern.',
    ],
    [
        'slug' => 'lippenbalsam',
        'title' => 'Lippenbalsam',
        'purpose' => 'Pflege für rissige Lippen',
        'category' => 'skin',
        'method' => 'balm',
        'yield' => '≈ 15 g',
        'ingredients' => [
            ['Süßmandelöl', 'carrier_oil', 6, 'ml', $almond],
            ['Sheabutter', 'carrier_oil', 5, 'g', $shea],
            ['Bienenwachs', 'wax', 4, 'g', $beeswax],
            ['Lavendel', 'essential_oil', 1, 'Tr.', $lavender, true],
        ],
        'steps' => [
            'Mandelöl, Sheabutter und Bienenwachs im Wasserbad schmelzen.',
            'Kurz abkühlen lassen, nach Wunsch Lavendel einrühren.',
            'In Lippenstifthülsen oder kleine Tiegel füllen.',
        ],
    ],
    [
        'slug' => 'handbalsam',
        'title' => 'Handbalsam',
        'purpose' => 'Reichhaltig für beanspruchte Hände',
        'category' => 'skin',
        'method' => 'balm',
        'yield' => '≈ 50 g',
        'ingredients' => [
            ['Sheabutter', 'carrier_oil', 20, 'g', $shea],
            ['Süßmandelöl', 'carrier_oil', 22, 'ml', $almond],
            ['Bienenwachs', 'wax', 8, 'g', $beeswax],
            ['Lavendel', 'essential_oil', 5, 'Tr.', $lavender],
            ['Geranie', 'essential_oil', 3, 'Tr.', $geranium],
        ],
        'steps' => [
            'Sheabutter, Mandelöl und Bienenwachs im Wasserbad schmelzen.',
            'Etwas abkühlen lassen und die ätherischen Öle einrühren.',
            'In einen Tiegel füllen und fest werden lassen.',
        ],
    ],
    [
        'slug' => 'koerperoel',
        'title' => 'Körperöl nach dem Duschen',
        'purpose' => 'Zarte Pflege für den ganzen Körper',
        'category' => 'skin',
        'method' => 'care_oil',
        'yield' => '100 ml',
        'ingredients' => [
            ['Basisöl nach Wahl (z. B. Mandelöl)', 'carrier_oil', 100, 'ml', []],
            ['Lavendel', 'essential_oil', 10, 'Tr.', $lavender],
            ['Geranie', 'essential_oil', 6, 'Tr.', $geranium],
        ],
        'steps' => [
            'Die ätherischen Öle in eine 100-ml-Flasche tropfen.',
            'Mit dem Basisöl auffüllen und schütteln.',
            'Auf die noch feuchte Haut auftragen.',
        ],
    ],
    [
        'slug' => 'fussbad',
        'title' => 'Erfrischendes Fußbad',
        'purpose' => 'Für müde Füße am Abend',
        'category' => 'skin',
        'method' => 'bath',
        'yield' => '1 Fußbad',
        'ingredients' => [
            ['Meersalz', 'additive', 2, 'EL', $salt],
            ['Teebaum', 'essential_oil', 2, 'Tr.', $teaTree],
            ['Lavendel', 'essential_oil', 2, 'Tr.', $lavender],
        ],
        'steps' => [
            'Die ätherischen Öle mit dem Meersalz vermischen.',
            'In eine Schüssel mit warmem Wasser geben und auflösen.',
            '10–15 Minuten baden, danach gut abtrocknen.',
        ],
    ],

    // ── Haare ───────────────────────────────────────────────────────────────
    [
        'slug' => 'kopfhaut-oel',
        'title' => 'Kopfhaut-Öl',
        'purpose' => 'Pflegende Kur vor der Haarwäsche',
        'category' => 'hair',
        'method' => 'care_oil',
        'yield' => '30 ml',
        'ingredients' => [
            ['Jojobaöl', 'carrier_oil', 30, 'ml', $jojoba],
            ['Rosmarin', 'essential_oil', 4, 'Tr.', $rosemary],
            ['Zedernholz', 'essential_oil', 2, 'Tr.', $cedarwood],
        ],
        'steps' => [
            'Die ätherischen Öle in eine 30-ml-Pipettenflasche tropfen und mit Jojobaöl auffüllen.',
            'Einige Tropfen in die Kopfhaut einmassieren.',
            '30 Minuten einwirken lassen, dann die Haare wie gewohnt waschen.',
        ],
        'safety' => 'Rosmarin nicht bei Epilepsie oder Bluthochdruck und nicht in der Schwangerschaft.',
    ],
    [
        'slug' => 'haarspitzen-oel',
        'title' => 'Haarspitzen-Öl',
        'purpose' => 'Gegen trockene Spitzen',
        'category' => 'hair',
        'method' => 'care_oil',
        'yield' => '30 ml',
        'ingredients' => [
            ['Arganöl', 'carrier_oil', 30, 'ml', $argan],
            ['Lavendel', 'essential_oil', 2, 'Tr.', $lavender],
        ],
        'steps' => [
            'Lavendel in eine 30-ml-Pipettenflasche tropfen und mit Arganöl auffüllen.',
            '1–2 Tropfen zwischen den Handflächen verreiben und in die Spitzen geben.',
        ],
    ],

    // ── Raumduft ────────────────────────────────────────────────────────────
    [
        'slug' => 'waldluft-diffuser',
        'title' => 'Waldluft',
        'purpose' => 'Warmer, holziger Duft für gemütliche Abende',
        'category' => 'room',
        'method' => 'diffuser',
        'yield' => '1 Füllung',
        'ingredients' => [
            ['Zedernholz', 'essential_oil', 2, 'Tr.', $cedarwood],
            ['Weihrauch', 'essential_oil', 2, 'Tr.', $frankincense],
            ['Orange süß', 'essential_oil', 2, 'Tr.', $orange],
        ],
        'steps' => $diffuserSteps,
    ],
    [
        'slug' => 'zitrusfrische-diffuser',
        'title' => 'Zitrusfrische',
        'purpose' => 'Frischer Duft nach dem Putzen oder Kochen',
        'category' => 'room',
        'method' => 'diffuser',
        'yield' => '1 Füllung',
        'ingredients' => [
            ['Zitrone', 'essential_oil', 3, 'Tr.', $lemon],
            ['Orange süß', 'essential_oil', 3, 'Tr.', $orange],
        ],
        'steps' => $diffuserSteps,
    ],
];
