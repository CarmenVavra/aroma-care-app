<?php

namespace App\Enums;

enum RecipeMethod: string
{
    case RollOn = 'roll_on';
    case CareOil = 'care_oil';
    case Balm = 'balm';
    case Diffuser = 'diffuser';
    case Inhalation = 'inhalation';
    case Bath = 'bath';

    public function label(): string
    {
        return match ($this) {
            self::RollOn => 'Roll-on',
            self::CareOil => 'Pflegeöl',
            self::Balm => 'Balsam',
            self::Diffuser => 'Diffuser',
            self::Inhalation => 'Inhalation',
            self::Bath => 'Bad',
        };
    }

    /**
     * Applied to the skin: only then a dilution percentage is meaningful.
     */
    public function isTopical(): bool
    {
        return in_array($this, [self::RollOn, self::CareOil, self::Balm], true);
    }
}
