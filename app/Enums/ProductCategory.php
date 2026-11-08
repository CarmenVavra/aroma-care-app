<?php

namespace App\Enums;

enum ProductCategory: string
{
    case EssentialOil = 'essential_oil';
    case CarrierOil = 'carrier_oil';
    case Wax = 'wax';
    case Additive = 'additive';

    /**
     * Full label, e.g. for table columns and detail headers.
     */
    public function label(): string
    {
        return match ($this) {
            self::EssentialOil => 'Ätherisches Öl',
            self::CarrierOil => 'Basisöl',
            self::Wax => 'Wachs',
            self::Additive => 'Additiv',
        };
    }

    /**
     * Plural label for filter chips.
     */
    public function pluralLabel(): string
    {
        return match ($this) {
            self::EssentialOil => 'Ätherische Öle',
            self::CarrierOil => 'Basisöle',
            self::Wax => 'Wachse',
            self::Additive => 'Additive',
        };
    }

    /**
     * Short label for the segmented control in the form.
     */
    public function shortLabel(): string
    {
        return match ($this) {
            self::EssentialOil => 'Äth. Öl',
            default => $this->label(),
        };
    }

    /**
     * Tailwind classes for the category tile (background + foreground).
     */
    public function tileClasses(): string
    {
        return match ($this) {
            self::EssentialOil => 'bg-sky-100 text-cobalt-700 dark:bg-cobalt-700/25 dark:text-cobalt-200',
            self::CarrierOil => 'bg-[#E6E0F6] text-[#4B2E8F] dark:bg-violet-500/25 dark:text-[#CDB7F5]',
            self::Wax => 'bg-[#DDE6FB] text-[#1E3A8A] dark:bg-cobalt-500/20 dark:text-cobalt-200',
            self::Additive => 'bg-[#E3E6F2] text-muted dark:bg-white/10 dark:text-chalk',
        };
    }

    /**
     * @return array<string, string> value => label
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
