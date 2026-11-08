<?php

namespace App\Support;

/**
 * Common single essential oils: search names (English/German, lower case) →
 * German name + botanical name. Used to turn a scanned product title such as
 * "doTERRA Lavender Essential Oil 15 mL" into "Lavendel" / "Lavandula angustifolia".
 *
 * Longer names are checked first, so "wild orange" wins over "orange".
 */
final class EssentialOilNames
{
    /** @var array<string, array{0: string, 1: string}> */
    private const OILS = [
        'roman chamomile' => ['Römische Kamille', 'Anthemis nobilis'],
        'römische kamille' => ['Römische Kamille', 'Anthemis nobilis'],
        'blue tansy' => ['Blauer Rainfarn', 'Tanacetum annuum'],
        'black pepper' => ['Schwarzer Pfeffer', 'Piper nigrum'],
        'schwarzer pfeffer' => ['Schwarzer Pfeffer', 'Piper nigrum'],
        'juniper berry' => ['Wacholderbeere', 'Juniperus communis'],
        'wacholder' => ['Wacholderbeere', 'Juniperus communis'],
        'siberian fir' => ['Sibirische Tanne', 'Abies sibirica'],
        'douglas fir' => ['Douglasie', 'Pseudotsuga menziesii'],
        'clary sage' => ['Muskatellersalbei', 'Salvia sclarea'],
        'muskatellersalbei' => ['Muskatellersalbei', 'Salvia sclarea'],
        'wild orange' => ['Wilde Orange', 'Citrus sinensis'],
        'wilde orange' => ['Wilde Orange', 'Citrus sinensis'],
        'sweet orange' => ['Orange süß', 'Citrus sinensis'],
        'tea tree' => ['Teebaum', 'Melaleuca alternifolia'],
        'melaleuca' => ['Teebaum', 'Melaleuca alternifolia'],
        'teebaum' => ['Teebaum', 'Melaleuca alternifolia'],
        'ylang ylang' => ['Ylang-Ylang', 'Cananga odorata'],
        'ylang-ylang' => ['Ylang-Ylang', 'Cananga odorata'],
        'lemongrass' => ['Zitronengras', 'Cymbopogon flexuosus'],
        'zitronengras' => ['Zitronengras', 'Cymbopogon flexuosus'],
        'peppermint' => ['Pfefferminze', 'Mentha piperita'],
        'pfefferminz' => ['Pfefferminze', 'Mentha piperita'],
        'spearmint' => ['Grüne Minze', 'Mentha spicata'],
        'wintergreen' => ['Wintergrün', 'Gaultheria procumbens'],
        'frankincense' => ['Weihrauch', 'Boswellia carterii'],
        'weihrauch' => ['Weihrauch', 'Boswellia carterii'],
        'helichrysum' => ['Immortelle', 'Helichrysum italicum'],
        'immortelle' => ['Immortelle', 'Helichrysum italicum'],
        'sandalwood' => ['Sandelholz', 'Santalum album'],
        'sandelholz' => ['Sandelholz', 'Santalum album'],
        'cedarwood' => ['Zedernholz', 'Juniperus virginiana'],
        'zedernholz' => ['Zedernholz', 'Cedrus atlantica'],
        'eucalyptus' => ['Eukalyptus', 'Eucalyptus radiata'],
        'eukalyptus' => ['Eukalyptus', 'Eucalyptus radiata'],
        'grapefruit' => ['Grapefruit', 'Citrus paradisi'],
        'tangerine' => ['Mandarine', 'Citrus reticulata'],
        'mandarin' => ['Mandarine', 'Citrus reticulata'],
        'bergamot' => ['Bergamotte', 'Citrus bergamia'],
        'bergamotte' => ['Bergamotte', 'Citrus bergamia'],
        'lavender' => ['Lavendel', 'Lavandula angustifolia'],
        'lavendel' => ['Lavendel', 'Lavandula angustifolia'],
        'rosemary' => ['Rosmarin', 'Rosmarinus officinalis'],
        'rosmarin' => ['Rosmarin', 'Rosmarinus officinalis'],
        'geranium' => ['Geranie', 'Pelargonium graveolens'],
        'geranie' => ['Geranie', 'Pelargonium graveolens'],
        'patchouli' => ['Patchouli', 'Pogostemon cablin'],
        'vetiver' => ['Vetiver', 'Vetiveria zizanioides'],
        'marjoram' => ['Majoran', 'Origanum majorana'],
        'majoran' => ['Majoran', 'Origanum majorana'],
        'oregano' => ['Oregano', 'Origanum vulgare'],
        'thyme' => ['Thymian', 'Thymus vulgaris'],
        'thymian' => ['Thymian', 'Thymus vulgaris'],
        'cypress' => ['Zypresse', 'Cupressus sempervirens'],
        'zypresse' => ['Zypresse', 'Cupressus sempervirens'],
        'copaiba' => ['Copaiba', 'Copaifera officinalis'],
        'cinnamon' => ['Zimtrinde', 'Cinnamomum zeylanicum'],
        'zimt' => ['Zimtrinde', 'Cinnamomum zeylanicum'],
        'ginger' => ['Ingwer', 'Zingiber officinale'],
        'ingwer' => ['Ingwer', 'Zingiber officinale'],
        'fennel' => ['Fenchel', 'Foeniculum vulgare'],
        'fenchel' => ['Fenchel', 'Foeniculum vulgare'],
        'coriander' => ['Koriander', 'Coriandrum sativum'],
        'koriander' => ['Koriander', 'Coriandrum sativum'],
        'basil' => ['Basilikum', 'Ocimum basilicum'],
        'basilikum' => ['Basilikum', 'Ocimum basilicum'],
        'clove' => ['Nelke', 'Eugenia caryophyllata'],
        'nelke' => ['Nelke', 'Eugenia caryophyllata'],
        'myrrh' => ['Myrrhe', 'Commiphora myrrha'],
        'myrrhe' => ['Myrrhe', 'Commiphora myrrha'],
        'melissa' => ['Melisse', 'Melissa officinalis'],
        'melisse' => ['Melisse', 'Melissa officinalis'],
        'jasmine' => ['Jasmin', 'Jasminum grandiflorum'],
        'jasmin' => ['Jasmin', 'Jasminum grandiflorum'],
        'neroli' => ['Neroli', 'Citrus aurantium'],
        'lemon' => ['Zitrone', 'Citrus limon'],
        'zitrone' => ['Zitrone', 'Citrus limon'],
        'lime' => ['Limette', 'Citrus aurantifolia'],
        'limette' => ['Limette', 'Citrus aurantifolia'],
        'orange' => ['Orange', 'Citrus sinensis'],
    ];

    /**
     * @return array{name: string, latin: string}|null
     */
    public static function find(string $text): ?array
    {
        $text = mb_strtolower($text);
        $keys = array_keys(self::OILS);
        usort($keys, fn (string $a, string $b) => mb_strlen($b) <=> mb_strlen($a));

        foreach ($keys as $key) {
            if (preg_match('/(?<![\p{L}])'.preg_quote($key, '/').'/u', $text)) {
                return ['name' => self::OILS[$key][0], 'latin' => self::OILS[$key][1]];
            }
        }

        return null;
    }
}
