<?php

namespace App\Support;

/**
 * Common single essential oils: search names (English/German, lower case) →
 * botanical name. Used to complete scanned product data, e.g.
 * "doTERRA Lavender Essential Oil 15 mL" → "Lavandula angustifolia".
 * The product name itself is never translated (it stays as on the bottle).
 *
 * Longer names are checked first, so "wild orange" wins over "orange".
 */
final class EssentialOilNames
{
    /** @var array<string, string> */
    private const OILS = [
        'roman chamomile' => 'Anthemis nobilis',
        'römische kamille' => 'Anthemis nobilis',
        'blue tansy' => 'Tanacetum annuum',
        'black pepper' => 'Piper nigrum',
        'schwarzer pfeffer' => 'Piper nigrum',
        'juniper berry' => 'Juniperus communis',
        'wacholder' => 'Juniperus communis',
        'siberian fir' => 'Abies sibirica',
        'douglas fir' => 'Pseudotsuga menziesii',
        'clary sage' => 'Salvia sclarea',
        'muskatellersalbei' => 'Salvia sclarea',
        'wild orange' => 'Citrus sinensis',
        'wilde orange' => 'Citrus sinensis',
        'sweet orange' => 'Citrus sinensis',
        'tea tree' => 'Melaleuca alternifolia',
        'melaleuca' => 'Melaleuca alternifolia',
        'teebaum' => 'Melaleuca alternifolia',
        'ylang ylang' => 'Cananga odorata',
        'ylang-ylang' => 'Cananga odorata',
        'lemongrass' => 'Cymbopogon flexuosus',
        'zitronengras' => 'Cymbopogon flexuosus',
        'peppermint' => 'Mentha piperita',
        'pfefferminz' => 'Mentha piperita',
        'spearmint' => 'Mentha spicata',
        'wintergreen' => 'Gaultheria procumbens',
        'frankincense' => 'Boswellia carterii',
        'weihrauch' => 'Boswellia carterii',
        'helichrysum' => 'Helichrysum italicum',
        'immortelle' => 'Helichrysum italicum',
        'sandalwood' => 'Santalum album',
        'sandelholz' => 'Santalum album',
        'cedarwood' => 'Juniperus virginiana',
        'zedernholz' => 'Cedrus atlantica',
        'eucalyptus' => 'Eucalyptus radiata',
        'eukalyptus' => 'Eucalyptus radiata',
        'grapefruit' => 'Citrus paradisi',
        'tangerine' => 'Citrus reticulata',
        'mandarin' => 'Citrus reticulata',
        'bergamot' => 'Citrus bergamia',
        'bergamotte' => 'Citrus bergamia',
        'lavender' => 'Lavandula angustifolia',
        'lavendel' => 'Lavandula angustifolia',
        'rosemary' => 'Rosmarinus officinalis',
        'rosmarin' => 'Rosmarinus officinalis',
        'geranium' => 'Pelargonium graveolens',
        'geranie' => 'Pelargonium graveolens',
        'patchouli' => 'Pogostemon cablin',
        'vetiver' => 'Vetiveria zizanioides',
        'marjoram' => 'Origanum majorana',
        'majoran' => 'Origanum majorana',
        'oregano' => 'Origanum vulgare',
        'thyme' => 'Thymus vulgaris',
        'thymian' => 'Thymus vulgaris',
        'cypress' => 'Cupressus sempervirens',
        'zypresse' => 'Cupressus sempervirens',
        'copaiba' => 'Copaifera officinalis',
        'cinnamon' => 'Cinnamomum zeylanicum',
        'zimt' => 'Cinnamomum zeylanicum',
        'ginger' => 'Zingiber officinale',
        'ingwer' => 'Zingiber officinale',
        'fennel' => 'Foeniculum vulgare',
        'fenchel' => 'Foeniculum vulgare',
        'coriander' => 'Coriandrum sativum',
        'koriander' => 'Coriandrum sativum',
        'basil' => 'Ocimum basilicum',
        'basilikum' => 'Ocimum basilicum',
        'clove' => 'Eugenia caryophyllata',
        'nelke' => 'Eugenia caryophyllata',
        'myrrh' => 'Commiphora myrrha',
        'myrrhe' => 'Commiphora myrrha',
        'melissa' => 'Melissa officinalis',
        'melisse' => 'Melissa officinalis',
        'jasmine' => 'Jasminum grandiflorum',
        'jasmin' => 'Jasminum grandiflorum',
        'neroli' => 'Citrus aurantium',
        'lemon' => 'Citrus limon',
        'zitrone' => 'Citrus limon',
        'lime' => 'Citrus aurantifolia',
        'limette' => 'Citrus aurantifolia',
        'orange' => 'Citrus sinensis',
    ];

    public static function botanicalName(string $text): ?string
    {
        $text = mb_strtolower($text);
        $keys = array_keys(self::OILS);
        usort($keys, fn (string $a, string $b) => mb_strlen($b) <=> mb_strlen($a));

        foreach ($keys as $key) {
            if (preg_match('/(?<![\p{L}])'.preg_quote($key, '/').'/u', $text)) {
                return self::OILS[$key];
            }
        }

        return null;
    }
}
